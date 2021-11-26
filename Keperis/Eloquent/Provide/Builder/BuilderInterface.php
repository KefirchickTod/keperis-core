<?php


namespace Keperis\Eloquent\Provide\Builder;


use Illuminate\Database\Query\Builder;

interface BuilderInterface
{


    /**
     * @return Builder
     */
    public function getTable() : Builder;

    /**
     * Building to query
     * @return BuilderInterface
     */
    public function build();


    /**
     * @return string
     */
    public function toSql() : string;
}
