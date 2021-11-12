<?php


namespace src\Interfaces\Xlsx;


use src\Collection;
use src\Models\Model;

interface XlsxParseInterface
{

    public function parse(Collection $data, Model $model);

    public function getTitle(): array;

    public function getData(): array;

    public function toArray();
}