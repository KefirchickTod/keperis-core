<?php

namespace Keperis\Eloquent\Provide;

use PHPUnit\Framework\TestCase;

class StructureValidateTest extends TestCase
{

    public function testValidator()
    {
        $this->expectException(\RuntimeException::class);
        $structure = [
            'get'     => [],
            'class'   => get_class(new class{}) ,
            'setting' => [
                'join' => [],
            ],
        ];

        $validator = new StructureValidate($structure);

        $this->assertFalse($validator->validate());

        $structure['class'] = null;
        StructureValidate::checkValidate($structure);


        $structure['get'] = ['test'];
        $structure['class'] =  get_class(new class{}) ;
        unset($structure['setting']);
        $this->assertTrue(StructureValidate::checkValidate($structure));
    }
}
