<?php


namespace src\Middleware;


use Psr\Http\Message\RequestInterface;
use src\Http\Request;
use src\Http\Response;
use src\Interfaces\Handler\MiddlewareInterface;
use src\Interfaces\Handler\RequestHandlerInterface;

class Middleware implements MiddlewareInterface
{

    /**
     * @var RequestHandlerInterface
     */
    private $fallbackHandler;

    public function __construct(RequestHandlerInterface $fallbackHandler)
    {
        $this->fallbackHandler = $fallbackHandler;
    }

    /**
     * @inheritDoc
     */
    public function process(Request $request, Response $response, RequestHandlerInterface $handler)
    {
        $response = $handler->handle($request, $response, $handler);



        return $response;
    }
}