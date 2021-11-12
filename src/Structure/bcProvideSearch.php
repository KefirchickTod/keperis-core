<?php


namespace src\Structure;

use DateTime;
use Exception;
use src\Core\Filtration\DataFilterPrototype;

class bcProvideSearch extends DataFilterPrototype
{
    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $search;


    public function withSearch(string $search){
        $clone = clone $this;
        $clone->search = $search;
        return $clone;
    }
    /**
     * @param $parram string
     * @return $this
     */
    public function setSearch($parram)
    {
        $this->search = $parram;
     //   $this->search = ApostropheCreat::creat()->setStr($parram)->render();
        return $this;
    }

    /**
     * @return string
     */
    public function getSearchOption()
    {
        return $this->search;
    }

    public function cleanSearch($search)
    {
        return htmlentities(htmlspecialchars($search), ENT_QUOTES);
    }

    /**
     * @return $this
     */
    public function creatQuery()
    {
        try {
            $type = $this->typeSearch();
            if (!$this->where[$type]) {
                error_log("Type error in line 55 bcProvideSearch");
                throw new Exception("Type error");
            }

            $range = $this->apostropheValidation($this->search) == true ? 2 : 1;
            foreach (range(0, $range) as $loop) {
                foreach ($this->where[$type] as $key => $value) {
                    if (!$value) {
                        continue;
                    }
                    if ($type == 'int') {
                        $this->where['result'][$key] = $value . '  =  ' . $this->search;
                    }
                    if ($type == 'date') {

                        $this->where['result'][$key] = $value . "  = '$this->search'";
                    }
                    if ($type != 'int' && $type != 'date') {
                        addToArray($this->where['result'], $key, (is_int($this->search) && $this->search < 5)
                            ?
                            (
                                $this->getPatternList()[0]['idSort']['select'] . " = $this->search"
                            )
                            :
                            "$value LIKE \"%" . $this->deletApostophe($this->search, $loop) . "%\"", " OR ");

                    }
                }
            }
            $this->where['result'][] = $type == 'int' ? $this->getPatternList()[0]['idSort']['select'] . " = $this->search" : null;
            $this->where['result'] = array_diff($this->where['result'], [null, false, '']);
        } catch (Exception $error) {
            $this->error[] = $error->getMessage();
        }
        return $this;
    }

    /**
     * @return string
     */
    private function typeSearch()
    {
        $validatePhone = function () {
            $justNums = preg_replace("/[^0-9]/", '', $this->search);
            if (strlen($justNums) == 11) {
                $justNums = preg_replace("/^1/", '', $justNums);
            }
            if (strlen($justNums) == 10) {
                return true;
            }
            return false;

        };
        if (filter_var($this->search, FILTER_VALIDATE_EMAIL)) {
            $this->type = 'email';
            return 'email';
        }

        if (DateTime::createFromFormat("Y.m.d", $this->search)) {
            $this->type = 'date';
            return 'date';
        }
        $this->type = 'string';
        return 'string';
    }

    /**
     * @param null $search
     * @return false|int|string|null
     */
    protected function apostropheValidation($search = null)
    {
//        if (!$search) {
//            $search = $this->search;
//        }
//        if (preg_match("/&#039;/", $search)) {
//            return true;
//        }
        return false;
    }

    protected function deletApostophe(string $search, int $loop)
    {

//        if (preg_match("/&#039;/", $search) && $loop == 2) {
//
//            return join('', explode('&#039;', $search));
//        }
        return $search;
    }

    /**
     * @return bool|string
     */
    public function getWhere()
    {
        try {
            if (!isset($this->where['result']) || !$this->where['result']) {
                throw new Exception('Empty result');
            }
            return '(' . implode(' OR ', $this->where['result']) . ')';
        } catch (Exception $error) {
            $this->setError($error, __CLASS__);
        }
        return false;
    }

    /**
     * @var_dump error massage
     */
    function __debugInfo()
    {
        var_dump($this->error);
    }
}