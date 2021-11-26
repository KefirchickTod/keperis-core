<?php


namespace Keperis\Page;


use Illuminate\Database\Query\Builder;
use Keperis\EventDispatcher\Concerns\DelegatesToDisptacher;
use Keperis\Interfaces\ProvideMask;

interface Component
{


    /**
     * @return string
     */
    public function render() : string;
}
