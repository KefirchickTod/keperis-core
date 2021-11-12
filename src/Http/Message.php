<?php


namespace src\Http;


use Error;
use InvalidArgumentException;
use src\Collection;
use src\Interfaces\HeaderInterface;
use src\Interfaces\MessageInterface;
use src\Interfaces\StreamInterface;

abstract class Message implements MessageInterface
{


    public $protocolVersion = 1.1;
    /**
     * @var Uri
     */
    protected $uri;

    /**
     * @var HeaderInterface
     */
    protected $headers;

    /**
     * @var Collection
     */
    protected $attributes;
    /**
     * @var Body
     */
    protected $body;


    public function getBody()
    {
        return $this->body;
    }

    public function getProtocolVersion()
    {
        return $this->protocolVersion;
    }

    /**
     * @param string $version HTTP protocol version
     * @return static
     */
    public function withProtocolVersion($version)
    {
        static $valid = [
            '1.0' => true,
            '1.1' => true,
            '2.0' => true,
        ];
        if (!isset($valid[$version]) || !$valid[$version]) {
            throw new Error("Doesnt found protocol version");
        }

        $clone = clone $this;
        $clone->protocolVersion = $version;
        return $clone;
    }

    /**
     * @param string $name
     * @return string[]|void
     */
    public function getHeader($name)
    {
        $this->headers->get($name);
    }

    /**
     * @return string[][]
     */
    public function getHeaders()
    {
        return $this->headers->all();
    }

    /**
     * @param string $name
     * @return array|bool
     */
    public function hasHeader($name)
    {
        if (!$this->headers->has($name)) {
            return [];
        }
        return $this->headers->has($name);
    }

    /**
     * @param string $name
     * @return string
     */
    public function getHeaderLine($name)
    {
        return implode(',', $this->headers->get($name, []));
    }

    /**
     * @param string $name
     * @param string|string[] $value
     * @return Message|MessageInterface
     */
    public function withHeader($name, $value)
    {
        $clone = clone $this;
        $this->headers->set($name, $value);
        return $clone;
    }

    /**
     * @param string $name
     * @param string|string[] $value
     * @return Message|MessageInterface
     */
    public function withAddedHeader($name, $value)
    {
        $clone = clone $this;
        $clone->headers->add($name, $value);
        return $clone;
    }

    /**
     * @param string $name
     * @return MessageInterface|void
     */
    public function withoutHeader($name)
    {
        $clone = clone $this;
        $clone->headers->remove($name);
    }

    /**
     * @param StreamInterface $body Body.
     * @return static
     * @throws InvalidArgumentException When the body is not valid.
     */
    public function withBody(StreamInterface $body)
    {
        // TODO: Test for invalid body?
        $clone = clone $this;
        $clone->body = $body;

        return $clone;
    }
}