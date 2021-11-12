<?php


namespace src\Eloquent;


use Illuminate\Database\ConnectionResolver as ParentResolver;

class ConnectionResolver extends ParentResolver
{

    protected $default = 'mysql';
}