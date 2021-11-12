<?php


namespace src\EventDispatcher\Concerns;


use src\EventDispatcher\Dispatcher\Dispatcher;
use src\EventDispatcher\Provider\ListenerCollection;
use src\EventDispatcher\Provider\Provider;

trait DelegatesToDisptacher
{


    /**
     * @var ListenerCollection
     */
    protected $listener;

    public function addEvent(callable $event, $key = null)
    {
        $this->attachDispatcher();

        if (!$key) {
            $key = get_class($key);
        }
        $this->listener = $this->listener->add($event, $key);
        return $this;
    }

    public function hasEvent($key) : bool
    {
        return $this->listener->hasListener($key);
    }



    private function attachDispatcher()
    {


        if ($this->listener) {
            return true;
        }
        $this->listener = new ListenerCollection();

        return true;
    }

    protected function selfDispatch()
    {
        return $this->dispatch($this);
    }

    public function dispatch(object $event)
    {
        $this->attachDispatcher();

        $provider = new Provider($this->listener);

        $dispatcher = new Dispatcher($provider);

        return $dispatcher->dispatch($event);

    }
}
