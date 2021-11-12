<?php


namespace Keperis\Page\FilterData;


use Error;
use Keperis\Eloquent\Provide\Commands\ProvideReceiverInterface;

class Search extends TransformationLogic
{

    const SEARCH_ALL = '-1';

    /**
     * @var string|null
     */
    protected $search;

    /**
     * @param string $search
     * @return string
     */
    protected function getTypeForSearch(string $search)
    {
        if (is_numeric($search)) {
            return 'int';
        }

        if (filter_var($search, FILTER_VALIDATE_EMAIL)) {
            return 'email';
        }

        if (preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $search)) {
            return 'date';
        }


        return 'string';
    }


    private function clean(string $search)
    {
        return preg_replace('@<(\w+)\b.*?>.*?</\1>@si', '', trim($search));
    }

    /**
     * @param string $str
     * @return bool
     * @link https://stackoverflow.com/questions/7548533/how-to-find-apostrophe-in-string-using-php
     */
    private function hasApostrophe(string $str): bool
    {
        return strpos($str, '\'') || strpos($str, "'");
    }

    private function removeApostrophe(string $search)
    {
        return str_replace('\'', '', $search);
    }

    /**
     * @param string $search
     * @return array
     */
    public function search(string $search): array
    {

        $type = $this->getTypeForSearch($search);

        $values = $this->findValuesByType($type);


        if (!$values) {
            return [];
        }

        $search = $this->clean($search);

        $parse = function ($search) use ($values) {
            $where = [];
            foreach ($values as $value) {
                $value = $this->replaceTemplates($value);

                if (!is_string($value)) {
                    throw new Error("Value after replace must be a string");
                }

                $where[] = "$value LIKE '%{$search}%'";
            }
            return $where;
        };

        $where = $parse($search);
        if ($this->hasApostrophe($search)) {
            $where = array_merge($where, $parse($this->removeApostrophe($search)));
        }


        return $where;
    }

    /**
     * @param $changer ProvideReceiverInterface
     * @param $uriBody array
     * @param $next callable
     * @return mixed
     */
    public function __invoke($changer, $uriBody, $next)
    {
        $value = $uriBody['search'] ?? self::SEARCH_ALL;

        if ($value === self::SEARCH_ALL) {
            return $next($changer, $uriBody);
        }


        $search = Search::createFromMiddleware($changer->getStructure());

        $searchWhere = $search->search($value);

        $changer->changeWhere(function ($where) use ($searchWhere) {
            if (!is_array($where)) {
                $where = [$where];
            }


            if ($searchWhere) {
                $where[] = implode(' OR ', $searchWhere);
            }
            return $where;
        });

//        exit;
//        $search = new bcProvideSearch();
//        $apostophe = new ApostropheCreat();
//        $searchValue = str_replace('"', "'", $search->cleanSearch(trim($uriBody['search'])));
//
//        $isApostrof = boolval(preg_match("~'~", $uriBody['search']));
//
//        $apostopheValue = $apostophe->setStr($searchValue)->render();
//        if ($apostopheValue !== $searchValue) {
//            $where[] = join(" OR ", [
//                $search->setDataStructure($dataArray)->setSearch($searchValue)->parse()->creatQuery()->getWhere(),
//                $search->withSearch(addslashes($apostopheValue))->parse()->creatQuery()->getWhere(),
//            ]);
//
//            // echo $test ."<br><br>$test1";exit;
//        } else {
//            if ($isApostrof !== false) {
//                $deletApostofVal = implode('', array_filter(explode("'", $uriBody['search']), function ($val) {
//                    return $val !== "'";
//                }));
//                $where[] = join(" OR ", [
//                    $search->setDataStructure($dataArray)->setSearch($search->cleanSearch(trim($uriBody['search'])))->parse()->creatQuery()->getWhere(),
//                    $search->withSearch($search->cleanSearch($deletApostofVal))->parse()->creatQuery()->getWhere(),
//                ]);
//            } else {
//                $where[] = $search->setDataStructure($dataArray)->setSearch($search->cleanSearch(trim($uriBody['search'])))->parse()->creatQuery()->getWhere();
//            }
//        }
//
//        $changer->changeWhere(function ($w) use ($where) {
//            return $where;
//        });

        return $next($changer, $uriBody);
    }
}
