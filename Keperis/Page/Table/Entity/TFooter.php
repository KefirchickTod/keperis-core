<?php


namespace Keperis\Page\Table\Entity;



use Keperis\Page\Components\Table;

class TFooter implements TableEntity
{




    public function register(Table $table)
    {

        return $table;
    }
    public function render(): string
    {
        return  "Render table footer";
    }
}