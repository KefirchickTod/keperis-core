<?php

namespace Keperis\Page;

use Keperis\Eloquent\Provide\ProvideTemplate;
use PHPUnit\Framework\TestCase;
use Keperis\Eloquent\Provide\Commands\ProvideReceiver;



class bcTestSort extends ProvideTemplate
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
        return 'test';
    }
}

class Sort
{


    /**
     * @param $structure array
     * @param $uriBody array
     * @param $changer ProvideReceiver
     * @param $next callable
     * @return mixed
     */
    public function __invoke($structure, $uriBody, $changer, $next)
    {
        if (!array_key_exists('sort', $uriBody)) {
            return $next($structure, $uriBody, $changer);
        }

        $name = key($structure);
        $join = isset($structure[$name]['setting']['join']) ? array_keys($structure[$name]['setting']['join']) : null;
        creatSort($structure, $name, $join, $uriBody);

        return $next($structure);
    }
}

class DataTransformationTest extends TestCase
{

    public function testCallFilter()
    {

        $structure = [

            'get' => [
                'fullName'
            ],

            'class' => bcTestSort::class,
        ];
        $transformation = new DataTransformation($structure);

        $transformation->addFilter(function ($changer, $uriBody, $next) {
            /** @var $changer ProvideReceiver */
            $this->assertInstanceOf(ProvideReceiver::class, $changer);

            $changer->changeGet(function () {
                return false;
            });

            return $next($changer, $uriBody);
        });

        $data = $transformation->callFilter();

        $this->assertNotEquals($data, $structure);
    }
}
