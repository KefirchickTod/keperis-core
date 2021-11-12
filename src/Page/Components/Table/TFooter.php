<?php


namespace Keperis\Page\Components\Table;



use Keperis\Page\Components\TableComponent;

class TFooter implements TableEntity
{




    public function register(TableComponent $table)
    {

        return $table;
    }
    public function render(): string
    {
        return  "Render table footer";
    }
}
