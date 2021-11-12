<?php


namespace src\Middleware;


use src\Interfaces\Handler\RequestHandlerInterface;
use src\Interfaces\ResponseInterface;

class RequestHandler implements RequestHandlerInterface
{

    /**
     * @var RequestHandlerInterface
     */
    private $nextHandle;

    /**
     * @inheritDoc
     */
    public function handle($request, $response, RequestHandlerInterface $requestHandler = null): ?ResponseInterface
    {

        if ($this->nextHandle) {
            return $this->nextHandle->handle($request, $response, $requestHandler);
        }


        return $response;
    }

    public function setNext(RequestHandlerInterface $handler): RequestHandlerInterface
    {
        $this->nextHandle = $handler;
        return $handler;
    }
}