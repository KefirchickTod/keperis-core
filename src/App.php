<?php

/**
 * @author Zahar Pylypchuck
 * @version 0.79
 * @package App|App
 */

namespace Keperis;


use FastRoute\Dispatcher;
use RuntimeException;
use Keperis\Http\Request;
use Keperis\Http\Response;
use Keperis\Interfaces\RequestHandlerInterface;
use Keperis\Interfaces\ResponseInterface;
use Keperis\Middleware\Middleware;
use Keperis\Middleware\RequestHandler;
use Keperis\Router\Route;
use Keperis\Router\RouteGroup;
use Keperis\Router\Router;

class App
{
    /**
     * @var Container
     */
    public $container;
    /**
     * @var Middleware
     */
    private $middleware;
    /**
     * @var RequestHandlerInterface
     */
    private $requestHandle;

    function __construct($setting = [])
    {

        $this->container = new Container(compact('setting'));

        $this->middleware = new Middleware(new \Keperis\Middleware\RequestHandler\NotFoundHandler());

        $this->requestHandle = new RequestHandler();


    }

    /**
     * @param $pattern
     * @param $controller
     * @return Router
     */
    public function get($pattern, $controller, $methods = null)
    {

        return $this->map(['GET'], $pattern, $controller, $methods);
    }

    /**
     * @param array $methods
     * @param string $pattern
     * @param $func
     * @param $controller
     * @return Router
     */
    public function map(array $methods, $pattern, $controller, $func = null)
    {
        if (!is_object($controller) && class_exists($controller)) {
            $controller = new $controller($this->container);
        } elseif (!is_object($controller)) {
            debug($controller, __CLASS__, __LINE__);
            throw new RuntimeException("Not found controller for pattern $pattern");
        }
        $router = $this->container->get('router')->map($methods, $pattern, $controller, $func);
        /**
         * @var $router Router
         */
        $router->setContainer($this->container);


        return $router;
    }



    /**
     * @param $pattern
     * @param $controller
     * @return Router
     */
    public function post($pattern, $controller, $methods)
    {


        return $this->map(['POST'], $pattern, $controller, $methods);

    }

    /**
     * @param $pattern string
     * @param \Closure $callback
     * @return RouteGroup
     */
    public function group($pattern, \Closure $callback)
    {
        /** @var RouteGroup $group */
        $group = $this->container->get('router')->pushGroup($pattern, $callback);
        $group->setContainer($this->container);
        $group($this);
        $this->container->get('router')->popGroup();
        return $group;
    }

    public function __invoke(Request $request, Response $response)
    {
        $routeInfo = $request->getAttribute('routeInfo');
        /** @var  $router  Router */
        $router = $this->container->get('router');
        if ($routeInfo === null || ($routeInfo['request'] !== [$request->getMethod(), (string)$request->getUri()])) {
            $request = $this->dispatchRouterAndPrepareRoute($request, $router);
            $routeInfo = $request->getAttribute('routeInfo');


        }


        if ($routeInfo[0] === Dispatcher::FOUND) {
            /** @var  $route Route */
            $route = $router->lookupRoute($routeInfo[1]);
            $request = $request->withRouteName($route->getIndication());
            $response = $this->middleware->process($request, $response, $this->requestHandle);

            $response = $route->run($request, $response);

            return $response;
        } elseif ($routeInfo[0] === Dispatcher::NOT_FOUND) {


            /**
             * @var $response Response
             */
            $response = $response->withStatus(404);
        } else {

            $response = $response->withStatus(405);
        }
        return $response;
    }

    protected function dispatchRouterAndPrepareRoute(Request $request, Router $router): Request
    {
        $routeInfo = $router->dispatch($request);


        if ($routeInfo[0] === Dispatcher::FOUND) {
            $routerArgument = [];
            foreach ($routeInfo[2] as $key => $value) {
                $routerArgument[$key] = urldecode($value);
            }
            /** @var  $route Route */
            $route = $router->lookupRoute($routeInfo[1]);
            $route->prepare($request, $routerArgument);

            $request = $request->withAttribute('route', $route);

        }

        $routeInfo['request'] = [$request->getMethod(), (string)$request->getUri()];

        return $request->withAttribute('routeInfo', $routeInfo);
    }

    public function addMiddleware(RequestHandler $handler)
    {

        $this->requestHandle->setNext($handler);
        return $this;
    }


    /**
     * @return  ResponseInterface
     *
     */
    public function run()
    {
        /**
         * @var $request Request
         * @var $response Response
         * @var $router Router
         */
        $request = $this->container->get('request');
        $response = $this->container->get('response');
        $router = $this->container->get('router');

        if (is_callable([$request->getUri(), 'getBasePath']) && is_callable([$router, 'setBasePath'])) {
            $router->setBasePath($request->getUri()->getBasePath() ?: '');
        }

        try {

            //   var_dump($response);
            $response = $this($request, $response);


        } catch (RuntimeException $exception) {

            error_log($exception->getMessage());
            $response = $response->withStatus(301);
            //debug($exception->getMessage(), __METHOD__, __LINE__);
        }

        $response = $this->finalize($response);


        $this->respond($response);


        return $response;

    }

    protected function finalize(Response $response)
    {
        if ($this->isEmptyResponse($response)) {
            return $response->withoutHeader("Content-Type")->withoutHeader('Content-Length');
        }
        $size = $response->getBody()->getSize();
        if ($size && !$response->hasHeader('Content-Length')) {
            $response = $response->withHeader("Content-Length", (string)$size);
        }
        return $response;
    }

    protected function isEmptyResponse(ResponseInterface $response)
    {
        if (method_exists($response, 'isEmpty')) {
            return $response->isEmpty();
        }
        return in_array($response->getStatusCode(), [204, 205, 304]);
    }

    /**
     * @param ResponseInterface $response
     */
    public function respond(ResponseInterface $response)
    {

        if (!headers_sent()) {

            header(sprintf(
                'HTTP/%s %s %s',
                $response->getProtocolVersion(),
                $response->getStatusCode(),
                $response->getReasonPhrase()
            ));


            foreach ($response->getHeaders() as $name => $values) {
                foreach ($values as $value) {
                    header(sprintf('%s: %s', $name, $value));
                }
            }

        }


        if ($response->isStreamDownload()) {


            $callback = $response->getStreamDownload();
            call_user_func($callback, $response);
            return;
        }
        if (!$this->isEmptyResponse($response)) {
            $body = $response->getBody();


            if ($body->isSeekable()) {
                $body->rewind();
            }

            $settings = $this->container->get('setting')->toArray();
            $chunkSize = $settings['responseChunkSize'];
            if ($response->hasHeader('Content-Length')) {
                $contentLength = $response->getHeaderLine('Content-Length');
            }
            if (!isset($contentLength) || !$contentLength) {
                $contentLength = $body->getSize();
            }
            $totalChunks = ceil($contentLength / $chunkSize);
            $lastChunkSize = $contentLength % $chunkSize;
            $currentChunk = 0;
            while (!$body->eof() && $currentChunk < $totalChunks) {
                if (++$currentChunk == $totalChunks && $lastChunkSize > 0) {
                    $chunkSize = $lastChunkSize;
                }
                echo $body->read($chunkSize);
                if (connection_status() != CONNECTION_NORMAL) {
                    break;
                }
            }
        }

    }

}