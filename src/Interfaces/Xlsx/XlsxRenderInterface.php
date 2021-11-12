<?php


namespace src\Interfaces\Xlsx;


use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

interface XlsxRenderInterface
{

    /**
     * @return string
     */
    public function render();


    /**
     * @return mixed
     */
    public function execute(XlsxValidationInterface $validation);

    public function parser() : XlsxParseInterface;

}