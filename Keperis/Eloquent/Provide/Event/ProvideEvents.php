<?php

namespace Keperis\Eloquent\Provide\Event;

use Keperis\Eloquent\Provide\Commands\ProvideReceiver;
use Keperis\Eloquent\Provide\Commands\ProvideReceiverInterface;
use Keperis\Eloquent\Provide\StructureCollection;

abstract class ProvideEvents implements ProvideEventInterface
{
    /**
     * @var StructureCollection
     */
    protected $collection;

    /**
     * @var ProvideReceiverInterface
     */
    protected $change;

    /**
     * @param StructureCollection $collection
     */
    public function __construct(StructureCollection $collection)
    {
        $this->collection = $collection;
        $this->change = $this->changer();
    }

    /**
     * Easy factory for crate changer
     * @return ProvideReceiver
     */
    protected function changer()
    {
        return new ProvideReceiver($this->collection);
    }

    /**
     * Butting events action with changer
     * @return void
     */
    abstract public function event();

    /**
     * Call after dispatch for inversion collection
     * @return StructureCollection
     */
    public function getCollection(): StructureCollection
    {
        $this->event();

        if ($this->change->isChange()) {
            return $this->change->getStructure();
        }

        return $this->collection;
    }
}