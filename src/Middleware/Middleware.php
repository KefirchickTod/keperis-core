<?php


namespace Keperis\Middleware;


use Psr\Http\Message\RequestInterface;
use Keperis\Http\Request;
use Keperis\Http\Response;
use Keperis\Interfaces\Handler\MiddlewareInterface;
use Keperis\Interfaces\Handler\RequestHandlerInterface;

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