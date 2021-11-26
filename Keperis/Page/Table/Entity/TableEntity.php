<?php


namespace Keperis\Page\Table\Entity;


use Keperis\Page\Table\Table;

interface TableEntity
{


    public function register(Table $table);
    /**
     * Render table component
     * @return string
     */
    public function render(): string;
}
