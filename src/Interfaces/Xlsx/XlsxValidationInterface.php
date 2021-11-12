<?php


namespace Keperis\Interfaces\Xlsx;


interface XlsxValidationInterface
{

    public function validate($data);

    public function getMassage();
}