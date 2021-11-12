<?php

namespace Keperis\Page\FilterData;

use PHPUnit\Framework\TestCase;
use Keperis\Page\DataTransformation;
use Keperis\Structure\ProvideStructures;

class bcTest extends ProvideStructures
{
    protected $sqlSetting = [
        'table' => 'none',
        'fullName' => [
            'select' => 'false',
            'type' => 'string',
        ],
    ];
}

class SortTest extends TestCase
{

    public function test__invoke()
    {
        $structure = [
            'get' => [
                'fullName'
            ],
            'class' => bcTest::class,
        ];

        $transformation = new DataTransformation($structure);
        $transformation->addFilter(Sort::class);
        $data  = $transformation->callFilter([
            'sort' => 'fullName'
        ]);

        $structure['setting']['order'] = 'fullName';

        $this->assertEquals($data, $structure);
    }
}
