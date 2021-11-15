<?php


namespace Keperis\Eloquent;


use Illuminate\Database\ConnectionResolver as ParentResolver;

class ConnectionResolver extends ParentResolver
{

    /**
     * Default value for normal connection to db (only for mysql)
     * @var string
     */
    protected $default = 'mysql';
}