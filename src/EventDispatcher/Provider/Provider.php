<?php


namespace src\EventDispatcher\Provider;


use src\Interfaces\EventDispatcher\ListenerProviderInterface;

class Provider implements ListenerProviderInterface
{

    /**
     * @var ListenerCollection
     */
    private $collection;

    public function __construct(ListenerCollection $listenerCollection)
    {
        $this->collection = $listenerCollection;
    }

    public function getListenersForEvent(object $event): iterable
    {
        yield from  $this->collection->getForEvent(get_class($event));
    }
}