<?php

namespace src;

use PHPUnit\Framework\TestCase;

class EmulatorTrait
{
    use MiddlewareTrait;

    public function add(callable $callable)
    {
        return $this->addMiddleware($callable);
    }

    /**
     * @return \SplStack
     */
    public function getStack(): \SplStack
    {
        return $this->stack;
    }

    public function lock(): bool
    {
        return $this->middlewareLock;
    }
}

class MiddlewareTraitTest extends TestCase
{

    public function testCallMiddlewareStack()
    {
        $middleware = new EmulatorTrait();

        $middleware->add(function ($value, $next) {
            return true;
        });

        $this->assertFalse($middleware->lock());

        $middleware->add(function ($value, $settings, $next) {
            $this->assertSame(23, $value);
            $this->assertEquals('add', $settings);
            $this->assertIsCallable($next);
            return true;
        });


        $result = $middleware->callMiddlewareStack(23, 'add');

        $this->assertTrue($result);

        $middleware->add(function ($val, $next) {
            $this->assertIsCallable($next);
            return true;
        });

        $middleware->callMiddlewareStack('test');

    }
}
