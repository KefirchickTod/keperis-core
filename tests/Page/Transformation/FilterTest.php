<?php

namespace Keperis\Page\FilterData;

use Keperis\Eloquent\Provide\ProvideTemplate;
use PHPUnit\Framework\TestCase;
use Keperis\Page\DataTransformation;


class bcFilter extends ProvideTemplate
{
    protected $sqlSetting = [
        'table' => 'none',
        'fullName' => [
            'select' => 'false',
            'type' => 'string',
        ],
    ];

    /**
     * Get resolve name for quick copy obj
     * @return string
     */
    public function getResolveName(): string
    {
        // TODO: Implement getResolveName() method.
    }
}

class FilterTest extends TestCase
{

    public function test__invoke()
    {
        $filtering = [
            'fullName' => 'one'
        ];

        $structure = [
            'get' => [
                'fullName'
            ],
            'class' => bcFilter::class,
        ];

        $transformation = new DataTransformation($structure);
        $transformation->addFilter(Filter::class);
        $data = $transformation->callFilter([
            'filter' => json_encode($filtering),
        ]);

       $structure['setting']['where'] = 'false = one';
        $this->assertEquals($data, $structure);
    }
}
