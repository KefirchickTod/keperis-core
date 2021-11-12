<?php


namespace src\Page;


use src\EventDispatcher\Concerns\DelegatesToDisptacher;
use src\Interfaces\ProvideMask;

abstract class Component
{

    use DelegatesToDisptacher;

    public abstract static function createByMask(ProvideMask $mask, string $key);

}
