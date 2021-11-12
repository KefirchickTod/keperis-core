<?php


namespace src\Eloquent\Provide\Processes;


use src\Eloquent\Provide\StructureCollection;

interface ProcessorInterface
{


    public function process(StructureCollection $collection);
}