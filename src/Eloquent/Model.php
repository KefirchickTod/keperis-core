<?php


namespace Keperis\Eloquent;


use Keperis\Eloquent\Concerns\ImportXlsx;
use Keperis\Eloquent\Concerns\MaskConcerns;

abstract class Model extends \Illuminate\Database\Eloquent\Model
{
    use ImportXlsx, MaskConcerns;


    public $timestamps = false;

    protected function newBaseQueryBuilder()
    {
        return container()->connection->query();
    }


    public function setAsOrm(array $value){
        if(!is_array($value)){
            throw new \InvalidArgumentException("Value for create orm must by a array type");
        }

        $clone = new static($value);

        return $clone;
    }



}