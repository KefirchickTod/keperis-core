<?php


namespace Keperis\Eloquent\Provide\Processes;


use Keperis\Eloquent\Provide\StructureCollection;

interface ProcessorInterface
{


    public function process(StructureCollection $collection);
}