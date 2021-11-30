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
    /**
     * @var \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    private $paginate = null;

    public function __construct(?Builder $query)
    {
        $this->builder = $query;

        $this->total = $this->builder->getCountForPagination();
    }


    /**
     * Return all count
     * @return int
     */
    public function getCount()
    {
        return $this->builder->getCountForPagination();
    }


    /**
     * Compiled and paginate builder
     * @return AbstractPaginator
     */
    public function paginate(): AbstractPaginator
    {
        $this->paginate = $this->builder->paginate(self::PER_PAGE);

        return $this->paginate;
    }


    /**
     * Easy factory
     * @return Render
     */
    public function makeRender()
    {
        return new Render($this);
    }

    /**
     * @return bool
     */
    public function hasPaginated()
    {

        return $this->paginate instanceof AbstractPaginator;
    }

    /**
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator|null
     */
    public function getPaginate()
    {
        return $this->paginate;
    }

}
