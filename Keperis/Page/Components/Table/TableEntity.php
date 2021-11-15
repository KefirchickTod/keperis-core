<?php


namespace Keperis\Page\Components\Table;


use Keperis\Page\Components\TableComponent;

interface TableEntity
{


    public function register(TableComponent $table);
    /**
     * Render table component
     * @return string
     */
    public function render(): string;
}