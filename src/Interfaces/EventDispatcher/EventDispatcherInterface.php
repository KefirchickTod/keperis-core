<?php
declare(strict_types=1);

namespace src\Interfaces\EventDispatcher;

/**
 * Defines a dispatcher for events.
 * @link https://github.com/php-fig/event-dispatcher/blob/master/src/EventDispatcherInterface.php
 */
interface EventDispatcherInterface
{

    /**
     * Provide all relevant listeners with an event to process.
     *
     * @param object $event
     *   The object to process.
     *
     * @return object
     *   The Event that was passed, now modified by listeners.
     */
    public function dispatch(object $event) : object ;
}