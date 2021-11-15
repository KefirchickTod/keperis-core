<?php


namespace Keperis\Router;


use Closure;
use Keperis\App;
use Keperis\Traits\CallableResolverAwareTrait;

class RouteGroup extends Routeable
{

    use CallableResolverAwareTrait;

    private $pattern;
    private $callable;

    public function __construct($pattern, $callable)
    {
        $this->pattern = $pattern;
        $this->callable = $callable;
    }


    public function __invoke(App $app = null)
    {
        $callable = $this->resolveCallable($this->callable);
        if ($callable instanceof Closure && $app !== null) {
            $callable = $callable->bindTo($app);
        }

        $callable();
    }


}