<?php


namespace src\Page\FilterData;


use src\Eloquent\Provide\Commands\ProvideReceiver;

class Sort extends TransformationLogic
{

    private function parseSort(array $structureGetBody, $sort, $changer)
    {
        if (in_array($sort, $structureGetBody)) {
            return $changer->changeInSetting('order', function () use ($sort) {
                return $sort;
            });
        }
        foreach ($structureGetBody as $value) {
            if ("a_{$value}" == $sort) {
                return $changer = $changer->changeInSetting('order', function () use ($sort) {
                    return $sort;
                });
            }
        }
        return null;
    }


    /**
     * @param $uriBody array
     * @param $changer ProvideReceiver
     * @param $next callable
     * @return mixed
     */
    public function __invoke($changer, $uriBody, $next)
    {
        if (!array_key_exists('sort', $uriBody)) {
            return $next($changer, $uriBody);
        }

        $sort = htmlspecialchars(trim($uriBody['sort']));

        $ch = $this->parseSort($changer->getStructure()->getGet(), $sort, $changer);

        if (!$ch) {
            $joins = $changer->getStructure()->getJoin();
            foreach ($joins as $join) {

                $ch = $this->parseSort($join['get'], $sort, $changer);

                if ($ch) {
                    return $next($ch, $uriBody);
                }

            }
        }


        return $next($changer, $uriBody);
    }
}
