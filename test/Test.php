<?php


use PHPUnit\Framework\TestCase;

class Test extends TestCase
{

    public function test__init(){
        $add = function ($a, $b){
            return $a + $b;
        };

        $this->assertSame(23, $add(20, 3));
    }
}
