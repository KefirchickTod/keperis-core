<?php


namespace Keperis\Router;

use Keperis\Container;
use Keperis\Middleware\RequestHandler;

abstract class Routeable
{

    protected $requestHandle = [];
    /**
     * @var Container
     */
    private $container;
    private $pattern;

    public function getPattern()
    {
        return $this->pattern;
    }


    public function getMiddleware()
    {
        return $this->requestHandle;
    }

    public function setContainer(Container $container)
    {
        $this->container = $container;
        return $this;
    }
    public function add(RequestHandler $callable)
    {
        $this->requestHandle[] = $callable;
        return $this;
    }
}