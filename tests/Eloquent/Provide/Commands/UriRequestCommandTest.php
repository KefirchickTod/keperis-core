<?php

namespace Keperis\Eloquent\Provide\Commands;


use PHPUnit\Framework\TestCase;
use Keperis\Http\Request;
use Keperis\Http\ServerData;
use Keperis\Http\Uri;


class SimulateCommand extends UriRequestCommand
{

    public function execute()
    {
        return true;
    }
}

class bcTest extends ProvideTemplate
{
    protected $sqlSetting = [
        'table' => 'none',
        'fullName' => [
            'select' => 'false',
            'type'   => 'string',
        ],
    ];
}

class UriRequestCommandTest extends TestCase
{

    public static function command(): SimulateCommand
    {

        $request = Request::creatFromServerData(new ServerData($_SERVER));
        $request = $request->withUri(Uri::createFromString('?search=test&filter={}'));

        $command = new SimulateCommand($request, new ProvideReceiver([
            'get'     => [],
            'class'   => bcTest::class,
            'setting' => [],
        ]));

        return $command;

    }

    public function testGetType()
    {
        $command = self::command();
        $type = $command->getType('fullName');
        $this->assertEquals('string', $type);
    }

    public function testHas()
    {
        $command = self::command();

        $this->assertFalse($command->has('sort'));
        $this->assertTrue($command->has('search'));
        $this->assertTrue($command->has('filter'));
    }

    public function testGet()
    {
        $command = self::command();

        $this->assertEquals('test', $command->get('search'));
        $this->assertNotSame('', $command->get('filter'));

    }

    public function testGetQuery()
    {
        $query = self::command()->getQuery();

        $this->assertIsArray($query);

        $this->assertSame([
            'search' => 'test',
            'filter' => '{}',
        ], $query);
    }
}
