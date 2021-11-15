<?php

namespace Keperis\Http;
use InvalidArgumentException;
use Keperis\Interfaces\StreamInterface;
use Keperis\Interfaces\UploadedFileInterface;
use Keperis\Interfaces\UriInterface;

class ServerRequest extends Request
{

    /**
     * @var array
     */
    protected $attributes = [];

    /**
     * @var array
     */
    private $cookieParams = [];

    /**
     * @var null|array|object
     */
    private $parsedBody;

    /**
     * @var array
     */
    private $queryParams = [];

    /**
     * @var array
     */
    private $serverParams;

    /**
     * @var array
     */
    private $uploadedFiles;


    /**
     * @param array $serverParams Server parameters, typically from $_SERVER
     * @param array $uploadedFiles Upload file information, a tree of UploadedFiles
     * @param UriInterface $uri URI for the request, if any.
     * @param null|string $method HTTP method for the request, if any.
     * @param string|resource|StreamInterface $body Message body, if any.
     * @param Headers $headers Headers for the message, if any.
     * @param array $cookies Cookies for the message, if any.
     * @param array $queryParams Query params for the message, if any.
     * @param null|array|object $parsedBody The deserialized body parameters, if any.
     * @param string $protocol HTTP protocol version.
     * @throws InvalidArgumentException for any invalid value.
     */
    public function __construct(
        array $serverParams = [],
        array $uploadedFiles = [],
        $uri = null,
        string $method = null,
        $body = 'php://input',
        $headers = [],
        $cookies = [],
        $queryParams = [],
        $parsedBody = null,
        string $protocol = '1.1'
    ) {

        $this->validateUploadedFiles($uploadedFiles);

        if ($body === 'php://input') {
            $body = Body::getAsRequestBody();
        }
        parent::__construct($method, $uri, $headers, $cookies,$serverParams, $body, $uploadedFiles);
        $this->serverParams  = $serverParams;
        $this->uploadedFiles = $uploadedFiles;
        $this->cookieParams  = $cookies;
        $this->queryParams   = $queryParams;
        $this->parsedBody    = $parsedBody;
        $this->protocol      = $protocol;
    }

    /**
     * {@inheritdoc}
     */
    public function getServerParams() : array
    {
        return $this->serverParams;
    }

    /**
     * {@inheritdoc}
     */
    public function getUploadedFiles() : array
    {
        return $this->uploadedFiles;
    }

    /**
     * {@inheritdoc}
     */
    public function withUploadedFiles(array $uploadedFiles) : ServerRequest
    {
        $this->validateUploadedFiles($uploadedFiles);
        $new = clone $this;
        $new->uploadedFiles = $uploadedFiles;
        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function getCookieParams() : array
    {
        return $this->cookieParams;
    }

    /**
     * {@inheritdoc}
     */
    public function withCookieParams(array $cookies) : ServerRequest
    {
        $new = clone $this;
        $new->cookieParams = $cookies;
        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function getQueryParams() : array
    {
        return $this->queryParams;
    }

    /**
     * {@inheritdoc}
     */
    public function withQueryParams(array $query) : ServerRequest
    {
        $new = clone $this;
        $new->queryParams = $query;
        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function getParsedBody()
    {
        return $this->parsedBody;
    }

    /**
     * {@inheritdoc}
     */
    public function withParsedBody($data) : ServerRequest
    {
        if (! is_array($data) && ! is_object($data) && null !== $data) {
            throw new InvalidArgumentException(sprintf(
                '%s expects a null, array, or object argument; received %s',
                __METHOD__,
                gettype($data)
            ));
        }

        $new = clone $this;
        $new->parsedBody = $data;
        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributes() : array
    {
        return $this->attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttribute($attribute, $default = null)
    {
        if (! array_key_exists($attribute, $this->attributes)) {
            return $default;
        }

        return $this->attributes[$attribute];
    }

    /**
     * {@inheritdoc}
     */
    public function withAttribute($attribute, $value) : ServerRequest
    {
        $new = clone $this;
        $new->attributes[$attribute] = $value;
        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function withoutAttribute($attribute) : ServerRequest
    {
        $new = clone $this;
        unset($new->attributes[$attribute]);
        return $new;
    }

    /**
     * Recursively validate the structure in an uploaded files array.
     *
     * @throws InvalidArgumentException if any leaf is not an UploadedFileInterface instance.
     */
    private function validateUploadedFiles(array $uploadedFiles) : void
    {
        foreach ($uploadedFiles as $file) {
            if (is_array($file)) {
                $this->validateUploadedFiles($file);
                continue;
            }

            if (! $file instanceof UploadedFileInterface) {
                throw new InvalidArgumentException('Invalid leaf in uploaded files structure');
            }
        }
    }
}