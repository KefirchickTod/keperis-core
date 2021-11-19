<?php

namespace Eloquent\Provide\Builder;

use Keperis\Eloquent\Provide\Builder\StructureQueryBuilder;
use Keperis\Eloquent\Provide\ProvideTemplate;
use Keperis\Eloquent\Provide\StructureCollection;
use PHPUnit\Framework\TestCase;


class TemplateOfBuilder extends ProvideTemplate
{

    protected $temp = [
        'table' => 'bc_test',
        'city'  => [
            'select' => 'city',
            'as'     => 'as_city',
        ],
        'staff' => [
            'select' => 'bc_staff',
            'as' => 'staff',
            'template' => 'CONCAT(%_select_%)'
        ],
    ];

    /**
     * Get resolve name for quick copy obj
     * @return string
     */
    public function getResolveName(): string
    {
        return 'table_builder_test';
    }
}

class TemplateOfBuilderJoin extends ProvideTemplate
{

    protected $temp = [
        'table' => 'bc_join',
        'turnoved' => [
            'select' => 'bc_turnoved',
            'as' => 'turnoved',
            'template' => 'CONCAT(%_select_%)'
        ],
    ];

    /**
     * Get resolve name for quick copy obj
     * @return string
     */
    public function getResolveName(): string
    {
        return 'table_builder_test';
    }
}

class StructureQueryBuilderTest extends TestCase
{


    public function testToSql()
    {
        $structure = [
            'get'   => ['city', 'staff'],
            'class' => TemplateOfBuilder::class,
            'setting' => [
                'join' => [
                    [
                        'get' => ['turnoved'],
                        'class' => TemplateOfBuilderJoin::class,
                        'join_type' => 'LEFT',
                        'on' => 'join_id = id'
                    ],
                ],
                'order' => 'as_city',
                'where' => 'city in (1,2)'
            ],
        ];

        $builder = new StructureQueryBuilder(new StructureCollection('test', $structure));

        $builder->build();

        $this->assertEquals("select city as as_city, bc_staff as staff, bc_turnoved as turnoved from bc_test left join `bc_join` on join_id  =  id where city in (1,2) order by `as_city` asc", $builder->toSql());

    }

    public function testLine(){
        $structure = [
            'get'   => ['city', 'staff'],
            'class' => TemplateOfBuilder::class,
            'setting' => [
                'order' => 'as_city',
                'where' => 'city in (1,2)',
                'line' => [
                    'select' => 'id',
                    'join' => [
                        [
                            'table' => 'user',
                            'on' => 'id1 = id2'
                        ]
                    ],
                ]
            ],
        ];

        $builder = new StructureQueryBuilder(new StructureCollection('test', $structure));

        $builder->build();


        $this->assertEquals("select city as as_city, bc_staff as staff, id from bc_test inner join `user` on id1  =  id2 where city in (1,2) order by `as_city` asc", $builder->toSql());
    }
}
