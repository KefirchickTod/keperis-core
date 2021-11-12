<?php


namespace src\Eloquent\Provide;


use src\Eloquent\Provide\Processes\ProcessorInterface;
use src\EventDispatcher\Dispatcher\Dispatcher;
use src\EventDispatcher\Provider\ListenerCollection;
use src\Interfaces\EventDispatcher\EventDispatcherInterface;
use src\Interfaces\EventDispatcher\ListenerProviderInterface;

class ProvideStructure implements ListenerProviderInterface
{
    /**
     * @var ListenerCollection
     */
    protected $listener;
    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;
    /**
     * Array of events
     * @var array
     */
    private $events = [];

    /**
     * @var ProcessorInterface|null
     */
    private $processor = null;

    /**
     * @var StructureCollection
     */
    private $collection;

    public function __construct(StructureCollection $collection)
    {
        $this->collection = $collection;
        $this->listener = new ListenerCollection();
    }

    public function getCollections()
    {
        return $this->collection;
    }

    /**
     * Clone and set precessor with cleand listener collection
     * @param string $processor
     * @return ProvideStructure
     */
    public function withProcessor(string $processor)
    {

        if (!class_exists($processor)) {
            throw new \TypeError("Type of proccero must by link to class");
        }

        $clone = clone $this;
        $clone->processor = $processor;
        return $clone;
    }

    /**
     * Register event for processor
     * @param $event
     * @return $this
     */
    public function event($event)
    {
        if (!is_callable($event)) {
            throw new \InvalidArgumentException("Event must by callable");
        }

        $this->events[] = $event;

        return $this;
    }

    public function __clone()
    {
        $this->listener = new \src\EventDispatcher\Provider\ListenerCollection();
    }


    private function attachDefaultEvent()
    {
        $provider = clone $this;

        $this->event(function (ProcessorInterface $event) use ($provider) {
            $event->process($provider->collection);
        });
    }

    /**
     * @return Dispatcher
     */
    public function getDispatcher()
    {

        if (!$this->processor) {
            throw new \RuntimeException("Undefined processor");
        }

        $this->attachDefaultEvent();

        foreach ($this->events as $event) {

            $this->listener = $this->listener->add($event, $this->processor);
        }


        $dispatcher = new Dispatcher($this);

        return $dispatcher;
    }

    /**
     * @inheritDoc
     */
    public function getListenersForEvent(object $event): iterable
    {
        yield from  $this->listener->getForEvent(get_class($event));
    }

    public function execute()
    {

    }
}
