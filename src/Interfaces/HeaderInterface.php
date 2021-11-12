<?php


namespace src\Interfaces;


interface  HeaderInterface extends CollectionInterface
{

    public function add($key, $value);

    public function normalizeKey($key);
}