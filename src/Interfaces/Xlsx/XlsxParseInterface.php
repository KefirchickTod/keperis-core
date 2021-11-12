<?php


namespace Keperis\Interfaces\Xlsx;


use Keperis\Collection;
use Keperis\Models\Model;

interface XlsxParseInterface
{

    public function parse(Collection $data, Model $model);

    public function getTitle(): array;

    public function getData(): array;

    public function toArray();
}