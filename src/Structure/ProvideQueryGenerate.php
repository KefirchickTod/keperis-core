<?php


namespace src\Structure;

use src\Http\Request;

class ProvideQueryGenerate
{

    private $query =
        [
            'select' => "",
            'from'   => '',
            'join'   => "",
            'where'  => '',
            'group'  => '',
            'order'  => '',
            'limit'  => '',
        ];

    private $get;

    private $setting;

    private $pattern;


    private $parser;

    private $filter = [
        'filter' => null,
        'length' => 10,
        'search' => '-1',
        'page' => '',
        'event_outer_id' => '',
        'grant_outer_id' => '',
        'export' => '',

    ];

    private $template = null;

    function __construct(ProvideStructures $patternObj, $getSetting, $otherSetting, $pattern = null)
    {

        $this->query['from'] = $patternObj->getTableName();
        $this->pattern = $patternObj->getPattern();
        $this->setting = $otherSetting;
        $this->get = $getSetting;
        $this->parser = new ProvideSettingParse($this->pattern);
        $this->template = $patternObj->getTemplate();

    }


    public function getParsedQuery() : array {
        $result = [];
        foreach ($this->parse(!true)->query as $name => $value) {
            if ($value !== '' && !empty(trim($value))) {
                $name = strtoupper($name);
                if ($name === 'JOIN') {
                    addToArray($result, $name, ' ' . $value);
                } else {
                    addToArray($result, $name, $name === 'GROUP' ? $name . ' BY ' . $value : $name . ' ' . $value);

                }
            }
        }
        return  $result;
    }

    public function getQuery(bool $array = false, $strict = true)
    {
        $result = [];

        $template = $this->prepreTemplate();
        if($template === true && $array === false && $strict === true){
            return $this->query;
        }
        foreach ($this->parse(!$array)->query as $name => $value) {
            if ($value !== '' && !empty(trim($value))) {
                $name = strtoupper($name);
                if ($name === 'JOIN') {
                    addToArray($result, $name, ' ' . $value);
                } else {
                    addToArray($result, $name, $name === 'GROUP' ? $name . ' BY ' . $value : $name . ' ' . $value);

                }
            }
        }

        return $array == false ? join(' ', $result) : $this->query;
    }

    protected function prepreTemplate()
    {
        if(!isset($this->setting['template'])){
            return false;
        }
        /** @var  $request Request */
        $request = container()->get('request');
        $get = $request->getUri()->getParseQuery();

        $get = array_intersect_key($get, $this->filter);
        if (!empty($get)) {
            foreach ($get as $key => $value){
                if(isset($this->filter[$key]) && $this->filter[$key] !== $value){
                    return false;
                }
            }
        }

        if(!$get){

            $this->query = $this->template[$this->setting['template'] ?? ''] ?? $this->query;

            return true;
        }
        return false;
    }

    protected function parse($getId = true)
    {
        $select = $this->creatSelect($getId);
        if ($select) {
            addToArray($this->query, 'select', join(', ', $select));
        }
        if (is_array($this->setting)) {
            foreach (array_keys($this->setting) as $fun) {
                if (isset($this->query[$fun]) && method_exists($this, $fun)) {
                    $exit = $this->$fun();
                    $exit = is_array($exit) ? implode(' ', $exit) : $exit;
                    addToArray($this->query, $fun, $exit);
                }
            }
        }
        return $this;
    }

    private function creatSelect($getId = true)
    {
        $select = [];
        if ($getId == true && isset($this->pattern['id']) && !in_array('all', $this->get)) {

            $select['id'] = $this->pattern['id'];
        }
        foreach ($this->get as $key => $value) {
            if (in_array($value, array_keys($this->pattern))) {
                $selectSetting = $this->pattern[$value];
                addToArray($select, $key, $this->templates($selectSetting));

                addToArray($select, $key,
                    isset($selectSetting['as']) && !empty($selectSetting['as']) ? ' AS ' . $selectSetting['as'] : '');
                if (isset($selectSetting['join'])) {
                    $join = is_array($selectSetting['join']) ? join(' ',
                        $selectSetting['join']) : $selectSetting['join'];
                    addToArray($this->query, 'join', $join);
                }
            }
        }

        return $select ? array_unique($select) : null;
    }

    /**
     * @param $value
     * @return mixed
     */
    private function templates($value)
    {

        if (!is_array($value)) {
            return $value;
        }
        if (isset($value['templates'])) {
            return preg_replace("~%_select_%~", $value['select'], $value['templates']) ?: $value['select'];
        }
        return $value['select'];
    }

    function __toString(): string
    {
        $result = '';
        foreach ($this->query as $method => $value) {
            $result .= $method . ' ' . $value;
        }
        return $result;
    }

    private function join()
    {

        $structure = structure();
        $structure->set($this->setting['join']);
        $joinQuery = '';
        $saveJoin = [];
        foreach ($this->setting['join'] as $name => $value) {
            $argument = $structure->get($name, true);
            $structure->delete($name);
            $type = isset($value['type_join']) ? $value['type_join'] : 'LEFT';
            $joinQuery .= " $type JOIN " . $argument['from'] . " ON " . $value['on'];
            $this->query['join'] = "$type JOIN " . $argument['from'] . " ON (" . $value['on'] . ') ' . $this->query['join'];
            $saveJoin[] = $argument['join'];
            addToArray($this->query, 'select', ', ' . $argument['select']);
            addToArray($this->query, 'order', ' ' . $argument['order']);

        }
        $this->query['join'] = trim($joinQuery) !== trim($joinQuery) ? $joinQuery . ' ' . $this->query['join'] : $this->query['join'];
        addToArray($this->query, 'join', $saveJoin ? join(' ', $saveJoin) : '');

        return [''];
    }

    private function where()
    {
        return $this->parser->parsePattern($this->setting['where']);
    }

    private function limit()
    {
        return $this->setting['limit'];
    }

    private function order()
    {
        $result = '';
        $value = $this->setting['order'];

        foreach ($this->pattern as $key => $valueOrder) {
            if ($key == $value || $value == "a_$key") {
                $needReplace = !isset($valueOrder['order']);
                //var_dump($valueOrder);
                $descOrAsc = (preg_match('~a_~', $value)) ? "ASC" : "DESC";
                $result = ' BY ' . ($needReplace ? $valueOrder['select'] : $valueOrder['as'] )  . " $descOrAsc ";

            }
        }
        if(!$result){
            $result = " BY {$value}" ;
            if(preg_match("~BY~", $this->query['order'] ?? '')){
                $result = ", {$value}";
            }
        }
        //var_dump($result, $value);exit;
        return $result;

    }

    private function group()
    {

        return $this->parser->parsePattern($this->setting['group']);
    }
}