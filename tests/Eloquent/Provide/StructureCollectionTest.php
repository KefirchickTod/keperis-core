<?php

namespace Keperis\Eloquent\Provide;

use PHPUnit\Framework\TestCase;


class bcTest extends ProvideTemplate
{
    protected $sqlSetting = [
        'table'    => 'none',
        'fullName' => [
            'select' => 'false',
            'type'   => 'string',
        ],
    ];

    /**
     * Get resolve name for quick copy obj
     * @return string
     */
    public function getResolveName(): string
    {
        return 'bc_test';
    }
}

class StructureCollectionTest extends TestCase
{
    private static $structure = [
        'get'     => ['fullName'],
        'class'   => bcTest::class,
        'setting' => [
            'join' => [
                'join1' => [
                    'get'   => ['fullName'],
                    'class' => bcTest::class,
                ],
            ],
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


}
