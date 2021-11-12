<?php


namespace Keperis\Interfaces;


interface  HeaderInterface extends CollectionInterface
{

    public function add($key, $value);

    public function normalizeKey($key);
}