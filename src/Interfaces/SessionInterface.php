<?php


namespace src\Interfaces;


use src\Http\Session;

interface SessionInterface
{

    public function set(string $key, $value, int $type);

    public function has(string $key, int $type = null): bool;

    public function get(string $key, $type = Session::SUCCESS, $default = null);

    public function isEmpty(): bool;

    public function clear(): void;

    /**
     * @param $key string[]|string
     */
    public function remove( $key): void;

    public function success($value, $key = null);

    public function error($value, $key = null);

    public function hasMassage(int $type) : bool ;

    public function isError() : bool ;

    public function isSuccess() : bool ;


    public function render() ;

}