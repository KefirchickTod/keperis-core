<?php


namespace src\Router;


use Error;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use FastRoute\RouteParser;
use Psr\Container\ContainerInterface;
use RuntimeException;
use src\Controller\Controller;
use src\Http\Request;
use src\Middleware\RequestHandler;
use function FastRoute\simpleDispatcher;

class Router
{


    /**
     * @var string
     */
    protected $fullPath;

    private $counting = 1;
    /**
     * @var array
     */
    private $routes;
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var RouteParser\Std
     */
    private $routeParser;
    /**
     * @var Dispatcher
     */
    private $dispatcher;

    /**
     * @var string
     */
    private $currentIndification;
    private $routeGroups = [];


    public function __construct(RouteParser $parser = null)
    {
        $this->routeParser = $parser ?: new RouteParser\Std();
    }


    /**
     * @param $pattern
     * @param $callable
     * @return RouteGroup
     */
    public function pushGroup($pattern, $callable)
    {
        $group = new RouteGroup($pattern, $callable);

        array_push($this->routeGroups, $group);
        return $group;
    }


    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return bool|mixed|RouteGroup
     */
    public function popGroup()
    {
        $group = array_pop($this->routeGroups);
        return $group instanceof RouteGroup ? $group : false;
    }

    public function name(string $name)
    {

        change_key($this->currentIndification, $name, $this->routes);
        $this->routes[$name] = $this->routes[$name]->withIndication($name);

        return $this;

    }

    /**
     * @param array $method
     * @param array $pattern
     * @param Controller|\Closure $controller
     * @param $func
     * @return $this
     */
    public function map(array $method, $pattern, $controller, $func)
    {
        $indication = !($controller instanceof \Closure) ? get_class($controller) . ".$func" : "route" . $this->counting;
        $route = new Route($method, $pattern, $controller, $indication, $func);

        $route->setContainer($this->container);


        $this->routes[$route->getIndication()] = $route;
        $this->currentIndification = $route->getIndication();
        $this->counting++;
        return $this;

    }

    public function dispatch(Request $request)
    {
        $uri = '/' . ltrim($request->getUri()->getPath(), '/');
        if (env('ROUTE_FULL_PATH', 'off') === 'on') {
            $uri = $request->getUri()->getBaseUrl() . $uri;
        }

        return $this->getDispatcher()->dispatch(
            $request->getMethod(),
            $uri
        );
    }

    /**
     * @return Dispatcher
     */
    protected function getDispatcher()
    {

        return $this->dispatcher ?: simpleDispatcher(function (RouteCollector $r) {
            foreach ($this->routes as $route) {

                /** @var $route Route */

                $r->addRoute($route->getMethods(), $route->getPattern(), $route->getIndication());


            }
        }, [
            'routeParser' => $this->routeParser,
        ]);
    }

    /**
     * @param null $counting
     * @return Controller
     * @var $route Route
     */
    public function getController($counting = null): Controller
    {
        $route = $counting ? $this->routes[$counting] : end($this->routes);
        $controller = $route->getController();
        if ($controller instanceof Controller) {
            return $controller;
        }
        if (class_exists($controller)) {
            return new $controller($this->container);
        }
        $controller = \App\Controller\User\UserController::class;
        return new $controller($this->container);

    }

    public function fullPath($path)
    {
        $this->fullPath = $path;
    }


    public function parse($path = null): array
    {
        $path = $path ?: $this->fullPath;
        if (!$path) {
            throw new Error("Empty path for parse in " . __FILE__ . " method " . __METHOD__);
        }

        return [];
    }

    public function lookupRoute($identifier)
    {

        if (!isset($this->routes[$identifier])) {
            throw new RuntimeException('Route not found, looks like your route cache is stale. ' . $identifier);
        }
        return $this->routes[$identifier];
    }

    public function middleware(RequestHandler $middleware)
    {
        $this->routes[$this->currentIndification]->add($middleware);
        return $this;
    }

    public function getRoutes()
    {
        return $this->routes;
    }


}