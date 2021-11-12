<?php

namespace Collection;

use Keperis\Collection;
use PHPUnit\Framework\TestCase;

class CollectionTest extends TestCase
{


    public function testGetIterator()
    {
        $collection = new Collection([1, 2, 3]);

        $iterator = $collection->getIterator();

        $this->assertInstanceOf(\ArrayIterator::class, $iterator);

    }

    public function testGet()
    {

        $collection = new Collection([
            'key' => 'hello',
            1     => 'first',
        ]);

        $this->assertSame('hello', $collection->get('key'));

        $this->assertNull($collection->get(0));

        $this->assertSame('first', $collection->get(1));

    }

    public function testClear()
    {
        $collection = new Collection([1, 2, 3, 5]);

        $this->assertTrue($collection->count() > 0);

        $collection->clear();

        $this->assertFalse($collection->count() > 0);

    }

    public function testMap()
    {
        $collection = new Collection(['first' => '1', 'second' => '2']);

        $mapping = $collection->map(function ($val, $key) {
            if ($key === 'first') {
                return 2;
            }
            if ($key === 'second') {
                return 1;
            }
        });

        $this->assertSame(['first' => 2, 'second' => 1], $mapping);
    }

    public function testFirst()
    {
        $collection = new Collection([45, 234, 4332, 234]);

        $first = $collection->first();


        $this->assertSame(45, $first);

        $collection->clear();

        $this->assertNull($collection->first());

    }

    public function testCount()
    {

        $data = [];

        for($i = 0; $i < 1000; $i++){
            $data[] = $i;
        }

        $collection = new Collection($data);

        $this->assertTrue($collection->count() === 1000);

    }

    public function testToArray()
    {
        $arr = [1, 'hell', 'as'];
        $collection = new Collection([1, 'hell', 'as']);

        $this->assertSame($arr, (array)$collection->toArray());

    }

}
