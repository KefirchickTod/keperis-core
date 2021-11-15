<?php

namespace Keperis\Eloquent\Provide\Commands;

use Keperis\Eloquent\Provide\Processes\ProvideStructureProcessor;
use Keperis\Http\Request;
use Keperis\Interfaces\Command\CommandInterface;
use Keperis\Structure\ProvideStructures;

abstract class UriRequestCommand extends ProvideStructureProcessor implements CommandInterface
{

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var ProvideReceiverInterface
     */
    protected $receiver;

    protected $uriQuery = null;

    private $types = null;

    public function __construct(Request $request, ProvideReceiverInterface $receiver)
    {
        $this->request = $request;
        $this->receiver = $receiver;

        $this->getQuery();
    }

    /**
     * @return ProvideStructures
     */
    public function strucutreController()
    {
        return $this->receiver->getStructure()->getController();
    }

    public function getType(string $key)
    {
        if (!is_null($this->types)) {
            return $this->types[$key] ?? null;
        }

        $this->types = $this->strucutreController()->getAllWithType();

        if (!$this->types) {
            throw new \RuntimeException("Undefined types");
        }

        return $this->types[$key] ?? null;
    }

    /**
     * Check if exists key in uri arguments
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->uriQuery);
    }

    /**
     * Get from uriQuery value or default value
     * @param string $key
     * @param null $default
     * @return string
     */
    public function get(string $key, $default = null): string
    {
        if (!$this->has($key)) {
            return $default;
        }
        return $this->uriQuery[$key];
    }


    /**
     * Parse uri and save value to property
     * @return array
     */
    public function getQuery(): array
    {
        if (is_null($this->uriQuery)) {
            $this->uriQuery = $this->request->getUri()->getParseQuery();
        }
        return $this->uriQuery;
    }

}
