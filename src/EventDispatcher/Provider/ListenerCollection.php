<?php


namespace src\EventDispatcher\Provider;


use InvalidArgumentException;

final class ListenerCollection
{

    /**
     * @var callable[][]
     */
    private $listener = [];

    public function getForEvents(string ...$className)
    {

        if (!$className) {
            throw new InvalidArgumentException("Function getForEvent cant get empty class names");
        }

        foreach ($className as $name) {
            yield from $this->getForEvent($name);
        }

    }

    public function getForEvent(string $className)
    {

        if (!$this->hasListener($className)) {
            throw new InvalidArgumentException("Invalid class name : $className");
        }
        return $this->listener[$className];
    }

    public function hasListener(string $className): bool
    {
        return array_key_exists($className, $this->listener);
    }


    public function add(callable $listener, string $className): self
    {
        $clone = clone $this;

        $clone->listener[$className][] = $listener;


        return $clone;
    }
}