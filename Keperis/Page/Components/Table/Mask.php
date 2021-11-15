<?php


namespace Keperis\Page\Components\Table;


use Carbon\Traits\Mutability;

abstract class Mask
{
    /**
     * Array of setting for generate table
     * @var array
     */
    protected $title = [];

    /**
     * Array of setting for generate data by ProvideStructure | Structure
     * @var array
     */
    protected $mask = [];

    /**
     * Setting of create column event_o in table
     * @var array
     */
    protected $action = [];

    public function getTitle($key = null)
    {

        if ($this->isMutator($key, 'title')) {
            return $this->callMutator($this->get($this->title, $key), $key, 'title');
        }
        return $this->get($this->title, $key);
    }

    private function isMutator($key, string $prefix)
    {
        if (!$key) {
            return false;
        }

        $method = 'get' . ucfirst($key) . ucfirst($prefix);
        if (method_exists($this, $method)) {
            return true;
        }
        return false;
    }

    public function callMutator($data, $key, string $prefix)
    {
        $method = 'get' . ucfirst($key) . ucfirst($prefix);
        $data = call_user_func([$this, $method], $data);
        return $data;
    }

    /**
     * @param $array
     * @param null $key
     * @return array|mixed
     */
    protected function get($array, $key = null)
    {
        if (!$key) {
            return $array;
        }

        if (!array_key_exists($key, $array)) {
            return [];
        }
        return $array[$key];
    }

    public function getMask($key = null)
    {
        if ($this->isMutator($key, 'mask')) {
            return $this->callMutator($this->get($this->mask, $key), $key, 'mask');
        }
        return $this->get($this->mask, $key);
    }

    public function getAction($key = null)
    {
        if ($this->isMutator($key, 'action')) {
            return $this->callMutator($this->get($this->action, $key), $key, 'action');
        }
        return $this->get($this->action, $key);
    }


    protected function setMaskGetValues($key, $values)
    {
        $name = key($this->mask[$key]);

        $this->mask[$key][$name]['get'] = $values;
    }

}