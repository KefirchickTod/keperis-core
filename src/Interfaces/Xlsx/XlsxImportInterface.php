<?php


namespace src\Interfaces\Xlsx;


interface XlsxImportInterface
{

    public function table();
    public function count();

    /**
     * @return XlsxRenderInterface
     */
    public function renderer();
}