<?php


namespace Keperis\Page\Paginator;


use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\AbstractPaginator;
use Keperis\Eloquent\Provide\Builder\BuilderInterface;

class Paginator
{


    const PER_PAGE = 10;

    /**
     * @var Builder
     */
    protected $builder;

    /**
     * @var int
     */
    protected $total = 0;

    public function __construct(Builder $query)
    {
        $this->builder = $query;

        $this->total = $this->builder->getCountForPagination();
    }


    public function getCount()
    {
        return $this->builder->getCountForPagination();
    }


    public static function makeByStructure(BuilderInterface $builder)
    {
        return new static($builder->getTable());
    }




    /**
     * @return AbstractPaginator
     */
    public function paginate(): AbstractPaginator
    {
        return $this->builder->paginate(self::PER_PAGE);
    }
}
