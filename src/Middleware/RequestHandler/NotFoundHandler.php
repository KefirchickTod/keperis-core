<?php


namespace Keperis\Middleware\RequestHandler;


use Keperis\Interfaces\Handler\RequestHandlerInterface;
use Keperis\Interfaces\ResponseInterface;
use Keperis\Middleware\RequestHandler;

class NotFoundHandler extends RequestHandler
{


    public function handle($request, $response, RequestHandlerInterface $requestHandler = null): ?ResponseInterface
    {
        return $response;
    }
}