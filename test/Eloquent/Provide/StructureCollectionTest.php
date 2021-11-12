<?php

namespace Keperis\Eloquent\Provide;

use PHPUnit\Framework\TestCase;
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

class StructureCollectionTest extends TestCase
{
    private static $structure = [
        'get' => ['fullName'],
        'class' => bcTest::class,
        'setting' => [
            'join' => [
                'join1' => [
                    'get' => ['fullName'],
                    'class' => bcTest::class,
                ]
            ]
        ],
    ];

    public function testGetController()
    {
        $collection = new StructureCollection('testGetController', self::$structure);

        $this->assertInstanceOf(bcTest::class, $collection->getController());
    }

    public function testGetControllers()
    {
        $collection = new StructureCollection('testGetController', self::$structure);

        $controllers = $collection->getControllers();


        $this->assertIsArray($controllers);

        $this->assertEquals([new bcTest], $controllers);
    }

    public function testGetSetting()
    {

    }

    public function testSetSetting()
    {

    }

    public function testHasSetting()
    {

    }

    public function testGetKey()
    {

    }

    public function testHasInGetParam()
    {

    }

    public function testGetJoin()
    {

    }

    public function testSetGet()
    {

    }

    public function testGetGet()
    {

    }
}
