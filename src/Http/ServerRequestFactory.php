<?php


namespace src\Http;


class ServerRequestFactory
{
    /**
     * Function to use to get apache request headers; present only to simplify mocking.
     *
     * @var callable
     */
    private static $apacheRequestHeaders = 'apache_request_headers';

    /**
     * Create a request from the supplied superglobal values.
     *
     * If any argument is not supplied, the corresponding superglobal value will
     * be used.
     *
     * The ServerRequest created is then passed to the fromServer() method in
     * order to marshal the request URI and headers.
     *
     * @param array $server $_SERVER superglobal
     * @param array $query $_GET superglobal
     * @param array $body $_POST superglobal
     * @param array $cookies $_COOKIE superglobal
     * @param array $files $_FILES superglobal
     * @return ServerRequest
     * @see fromServer()
     */
    public static function fromGlobals(
        array $server = null,
        array $query = null,
        array $body = null,
        array $cookies = null,
        array $files = null
    ): ServerRequest {
        $server = $server ?: container()->get('serverdata');
        $files = $files ?: $_FILES;
        $headers = new Headers(["Content-Type" => 'text/html; charset=UTF-8']);

        if (null === $cookies && $headers->has('cookie') ) {
            $cookies = $headers->get('cookie');
        }

        /**
         * @var $server ServerData
         */

      //  debug($server->toArray());
        return new ServerRequest(
            ((array)$server->toArray()),
            $files,
            Uri::creat($server),
            $server->get('REQUEST_METHOD'),
            'php://input',
            $headers,
            $cookies ?: $_COOKIE,
            $query ?: $_GET,
            $_POST,
            $server->get('SERVER_PROTOCOL')
        );
    }

    /**
     * {@inheritDoc}
     */
    public function createServerRequest(string $method, $uri, array $serverParams = [])
    {
        $uploadedFiles = [];

        return new ServerRequest(
            $serverParams,
            $uploadedFiles,
            $uri,
            $method,
            'php://temp'
        );
    }
}