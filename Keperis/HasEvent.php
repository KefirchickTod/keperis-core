<?php

namespace Keperis;

use Keperis\Eloquent\Provide\Event\ProvideEventInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @property EventDispatcherInterface $dispatcher
 */
trait HasEvent
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
     * @param \Closure|mixed $callback
     * @return void
     */
    protected static function registerProvideEvent($event, $callback)
    {
        if (!$callback instanceof \Closure && (isset(self::$callableInterface) && !$callback instanceof self::$callableInterface)) {
            throw new \RuntimeException(sprintf("Cant register callback [%s] for invalid interface ",
                get_class($callback)));
        }

        if (isset(static::$dispatcher)) {
            $name = static::class;

            if (!isset(static::$dispatchesKey)) {
                throw new \InvalidArgumentException(sprintf("Invalid key for register event in: [%s]", static::class));
            }

            $key = static::$dispatchesKey;

            static::$dispatcher->addListener("{$key}.{$event}: {$name}", $callback);
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

    protected function issetDispatchesEvents(string $event)
    {
        return isset($this->dispatchesEvents[$event]);
    }

    /**
     * Fire a custom model event for the given event.
     *
     * @param string $event
     * @return mixed|void
     */
    protected function fireCustomModelEvent(string $event)
    {
        if (!$this->issetDispatchesEvents($event)) {
            return;
        }

        $result = static::$dispatcher->dispatch(new $this->dispatchesEvents[$event]($this->collection), $event);


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

        $key = static::$dispatchesKey;

        return !empty($result) ? $result : static::$dispatcher->dispatch(
            $this, "{$key}.{$event}: " . static::class,
        );
    }


}
