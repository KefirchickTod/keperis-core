<?php

namespace Keperis\Eloquent\Provide\Event;

use Keperis\Eloquent\Provide\ProvideStructure;
use Keperis\Eloquent\Provide\StructureCollection;

interface ProvideEventInterface
{

    /**
     * @param StructureCollection $collection
     * @return mixed
     */
    public function __construct(StructureCollection $collection);

    /**
     * @return StructureCollection
     */
    public function getCollection(): StructureCollection;
}