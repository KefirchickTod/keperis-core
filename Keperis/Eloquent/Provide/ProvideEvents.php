<?php

namespace Keperis\Eloquent\Provide;

use Illuminate\Support\Arr;
use Keperis\Eloquent\Provide\Event\ProvideEventInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @property EventDispatcherInterface $dispatcher
 */
trait ProvideEvents
{

    /**
     * The event map for the model.
     *
     * Allows for object-based events for native Eloquent events.
     *
     * @var array
     */
    protected $dispatchesEvents = [];





    /**
     * Register a model event with the dispatcher.
     *
     * @param string $event
     * @param \Closure|ProvideEventInterface $callback
     * @return void
     */
    protected static function registerProvideEvent($event, $callback)
    {
        if (isset(static::$dispatcher)) {
            $name = static::class;

            static::$dispatcher->addListener("provide.{$event}: {$name}", $callback);
        }
    }

    /**
     * Filter the model event results.
     *
     * @param mixed $result
     * @return mixed
     */
    protected function filterModelEventResults($result)
    {
        if (is_array($result)) {
            $result = array_filter($result, function ($response) {
                return !is_null($response);
            });
        }

        return $result;
    }

    /**
     * Fire a custom model event for the given event.
     *
     * @param string $event
     * @return mixed|void
     */
    protected function fireCustomModelEvent(string $event)
    {
        if (!isset($this->dispatchesEvents[$event])) {
            return;
        }

        $result = static::$dispatcher->dispatch(new $this->dispatchesEvents[$event]($this->collection), $event);

        var_dump($result);exit;

        if (!is_null($result)) {
            return $result;
        }
    }

    /**
     * Fire the given event for the model.
     *
     * @param string $event
     * @return mixed
     */
    protected function fireProvideEvent(string $event)
    {
        if (!isset(static::$dispatcher)) {
            return true;
        }


        $result = $this->filterModelEventResults(
            $this->fireCustomModelEvent($event)
        );


        if ($result === false) {
            return false;
        }

        return !empty($result) ? $result : static::$dispatcher->dispatch(
            $this, "provide.{$event}: " . static::class,
        );
    }



}