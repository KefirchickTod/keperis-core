<?php


namespace Keperis\View;


interface RenderInterface
{

    /**
     * @param string $file
     * @param array $data
     * @return string
     * @throws \RuntimeException
     */
    public function render(string $file, array $data = []) : string ;
}