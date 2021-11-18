<?php

namespace Eloquent\Provide;

use Keperis\Eloquent\Provide\Event\ProvideEventInterface;
use Keperis\Eloquent\Provide\Event\ProvideEvents;
use Keperis\Eloquent\Provide\ProvideStructure;
use Keperis\Eloquent\Provide\ProvideTemplate;
use Keperis\Eloquent\Provide\StructureCollection;
use PHPUnit\Framework\TestCase;

class ProvideStructureTemplate extends ProvideTemplate
{

    protected $temp = [
        'table' => 'bc_test',
        'city'  => [
            'select' => 'city',
            'as'     => 'as_city',
        ],
        'staff' => [
            'select'   => 'bc_staff',
            'as'       => 'staff',
            'template' => 'CONCAT(%_select_%)',
        ],
    ];

    /**
     * Get resolve name for quick copy obj
     * @return string
     */
    public function getResolveName(): string
    {
        return 'table_builder_test';
    }
}

class Event extends ProvideEvents
{

    public function event()
    {

        $this->change->changeGet(function (){
            return ['city'];
        });
    }
}

class ProvideStructureTest extends TestCase
{

    /** @var StructureCollection */
    private static $collection = null;

    /**
     * @return StructureCollection
     */
    private static function getCollection()
    {
        if (is_null(self::$collection)) {
            self::$collection = new StructureCollection('test', [
                'get'   => [
                    'staff',
                ],
                'class' => ProvideStructureTemplate::class,
            ]);
        }
        return self::$collection;
    }


    public function testBuild()
    {

        $provide = new ProvideStructure(self::getCollection());


        $provide->build();

    }

    public function testGlobalEvent()
    {
        ProvideStructure::registerGlobalEvents('beforeBuild', Event::class);
        $provide = new ProvideStructure(self::getCollection());


        $provide->build();


        $this->assertEquals(['city'], $provide->getCollection()->getGet());


    }
}
