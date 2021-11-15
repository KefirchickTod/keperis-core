<?php


namespace Keperis\Eloquent\Concerns;


use Keperis\MiddlewareTrait;

trait StructureFilterData
{
    use MiddlewareTrait;

    /**
     * @param $closer
     * @return bool
     */
    private function isCallable($closer)
    {
        return is_callable($closer);
    }


    /**
     * Convert link to object to callable
     * @param string $call
     * @return object
     */
    private function toObject($call)
    {
        if (!class_exists($call)) {
            throw new \InvalidArgumentException("Invalid callable  clouser : '$call' for call");
        }

        return new $call;
    }

    /**
     * @param callable|string ...$callable
     * @return self;
     */
    public function addFilter(...$callable)
    {
        foreach ($callable as $call) {
            if (is_string($call)) {
                $call = $this->toObject($call);
            }
            if ($this->isCallable($call)) {
                $this->addMiddleware($call);
            }
        }
        return $this;
    }
}