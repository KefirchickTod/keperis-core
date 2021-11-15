<?php


namespace Keperis\Http;


use Closure;
use Error;
use InvalidArgumentException;
use RuntimeException;
use Keperis\Collection;
use Keperis\Interfaces\HeaderInterface;
use Keperis\Interfaces\SessionInterface;
use Keperis\Interfaces\UriInterface;

class Request extends Message
{
    protected $validMethods = [
        'CONNECT' => 1,
        'DELETE'  => 1,
        'GET'     => 1,
        'HEAD'    => 1,
        'OPTIONS' => 1,
        'PATCH'   => 1,
        'POST'    => 1,
        'PUT'     => 1,
        'TRACE'   => 1,
    ];

    private $queryParrams;
    /**
     * @var array
     */
    private $serverParams;
    /**
     * @var array
     */
    private $cookies;
    /**
     * @var string
     */
    private $originalMethod;
    /**
     * @var array
     */
    private $uploadedFiles;
    private $method;
    private $bodyParsed;
    private $bodyParsers;

    /**
     * @var SessionInterface
     */
    private $session;
    private $routeName;

    public function __construct(
        $method,
        Uri $uri,
        HeaderInterface $headers,
        array $cookies,
        array $serverParams,
        Body $body,
        array $uploadedFiles = []
    ) {

        $this->originalMethod = $this->filterMethod($method);
        $this->uri = $uri;
        $this->headers = $headers;
        $this->cookies = $cookies;
        $this->serverParams = $serverParams;
        $this->attributes = new Collection();
        $this->body = $body;
        $this->uploadedFiles = $uploadedFiles;


        $this->registerMediaTypeParser('application/json', function ($input) {
            return json_decode($input, true);
        });

        $this->registerMediaTypeParser('application/xml', function ($input) {
            $backup = libxml_disable_entity_loader(true);
            $result = simplexml_load_string($input);
            libxml_disable_entity_loader($backup);
            return $result;
        });

        $this->registerMediaTypeParser('text/xml', function ($input) {
            $backup = libxml_disable_entity_loader(true);
            $result = simplexml_load_string($input);
            libxml_disable_entity_loader($backup);
            return $result;
        });

        $this->registerMediaTypeParser('application/x-www-form-urlencoded', function ($input) {
            parse_str($input, $data);
            return $data;
        });


        if (!$this->headers->has('Host') || $this->uri->getHost() !== '') {
            $this->headers->set('Host', $this->uri->getHost());
        }
    }


    public function getUploadedFiles()
    {
        return $this->uploadedFiles;
    }

    public function parseBody()
    {

        if ($this->bodyParsed) {
            return $this->bodyParsed;
        }

        $content = $this->body->getContents();
        $parse = [];
        if ($content || $content !== '') {
            parse_str($content, $parse);
        }
        return $parse;
    }

    public function withUploadedFiles(array $uploaderFiles)
    {
        $clone = clone $this;

        $clone->uploadedFiles = $uploaderFiles;

        return $this;
    }

    public function isGet()
    {
        return $this->isMethod('GET');
    }

    public function isPost()
    {
        return $this->isMethod('POST');
    }

    public function isXhr()
    {
        return $this->getHeaderLine('X-Requested-With') === 'XMLHttpRequest';
    }

    public function isMethod($method)
    {
        return strtolower($this->getMethod()) === strtolower($method);
    }

    public function registerMediaTypeParser($mediaType, callable $callable)
    {
        if ($callable instanceof Closure) {
            $callable = $callable->bindTo($this);
        }
        $this->bodyParsers[(string)$mediaType] = $callable;
    }

    protected function filterMethod($method)
    {
        if ($method === null) {
            return $method;
        }

        if (!is_string($method)) {
            throw new Error(sprintf(
                'Unsupported HTTP method; must be a string, received %s',
                (is_object($method) ? get_class($method) : gettype($method))
            ));
        }

        $method = strtoupper($method);
        if (!isset($this->validMethods[$method])) {
            throw new Error(sprintf(
                'Unsupported HTTP method "%s" provided',
                $method
            ));
        }

        return $method;
    }

    public function withParsedBody($data)
    {
        if (!is_null($data) && !is_object($data) && !is_array($data)) {
            throw new InvalidArgumentException('Parsed body value must be an array, an object, or null');
        }

        $clone = clone $this;
        $clone->bodyParsed = $data;

        return $clone;
    }

    public static function creatFromServerData(ServerData $serverData)
    {


        $method = $serverData->get('REQUEST_METHOD', "GET");
        $uri = Uri::creat($serverData);
        $header = Headers::creatFromServerData($serverData);

        $cookies = Cookies::parseHeader($header->get('Cookies', []));
        $body = Body::getAsRequestBody();
        $uploadFiles = UploadedFile::creatFromServer($serverData);
        $request = new static($method, $uri, $header, $cookies, $serverData->all(), $body, $uploadFiles);
        if ($method === 'POST' &&
            in_array($request->getMediaType(), ['application/x-www-form-urlencoded', 'multipart/form-data'])
        ) {
            // parsed body must be $_POST
            $request = $request->withParsedBody($_POST);
        }
        return $request;
    }

    public function getUri()
    {
        return $this->uri;
    }

    public function getMethod()
    {
        if ($this->method === null) {
            $this->method = $this->originalMethod;
            $customMethod = $this->getHeaderLine('X-Http-Method-Override');

            if ($customMethod) {
                $this->method = $this->filterMethod($customMethod);
            } elseif ($this->originalMethod === 'POST') {
                $body = $this->getParsedBody();

                if (is_object($body) && property_exists($body, '_METHOD')) {
                    $this->method = $this->filterMethod((string)$body->_METHOD);
                } elseif (is_array($body) && isset($body['_METHOD'])) {
                    $this->method = $this->filterMethod((string)$body['_METHOD']);
                }

                if ($this->getBody()->eof()) {
                    $this->getBody()->rewind();
                }
            }
        }

        return $this->method;
    }

    public function getParsedBody()
    {
        if ($this->bodyParsed) {
            return $this->bodyParsed;
        }

        if (!$this->body) {
            return null;
        }

        $mediaType = $this->getMediaType();
        $body = (string)$this->getBody();

        if (isset($this->bodyParsers[$mediaType]) === true) {
            $parsed = $this->bodyParsers[$mediaType]($body);

            if (!is_null($parsed) && !is_object($parsed) && !is_array($parsed)) {
                throw new RuntimeException('Request body media type parser return value must be an array, an object, or null');
            }
            $this->bodyParsed = $parsed;
        }

        return $this->bodyParsed;
    }

    public function getMediaType()
    {
        $contentType = $this->getContentType();
        if ($contentType) {
            $contentTypeParts = preg_split('/\s*[;,]\s*/', $contentType);

            return strtolower($contentTypeParts[0]);
        }

        return null;
    }

    public function getContentType()
    {
        $result = $this->headers->get('content-type');

        return $result ? $result[0] : null;
    }

    public function __clone()
    {
        $this->headers = clone $this->headers;
        $this->attributes = clone $this->attributes;
        $this->body = clone $this->body;
    }

    public function getAttributes()
    {
        return $this->attributes->all();
    }

    public function getArguments(){
        return $this->attributes->get('routeInfo');
    }
    public function getArgument($key, $default = null){
        if($this->attributes->get('routeInfo'))
        {
            return $this->attributes->get('routeInfo')[2][$key] ?? $default;
        }
        return $default;
    }

    public function getAttribute($key, $default = null)
    {
        return $this->attributes->get($key, $default);
    }

    public function withAttribute($name, $value)
    {
        $clone = clone $this;
        $clone->attributes->set($name, $value);
        return $clone;
    }

    public function withAttributes(array $attributes)
    {
        $clone = clone $this;
        $clone->attributes = new Collection($attributes);
        return $clone;
    }

    public function withoutAttributes($name)
    {
        $clone = clone $this;
        $clone->attributes->remove($name);
        return $name;
    }

    public function withRouteName(string $routeName){
        $clone = clone $this;
        $clone->routeName = $routeName;
        return $clone;
    }
    public function getRouteName(){
        return $this->routeName;
    }

    public function withUri(UriInterface $uri){
        $clone = clone $this;
        $clone->uri = $uri;

        return $clone;
    }


}
