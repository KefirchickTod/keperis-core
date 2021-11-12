<?php

namespace Keperis;

use RuntimeException;
use SplDoublyLinkedList;
use SplStack;

trait MiddlewareTrait
{
    /**
     * Middleware call stack
     *
     * @var  \SplStack
     * @link http://php.net/manual/class.splstack.php
     */
    protected $stack;

    /**
     * Middleware stack lock
     *
     * @var bool
     */
    protected $middlewareLock = false;

    /**
     * Call middleware stack
     *
     * @param $value
     *
     * @return mixed
     */

    /**
     * Call middleware stack
     *
     * @param $value
     * @param mixed ...$additional
     * @return mixed
     */
    public function callMiddlewareStack($value, ...$additional)
    {

        if (is_null($this->stack)) {
            $this->seedMiddlewareStack();
        }
        /** @var callable $start */
        $start = $this->stack->top();
        $this->middlewareLock = true;


        $result = call_user_func_array($start, array_merge([$value], $additional)); // $start($value, $setting);


        $this->middlewareLock = false;
        return $result;
    }


    protected function callableMethod(callable $callable, $next)
    {

        return function ($value, ...$other) use ($callable, $next) {
            $result = call_user_func_array($callable,
                array_merge([$value], $other, [$next]));

            return $result;
        };
    }

    /**
     * @param callable $callable
     * @return MiddlewareTrait
     */
    protected function addMiddleware(callable $callable)
    {

        if ($this->middlewareLock) {
            throw new RuntimeException('Middleware can’t be added once the stack is de queuing');
        }


        if (is_null($this->stack)) {
            $this->seedMiddlewareStack();
        }
        $next = $this->stack->top();

        $this->stack[] = $this->callableMethod($callable, $next);
        return $this;
    }

    /**
     * Seed middleware stack with first callable
     *
     * @param callable $kernel The last item to run as middleware
     *
     * @throws RuntimeException if the stack is seeded more than once
     */
    protected function seedMiddlewareStack(callable $kernel = null)
    {
        if (!is_null($this->stack)) {
            throw new RuntimeException('MiddlewareStack can only be seeded once.');
        }
        if ($kernel === null) {
            $kernel = $this;
        }
        $this->stack = new SplStack;
        $this->stack->setIteratorMode(SplDoublyLinkedList::IT_MODE_LIFO | SplDoublyLinkedList::IT_MODE_KEEP);
        $this->stack[] = $kernel;
    }
}
