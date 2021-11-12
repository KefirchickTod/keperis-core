<?php


namespace Keperis\Page;


use Keperis\Eloquent\Provide\Commands\ProvideReceiver;
use Keperis\Eloquent\Provide\StructureCollection;
use Keperis\Structure\StructureFilters\StructureFilterData;

class DataTransformation
{

    use StructureFilterData;

    protected $changer;

    public function __construct(?array $structure, string $key = null)
    {

        if (!$key) {
            $key = key($structure);
        }

        $this->changer = new ProvideReceiver(new StructureCollection($key, $structure));
    }


    public function callFilter($uriBody = null)
    {
        return $this->callMiddlewareStack($this->changer, $uriBody);

    }

    /**
     * @param $data ProvideReceiver
     * @return array
     */
    public function __invoke($data)
    {

        if ($data->hasKeyInSetting('where')) {
            $data->changeWhere(function ($where) {
                if (is_array($where)) {
                    $where = implode(' AND ', $where);
                }
                return $where;
            });
        }
        return (array)$data->getStructure()->toArray();
    }
}
