<?php


namespace Keperis\Middleware;


use Keperis\Interfaces\Handler\RequestHandlerInterface;
use Keperis\Interfaces\ResponseInterface;

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