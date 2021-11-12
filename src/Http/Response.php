<?php


namespace src\Http;


use RuntimeException;
use src\Interfaces\HeaderInterface;
use src\Interfaces\ResponseInterface;
use src\Interfaces\StreamInterface;
use src\View\View;


class Response extends Message implements ResponseInterface
{


    protected static $messages = [
        //Informational 1xx
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        //Successful 2xx
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        226 => 'IM Used',
        //Redirection 3xx
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => '(Unused)',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        //Client Error 4xx
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        //Server Error 5xx
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
    ];
    protected $status = 200;
    protected $reasonPhrase = '';
    protected $resource;

    protected $notice = [];
    /**
     * @var StreamInterface|false|resource
     */
    protected $body;
    protected $streamDownloadCallback;


    public function __construct(int $status, HeaderInterface $header, StreamInterface $stream = null)
    {
        $this->status = $this->filterStatus($status);
        $this->headers = $header;
        $this->body = $stream ? $stream : new Body(fopen('php://temp', 'r+'));
    }

    /**
     * @param $status
     * @return mixed
     */
    protected function filterStatus($status)
    {
        if (!is_integer($status) || $status < 100 || $status > 599) {
            throw new RuntimeException('Invalid HTTP status code');
        }

        return $status;
    }

    public function session()
    {
        return session();
    }


    /**
     * @param array $data
     * @param null $status
     * @return false|int
     */

    public function withJson(array $data, $status = null)
    {
        if ($this->getHeaders()) {
            return $this->withStatus(200)->withAddedHeader('Content-Type',
                'application/json')->body->write(json_encode($data));
        }

        return $this->withStatus(200)->withHeader('Content-Type', 'application/json')->body->write(json_encode($data));
    }

    /**
     * @inheritDoc
     */
    public function withStatus($code, $reasonPhrase = '')
    {

        $clone = clone $this;
        $clone->status = $code;
        $clone->reasonPhrase = $reasonPhrase;
        return $clone;

    }

    public function back()
    {
        return $this->withRedirect($_SERVER['HTTP_REFERER'] ?? '/');
    }


    public function getStreamDownload()
    {
        return $this->streamDownloadCallback;
    }

    public function isStreamDownload()
    {

        if (!is_null($this->streamDownloadCallback)) {
            return true;
        }
        return false;
    }

    public function withStreamDownload(callable $callback)
    {
        $clone = clone $this;
        $clone->streamDownloadCallback = $callback;
        return $clone;
    }


    public function write($context)
    {

        if ($this->body->isWritable()) {
            $this->body->write($context);
        }
        return $this;
    }

    public function withMassage(string $msg, $error = false, $link = false)
    {

        $actual_link = $link === false ? (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] : $link;
        $msg = $error == true ? "?errorMassage=" . $msg : "?massage=" . $msg;
        return $this->withRedirect($actual_link . $msg);
    }

    public function reload()
    {
        return $this->withRedirect((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    }

    public function render()
    {
        return $this->resource->render();
    }

    public function withRedirect($url, $status = 301)
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            $url = str_replace('//', '/', $url);

        }

        return $this->withStatus($status)->withHeader('Location', (string )$url);
    }

    public function isRedirect()
    {
        return in_array($this->getStatusCode(), [301, 302, 303, 307]);
    }

    /**
     * @inheritDoc
     */
    public function getStatusCode()
    {
        return $this->status;
    }

    public function withResource(View $resource)
    {
        $clone = clone $this;
        $clone->resource = $resource;
        return $clone;
    }


    public function isResource(): bool
    {
        return $this->resource && $this->resource instanceof View;
    }

    public function isRedirection()
    {
        return $this->getStatusCode() >= 300 && $this->getStatusCode() < 400;
    }

    public function __toString()
    {
        $output = sprintf(
            'HTTP/%s %s %s',
            $this->getProtocolVersion(),
            $this->getStatusCode(),
            $this->getReasonPhrase()
        );
        $output .= PHP_EOL;
        foreach ($this->getHeaders() as $name => $values) {
            $output .= sprintf('%s: %s', $name, $this->getHeaderLine($name)) . PHP_EOL;
        }
        $output .= PHP_EOL;
        $output .= (string)$this->getBody();

        return $output;
    }

    /**
     * @inheritDoc
     */
    public function getReasonPhrase()
    {
        if (isset($this->reasonPhrase) && $this->reasonPhrase) {
            return $this->reasonPhrase;
        }

        if (isset($this->status) && isset(Response::$messages[$this->status])) {
            return Response::$messages[$this->status];
        }

        return '';
    }


    public function massage(string $massage, string $key)
    {
        $this->notice[$key] = $massage;
        return $this;
    }

    public function getMassage(string $key)
    {
        return $this->notice[$key] ?? '';
    }
}
