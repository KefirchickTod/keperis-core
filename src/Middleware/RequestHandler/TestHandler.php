<?php


namespace src\Middleware\RequestHandler;


use src\Interfaces\Handler\RequestHandlerInterface;
use src\Interfaces\ResponseInterface;
use src\Middleware\RequestHandler;

class TestHandler extends RequestHandler
{

    public function handle($request, $response ,RequestHandlerInterface $requestHandler = null): ?ResponseInterface
    {
        return  $response;
    }
}
