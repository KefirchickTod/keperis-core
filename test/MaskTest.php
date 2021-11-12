<?php

namespace Keperis;


class UMask extends \App\Provides\Mask
{
    protected $title = [
        'list' => [
            'hello' => [
                'text' => 'World',
            ],
        ],
        'call' => [
            'first' => [
                'text' => 'First',
            ],
        ],
    ];

    protected $mask = [
        'list' => [
            'user' => [],
        ],
        'call' => [
            'call' => [],
        ],
    ];

    protected $action = [
        'list' => [],
    ];

    public function getCallTitle($title)
    {
        $title['first']['text'] = 'Second';
        return $title;
    }

    protected function getCallMask($mask)
    {
        return null;
    }
}

class MaskTest extends \PHPUnit\Framework\TestCase
{


    /**
     * @return \App\Provides\Mask|UMask
     */
    public function getMask()
    {
        $mask = new UMask();
        return $mask;
    }

    public function testGetTitle()
    {
        $mask = $this->getMask();

        $title = $mask->getTitle('list');

        $this->assertSame([
            'hello' => [
                'text' => 'World',
            ],
        ], $title);

        $title = $mask->getTitle('call');

        $this->assertNotSame([
            'hello' => [
                'text' => 'World',
            ],
        ], $title);

    }

    public function testGetMask()
    {
        $mask = $this->getMask();

        $this->assertSame([
            'user' => []
        ], $mask->getMask('list'));

        $this->assertNull($mask->getMask('call'));
    }

    public function testGetAction(){
        $action = $this->getMask()->getAction('bal');

        $this->assertEmpty($action);
    }

}
