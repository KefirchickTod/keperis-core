<?php


namespace src\Page\FilterData;


interface ProvideCreate
{
    public function getRow($page = false);

    public function getResult();
}