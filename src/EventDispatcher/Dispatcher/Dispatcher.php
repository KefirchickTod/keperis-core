<?php

namespace Keperis\EventDispatcher\Dispatcher;


use Keperis\Interfaces\EventDispatcher\EventDispatcherInterface;
use Keperis\Interfaces\EventDispatcher\ListenerProviderInterface;
use Keperis\Interfaces\EventDispatcher\StopabledEventIterface;

class Dispatcher implements EventDispatcherInterface
{

    /**
     * @var ListenerProviderInterface
     */
    private $listenerProvider;

    public function __construct(ListenerProviderInterface $listenerProvider)
    {
        $this->listenerProvider = $listenerProvider;
    }

    public function dispatch(object $event): object
    {
        foreach ($this->listenerProvider->getListenersForEvent($event) as $listener) {
            if ($event instanceof StopabledEventIterface && $event->isPropagationStopped()) {
                return $event;
            }

            $listener($event);
        }

        return $event;
    }
}