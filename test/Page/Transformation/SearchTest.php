<?php

namespace Keperis\Page\FilterData;

use PHPUnit\Framework\TestCase;
use Keperis\Page\DataTransformation;
use Keperis\Structure\ProvideStructures;

class bcTestSearch extends ProvideStructures
{
    protected $sqlSetting = [
        'table'       => 'none',
        'fullName'    => [
            'select' => 'false',
            'type'   => 'string',
        ],
        'secondName'  => [
            'select' => 'select2',
            'type'   => 'string',
        ],
        'selectemail' => [
            'select' => 'select-email',
            'type'   => 'email',
            'templates' => "GROUP_CONCAT(%_select_% SEPARATOR ' | ')",
        ],
    ];
}


class SearchTest extends TestCase
{

    public function test__invoke()
    {
        $structure = [
            'get'   => [
                'fullName',

            ],
            'class' => bcTestSearch::class,
        ];
        $transformation = new DataTransformation($structure);
        $transformation->addFilter(Search::class);
        $data = $transformation->callFilter([
            'search' => 'hello',
        ]);



        $exepted = "false LIKE '%hello%' OR select2 LIKE '%hello%'";

        $this->assertEquals($exepted, $data['setting']['where']);
    }
}
