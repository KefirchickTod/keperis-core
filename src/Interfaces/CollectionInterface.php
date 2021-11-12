<?php


namespace Keperis\Interfaces;


use ArrayIterator;

interface CollectionInterface
{

    public function clear();

    public function copy(): CollectionInterface;

    public function has($key);

    public function toArray(): ArrayIterator;

    public function set($key, $value);

    public function get($key, $default = []);


}
