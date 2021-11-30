<?php

namespace Page\Paginator;

use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\AbstractPaginator;
use Keperis\Page\Paginator\Paginator;
use PHPUnit\Framework\TestCase;

class PaginatorTest extends TestCase
{

    public function testPaginate()
    {
        $builder = new Builder(app()->container->connection);

        $paginate = new Paginator($builder->from('bc_user'));

        $this->assertInstanceOf(AbstractPaginator::class, $paginate->paginate());
    }

    public function testGetCount()
    {
        $builder = $this->createMock(Builder::class);


        $builder->method('getCountForPagination')
            ->willReturn(10);


        $paginator = new Paginator($builder);

        $this->assertSame(10, $paginator->getCount());
    }
}
