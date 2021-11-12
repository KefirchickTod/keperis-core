<?php


namespace src\Http;


use src\Collection;

class Environment extends Collection
{

    public static function mock($userData){
        if(empty($userData)){
            error_log("Empty load env data");
        }
        return new static($userData);
    }
}