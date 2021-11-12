<?php


namespace src\Page\Components\Table;


use src\Page\Components\TableComponent;

interface TableEntity
{


    public function register(TableComponent $table);
    /**
     * Render table component
     * @return string
     */
    public function render(): string;
}