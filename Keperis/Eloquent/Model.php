<?php


namespace Keperis\Eloquent;


use Keperis\Eloquent\Concerns\ImportXlsx;
use Keperis\Eloquent\Concerns\MaskConcerns;

abstract class Model extends \Illuminate\Database\Eloquent\Model
{
    use MaskConcerns;


    /**
     * Unset default timestamps
     * @var bool
     */
    public $timestamps = false;

    /**
     * Create connection to db (MySql);
     * @return \Illuminate\Database\Query\Builder
     */
    protected function newBaseQueryBuilder()
    {
        return container()->connection->query();
    }


}