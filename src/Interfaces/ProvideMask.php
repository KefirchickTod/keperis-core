<?php


namespace src\Interfaces;


interface ProvideMask
{

    /**
     * Return title setting for create table
     * @param $key string
     * @return array|null
     */
    public function getTitle(string $key);

    /**
     * Get provide structure setting
     * @param string $key
     * @return array|null
     */
    public function getMask(string $key);

    /**
     * Get setting for ProvideAction
     * @param string $key
     * @return array|null
     */
    public function getAction(string $key);
}