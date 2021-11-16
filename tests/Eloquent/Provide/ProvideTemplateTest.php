<?php

namespace Eloquent\Provide;

use Keperis\Eloquent\Provide\ProvideTemplate;
use PHPUnit\Framework\TestCase;


class Template extends ProvideTemplate
{

    protected $temp = [
        'table' => 'test_db',
        'id' => 'test_db_id AS id',
        'select1' => [
            'select' => 'convert_to_test1',
            'as' => 'test1'
        ],

        'select2' => [
            'select' => [
                'sw1', 'sw2'
            ],
            'as' => 'sw_s',
            'templates' => 'GROUP_CONTCAT(%_select_0_%, %_select_1_%)'
        ],
    ];


    /**
     * Get resolve name for quick copy obj
     * @return string
     */
    public function getResolveName(): string
    {
        return 'UNIT_TEST_PROVIDE_TEMPLATE';
    }
}

class ProvideTemplateTest extends TestCase
{

    static $template = null;

    /**
     * @return Template|ProvideTemplate
     */
    public static function createTemplate()
    {
        if (!self::$template instanceof Template) {
            self::$template = new Template();
        }
        return self::$template;
    }

    public function testGetOriginTableName()
    {

        $this->assertSame('test_db', self::createTemplate()->getOriginTableName());
    }

    public function testGetTemplates()
    {
        $excepted = [
             [
                'select' => 'convert_to_test1',
                'as' => 'test1'
            ],
             [
                'select' => [
                    'sw1', 'sw2'
                ],
                'as' => 'sw_s',
                'templates' => 'GROUP_CONTCAT(%_select_0_%, %_select_1_%)'
            ]
        ];

        $this->assertEquals($excepted, self::createTemplate()->getTemplates('select1', 'select2'));


        $this->expectException(\Exception::class);

        $this->assertInstanceOf(\InvalidArgumentException::class, self::createTemplate()->getTemplates('b'));


    }

    public function testConvertTemplate()
    {
        [$temp1, $temp2] = self::createTemplate()->getTemplates('select1', 'select2');


        $temp1 = (self::createTemplate())::convertTemplate($temp1);


        $this->assertEquals([
            'select' => 'convert_to_test1',
            'as' => 'test1'
        ], $temp1);


        $this->assertSame([
            'select' => 'GROUP_CONTCAT(sw1, sw2)',
            'as' => 'sw_s',
            'templates' => 'GROUP_CONTCAT(sw1, sw2)'
        ], (self::createTemplate())::convertTemplate($temp2));
    }

    public function testGetResolveName()
    {
        $this->assertSame('UNIT_TEST_PROVIDE_TEMPLATE', self::createTemplate()->getResolveName());
    }

    public function testGetTemplate()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->assertSame([
            'select' => 'convert_to_test1',
            'as' => 'test1'
        ], self::createTemplate()->getTemplate('select1'));

        self::createTemplate()->getTemplate('b');

    }

    public function testGetAllWhereType()
    {
        $this->assertEmpty(self::createTemplate()->getAllWhereType('int'));
    }
}
