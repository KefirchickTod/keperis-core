<?php


namespace src\Router;


use FastRoute\RouteParser\Std;
use LogicException;
use src\Controller\Controller;
use src\Http\Body;
use src\Http\Request;
use src\Http\RequestResponse;
use src\Http\Response;
use src\Interfaces\ResponseInterface;
use src\Middleware\RequestHandler;
use src\Resource;
use src\View\View;

class Route extends Routeable
{

    /**
     * @var array|string[]
     * Array of allow method
     */
    private $methods = [];
    /**
     * @var string
     * pattern uri (user/list etc.)
     */
    private $pattern = '';
    /**
     * @var Controller|\Closure
     * Controller object of callable for callback
     */
    private $controller;

    private $indication;

    /**
     * @var string|null
     */
    private $func;
    /**
     * @var array
     * arguments from StdParse
     */
    private $arguments = [];


    /**
     * @var RouteGroup[]
     */
    private $groups = [];
    /**
     * @var bool
     */
    private $finalize = false;


    private $middleware;

    function __construct(array $methods, $pattern, $controller, $indication, $func, $groups = [])
    {
        $this->methods = $methods;


        if (env('ROUTE_FULL_PATH', 'off') === 'on') {
            $pattern = container()->request->getUri()->getBaseUrl() . $pattern;
        }


        $this->pattern = $pattern;
        $this->controller = $controller;
        $this->indication = $indication;
        $this->func = $func;
        $this->groups = $groups;


        $this->middleware = container()->get('middleware');


    }

    public function finalize()
    {
        if ($this->finalize) {
            return;
        }
        $groupMiddleware = [];
        foreach ($this->getGroups() as $group) {
            $groupMiddleware = array_merge($group->getMiddleware(), $groupMiddleware);
        }


        $this->requestHandle = array_merge($this->requestHandle, $groupMiddleware);

        $this->finalize = true;
    }


    /**
     * @return RouteGroup[]
     */
    public function getGroups()
    {
        return $this->groups;
    }

    public function getPath(array $params = [])
    {
        $routeParser = new Std();
        // (Maybe store the parsed form directly)
        $routes = $routeParser->parse($this->pattern);

        // One route pattern can correspond to multiple routes if it has optional parts
        foreach ($routes as $route) {
            $url = '';
            $paramIdx = 0;

            foreach ($route as $part) {
                // Fixed segment in the route
                if (is_string($part)) {
                    $url .= $part;
                    continue;
                }

                // Placeholder in the route
                if ($paramIdx === count($params)) {
                    throw new LogicException('Not enough parameters given');
                }

                $url .= $params[$paramIdx++] ?? $params[$part[0]];

            }

            // If number of params in route matches with number of params given, use that route.
            // Otherwise try to find a route that has more params
            if ($paramIdx === count($params)) {
                return $url;
            }
        }

        //debug($params, $routes);
        throw new LogicException('Too many parameters given ' . $url);
    }

    function getMethods()
    {
        return $this->methods;
    }


    function getPattern()
    {
        return $this->pattern;
    }

    function withIndication($name)
    {
        $clone = clone $this;
        $clone->indication = $name;
        return $clone;
    }

    function getIndication()
    {
        return $this->indication;
    }


    /**
     * Retrieve route arguments
     *
     * @return array
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * Replace route arguments
     *
     * @param array $arguments
     *
     * @return Route
     */
    public function setArguments(array $arguments)
    {
        $this->arguments = $arguments;
        return $this;
    }

    /**
     * Retrieve a specific route argument
     *
     * @param string $name
     * @param mixed $default
     *
     * @return mixed
     */
    public function getArgument($name, $default = null)
    {
        if (array_key_exists($name, $this->arguments)) {
            return $this->arguments[$name];
        }
        return $default;
    }

    public function prepare(Request $request, array $arguments)
    {
        // Add the arguments
        foreach ($arguments as $k => $v) {
            $this->setArgument($k, $v);
        }
    }

    public function setArgument($name, $value)
    {
        $this->arguments[$name] = $value;
        return $this;
    }


    public function __invoke(Request $request, Response $response, $routerinfo = [])
    {
        $routerinfo = $routerinfo ?: $request->getAttribute('routeInfo');
        $this->controller = $this->controller instanceof Controller || $this->controller instanceof \Closure ? $this->controller : new $this->controller;
        $handler = new RequestResponse();

        $responseController = $handler($this->controller, $this->getFunc(), $request, $response, $routerinfo);


        if ($responseController instanceof View) {
            $response = $response->write($responseController->render() . PHP_EOL);
        }
        if ($responseController instanceof ResponseInterface) {
            $response = $responseController;
        }


        if (!empty($responseController) && is_string($responseController)) {
            if ($response->getBody()->isWritable()) {
                $body = new Body(fopen('php://temp', 'r+'));

                $body->write(join('', [
                    $responseController,
                    $response->getBody(),
                ]));

                return $response->withBody($body);
            } else {

                $response->getBody()->write($responseController);
                return $response;
            }

        }

        return $response;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return ResponseInterface
     */
    public function run(Request $request, Response $response, $routerinfo = [])
    {
        $this->finalize();


        $response = $this->middleware->process($request, $response, new RequestHandler());
        return $this($request, $response, $routerinfo);


        //return $this($request, $response, $routerinfo);
    }


    public function withPattern($pattern)
    {
        $clone = clone $this;
        $clone->pattern = $pattern;
        return $clone;
    }

    public function getFunc()
    {
        return $this->func;
    }

}