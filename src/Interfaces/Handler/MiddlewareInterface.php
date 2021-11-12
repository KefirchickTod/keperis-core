<?php


namespace Keperis\Interfaces\Handler;

use Keperis\Http\Request;
use Keperis\Http\Response;

/**
 * Participant in processing a server request and response.
 *
 * An HTTP middleware component participates in processing an HTTP message:
 * by acting on the request, generating the response, or forwarding the
 * request to a subsequent middleware and possibly acting on its response.
 */

/**
 * Interface MiddlewareInterface
 * @package App\Keperis\Interfaces
 * @property \Keperis\Http\Response $response
 */
interface MiddlewareInterface
{
    /**
     * Process an incoming server request.
     *
     * Processes an incoming server request in order to produce a response.
     * If unable to produce the response itself, it may delegate to the provided
     * request handler to do so.
     */
    public function process(Request $request, Response  $response, RequestHandlerInterface $handler);
}