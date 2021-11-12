<?php

namespace src\Page\FilterData;

use PHPUnit\Framework\TestCase;
use src\Page\DataTransformation;
use src\Structure\ProvideStructures;

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

class FilterTest extends TestCase
{

    public function test__invoke()
    {
        $filtering = [
            'fullName' => 'one'
        ];

        $structure = [
            'get' => [
                'fullName'
            ],
            'class' => bcTest::class,
        ];

        $transformation = new DataTransformation($structure);
        $transformation->addFilter(Filter::class);
        $data = $transformation->callFilter([
            'filter' => json_encode($filtering),
        ]);

       $structure['setting']['where'] = 'false = one';
        $this->assertEquals($data, $structure);
    }
}
