<?php


namespace Keperis\Page;


use Keperis\EventDispatcher\Concerns\DelegatesToDisptacher;
use Keperis\Interfaces\ProvideMask;

abstract class Component
{

    use DelegatesToDisptacher;

    public abstract static function createByMask(ProvideMask $mask, string $key);

}
