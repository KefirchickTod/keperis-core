<?php


namespace src\Eloquent\Provide\Processes;


use Exception;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\QueryException;
use src\Eloquent\Provide\ProvideStructure;
use src\Eloquent\Provide\StructureCollection;
use Symfony\Component\Console\Helper\DebugFormatterHelper;

abstract class ProvideStructureProcessor implements ProcessorInterface
{


    /**
     * @var StructureCollection
     */
    protected $collection;

    protected $id = true;


    protected $builder;


    private $joinBinding = [];

    protected function createBuilderIfNotExist()
    {
        if (!$this->builder) {
            $this->builder = new Builder(container()->connection);
            $this->builder->from($this->collection->getController()->getTableName());
        }

        return $this->builder;
    }

    public function process(StructureCollection $event)
    {
        $this->collection = $event;

        $this->createBuilderIfNotExist();
    }


    /**
     * @return string
     * @throws Exception
     */
    public function getColumnsRaw(): string
    {
        $columns = [];

        $params = $this->collection->getGet();
        $patterns = $this->collection->getController()->getPattern();

        foreach ($params as $param) {
            if (!array_key_exists($param, $patterns)) {
                throw new Exception("Undefined $param");
                continue;
            }

            $setting = $patterns[$param];

            if (!array_key_exists('select', $setting)) {
                throw new \RuntimeException("Undefined select option $param");
            }


            if (array_key_exists('join', $setting)) {
                $join = is_array($setting['join']) ? implode(PHP_EOL, $setting['join']) : $setting['join'];
                continue;
            }


            $columns[] = $this->replaceTemplates($setting) . (array_key_exists('as', $setting) ? " AS " . $setting['as'] : "");

        }
        return implode(', ' . PHP_EOL, $columns);
    }

    public function getJoining()
    {

        $join = $this->collection->getJoin();

        if (!$join) {
            return [];
        }


        foreach ($join as $value) {
            /** @var $value ProvideStructure */
            var_dump($value);
            exit;
        }
        return $join;
    }

    public function query()
    {

        $query = $this->createBuilderIfNotExist();

        $query
            ->selectRaw($this->getColumnsRaw());

        if ($this->collection->hasSetting('where')) {

            $query->whereRaw($this->parsePattern($this->collection->getSetting('where')));
        }

        $query->orderByRaw($this->order())->groupBy($this->collection->getSetting('group'));




        return $query->toSql();
    }


    protected function order()
    {
        $result = '';
        $value = $this->collection->getSetting('order');

        if (!$value) {
            return $result;
        }

        $pattern = $this->collection->getController()->getPattern();

        foreach ($pattern as $key => $valueOrder) {
            if ($key == $value || $value == "a_$key") {
                $needReplace = !isset($valueOrder['order']);
                //var_dump($valueOrder);
                $descOrAsc = (preg_match('~a_~', $value)) ? "ASC" : "DESC";
                $result = ($needReplace ? $valueOrder['select'] : $valueOrder['as']) . " $descOrAsc ";

            }
        }
        if (!$result) {
            $result = "{$value}";
            if (preg_match("~BY~", $this->query['order'] ?? '')) {
                $result = ", {$value}";
            }
        }
        //var_dump($result, $value);exit;
        return $result;

    }


    protected function parsePattern($pattern, $checkId = true)
    {
        if (!$pattern) {
            return '';
        }

        $controllerPattern = $this->collection->getController()->getPattern();

        if ($pattern = explode(' ', $pattern)) {
            $pattern = array_diff($pattern, ['', null, false]);

            $parsing = array_map(function ($val) {
                if ($this->checkOnId($val) === false) {

                    return $controllerPattern[$val]['select'] ?? $val;
                }
                return $this->checkOnId($val);
            }, $pattern);

            if ($parsing) {
                $parsing = join(' ', $parsing);
            }

            return preg_replace('/! =/', '!=', $parsing);
        }

        return $controllerPattern[$pattern]
            ? ($this->checkOnId($pattern) === false ? ( $controllerPattern[$pattern]['select'] ?? $pattern) : $this->checkOnId($pattern))
            : $pattern;

    }

    private function checkOnId($value)
    {
        if ($value === 'id') {

            return explode('AS', trim($this->patterns[$value]))[0];
        }
        return false;
    }


    private function replaceTemplates($value)
    {

        if (!is_array($value)) {
            return $value;
        }
        if (isset($value['templates'])) {
            return preg_replace("~%_select_%~", $value['select'], $value['templates']) ?: $value['select'];
        }
        return $value['select'];
    }


}