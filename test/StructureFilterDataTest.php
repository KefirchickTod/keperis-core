<?php

namespace src\Structure\StructureFilters;

use PHPUnit\Framework\TestCase;


class Creator
{
    use StructureFilterData;

    public function callFilter($data)
    {
        return $this->callMiddlewareStack($data);
    }
}

class Sort
{
    public function __invoke($data, $next)
    {
        $data = [
            'get' => []
        ];

        return $data;
    }
}

class StructureFilterDataTest extends TestCase
{

    public function testAddFilter()
    {
        $structure = [];


        $creator = new Creator();

        $creator->addFilter(Sort::class);

        $structure = $creator->callFilter($structure);

        $this->assertEquals([
            'get' => []
        ], $structure);

    }
}
