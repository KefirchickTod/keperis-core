<?php


namespace Keperis\Eloquent\Provide;


use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\MySqlGrammar;

class ProvideQueryBuilder
{

    private $processor;

    private $query;

    public function __construct($processor)
    {
        $this->processor = $processor;
        $this->query = new Builder(container()->connection, new MySqlGrammar());
    }

    public function findInPattern(string $key){

    }

    public function exucute(){}

    public function join(){}


    public function toSql(){}

}