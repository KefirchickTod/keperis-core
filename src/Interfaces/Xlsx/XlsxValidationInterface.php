<?php


namespace src\Interfaces\Xlsx;


interface XlsxValidationInterface
{

    public function validate($data);

    public function getMassage();
}