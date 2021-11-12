<?php


namespace Keperis\Page\FilterData;


use Error;
use Exception;

use Keperis\Eloquent\Provide\Commands\ProvideReceiver;

class Filter extends TransformationLogic
{


    /**
     * Unique rules for filtration data
     * @var array
     */
    public static $exceptions = [];

    /**
     * Basic name for filter by empty value or filled
     * @var string[]
     */
    protected $basic = [
        'empty' => '(%_val_%     IS NULL OR %_val_% = ""   OR   %_val_% = "0")',
        'filled' => '(%_val_%    IS NOT NULL     AND %_val_%  != ""  AND %_val_% != "0")',
    ];




    protected function getTypeOfFilterValue($value)
    {
        $type = 'string';

        if (isInteger($type)) {
            return 'int';
        }

        return $type;
    }





    /**
     * Parse out filter value and return setting ('where') for strucutre
     * @param array $filter
     * @return array
     * @throws Exception
     */
    public function filters(array $filter): array
    {
        if (!$filter) {
            return [];
        }


        $where = [];

        foreach ($filter as $key => $values) {
            if (!is_array($values)) {
                $values = explode(',', $values);
            }

            $pattern = $this->findPattern($key);
            if (is_null($pattern)) {
                throw new Exception("Cant find pattern for key: {$key}");
            }


            $where[] = implode(' OR ', $this->createSubWhere($values, $pattern, self::$exceptions[$key] ?? null));
        }

        //$where = implode(' AND ', $where);



        return $where;

    }

    /**
     * @param $changer ProvideReceiver
     * @param $uriBody array
     * @param $next callable
     * @return mixed
     */
    public function __invoke($changer, $uriBody, $next)
    {

        if (!array_key_exists('filter', $uriBody)) {
            return $next($changer, $uriBody);
        }

        $filterAttributes = $uriBody['filter'];

        if (is_string($filterAttributes)) {
            try {
                if (!is_array(json_decode($filterAttributes, true))) {
                    throw new Exception("Filter attributes must be a json");
                }
                $filterAttributes = json_decode($filterAttributes, true);

            } catch (Exception $e) {

                if (!is_array(unserialize($filterAttributes))) {
                    throw new Error("Filter attributes must be a serialized array");
                }
                $filterAttributes = unserialize($filterAttributes);
            }
        }

        //PageCreatePaginator::$distinct = array_keys($filterAttributes);//todo dispatch to paginator


        $changer->changeWhere(function ($where) use ($changer, $filterAttributes) {
            if (!is_array($where)) {
                $where = [$where];
            }

            $filter = Filter::createFromMiddleware($changer->getStructure())->filters($filterAttributes);

            if($filter){
                $where[] = implode(' AND ', $filter);
            }


            return $where;
        });
        return $next($changer, $uriBody);

    }

    private function createSubWhere(array $values, $pattern, $exception = null)
    {

        return array_map(function ($val) use ($pattern, $exception) {

            if (!is_null($exception)) {
                return $exception[$val] ?? "$pattern = $val";
            }

            return "$pattern = $val";
        }, $values);
    }
}
