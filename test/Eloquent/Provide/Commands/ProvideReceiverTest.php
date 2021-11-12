<?php

namespace src\Eloquent\Provide\Commands;

use PHPUnit\Framework\TestCase;
use src\Eloquent\Provide\StructureCollection;

class ProvideReceiverTest extends TestCase
{

    static $collection = null;

    public static function createStructure()
    {

        if (!is_null(self::$collection)) {
            return self::$collection;
        }
        $data = [
            'get'     => ['u'],
            'class'   => 'test',
            'setting' => [
                'where' => 'false',
            ],
        ];
        self::$collection = new StructureCollection('test', $data);
        return self::$collection;
    }



    public function testChangeSetting()
    {
        $receiver = new ProvideReceiver(self::createStructure());

        $receiver->changeInSetting('join', function ($join) {
            return [];
        });

        $structure = $receiver->get()['setting'];

        $this->assertArrayHasKey('join', $structure);
        $this->assertIsArray($structure['join']);

    }

    public function testChangeWhere()
    {
        $receiver = new ProvideReceiver(self::createStructure());

        $receiver->changeWhere(function () {
            return 'hello';
        });

        $structure = $receiver->get();

        $this->assertEquals([
            'get'     => ['u'],
            'class'   => 'test',
            'setting' => [
                'where' => 'hello',
            ],
        ], $structure);
    }

    public function testChange()
    {
        $receiver = new ProvideReceiver(self::createStructure());

        $receiver->change('class', function ($class) {
            $this->assertSame('test', $class);
            return null;
        });

        $structure = $receiver->get();

        $this->assertEquals([
            'get'     => ['u'],
            'class'   => null,
            'setting' => [
                'where' => 'false',
            ],
        ], $structure);

    }

    public function testChangeGet()
    {
        $receiver = new ProvideReceiver(self::createStructure());

        $receiver->changeGet(function () {
            return ['a'];
        });

        $structure = $receiver->get();


        $this->assertEquals([

            'get'     => ['a'],
            'class'   => 'test',
            'setting' => [
                'where' => 'false',
            ],

        ], $structure);

    }

}
