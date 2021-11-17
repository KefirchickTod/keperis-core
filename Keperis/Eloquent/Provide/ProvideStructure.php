<?php


namespace Keperis\Eloquent\Provide;


use Keperis\Eloquent\Provide\Event\BuiltEvent;
use Keperis\Eloquent\Provide\Event\ProvideEventInterface;
use Keperis\Eloquent\Provide\ProvideEvents;
use Keperis\Eloquent\Provide\Builder\StructureQueryBuilder;

use Keperis\Eloquent\Provide\Event\RequestEvent;
use Keperis\Http\Request;
use Keperis\Interfaces\EventDispatcher\EventDispatcherInterface;

use Symfony\Component\EventDispatcher\EventDispatcher;

class ProvideStructure
{

    use ProvideEvents;

    protected static $globalEvents = [];
    /**
     * The event dispatcher instance.
     *
     * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    protected static $dispatcher;

    /**
     * @var StructureCollection
     */
    protected $collection;


    /**
     * Check if is checked basic processor or not
     * @var bool
     */
    protected static $booted = false;

    /**
     * @var StructureQueryBuilder
     */
    protected $builder;

    /**
     * @param string $event
     * @param string $className
     */
    public static function registerGlobalEvents(string $event, string $className)
    {
        static::$globalEvents = array_merge([$event, $className], static::$globalEvents);
    }

    public function __construct(StructureCollection $collection)
    {
        $this->collection = $collection;
        static::bootIfNot();

        $this->dispatchesEvents = static::$globalEvents;
    }

    public function bindRequest(Request $request)
    {
        $event = $this->fireProvideEvent('request');
        if ($event instanceof ProvideEventInterface) {
            $this->collection = $event->getCollection();
        }
    }

    /**
     * Register globals event params
     */
    protected static function bootIfNot()
    {
        if (static::$booted === false) {
            self::bootDispatcher();

        }
    }

    private static function bootDispatcher()
    {
        static::$dispatcher = new EventDispatcher();
    }

    /**
     * Set and trigger event {set.request}
     * @param Request $request
     */
    public function fireRequest(Request $request)
    {


        $this->fireProvideEvent('set.request');
    }

    public function build()
    {

        if ($c = $this->fireProvideEvent('beforeBuild')) {
            $this->collection = $c->getCollection();
        }

        $builder = new StructureQueryBuilder($this->collection);


        $this->builder = $builder;


        /** @var ProvideEventInterface $event */
        $this->fireProvideEvent('built');


        $q = $builder->toSql();

        return $q;
    }


}
