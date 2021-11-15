<?php


namespace Keperis\Eloquent\Provide;


use Keperis\Collection;
use Keperis\Structure\ProvideStructures;
use TypeError;

class StructureCollection extends Collection
{


    /**
     * @var Collection
     */
    protected $structure;

    /**
     * @var array
     */
    protected $setting;

    /**
     * @var array
     */
    protected $get = [];

    /**
     * @var ProvideStructures
     */
    protected $controller;

    /**
     * @var string
     */
    protected $key;
    /**
     * @var array|mixed|null
     */
    private $joined = null;

    public function __construct(string $key, array $structure)
    {
        $this->key = $key;


        parent::__construct($structure);
    }


    public function getJoin()
    {
        if (!$this->hasSetting('join')) {
            return [];
        }

        if (!is_null($this->joined)) {
            return $this->joined;
        }

        $this->joined = $this->getSetting('join');

        $this->joined = array_map(function ($val, $key) {
            if (!is_array($val)) {
                return $val;
            }

            return new StructureCollection($key, $val);
        }, $this->joined, array_keys($this->joined));

        return $this->joined;
    }

    /**
     * @param $key string
     * @return mixed|null
     */
    public function getSetting(string $key)
    {

        if ($this->setting) {
            return $this->setting[$key] ?? null;
        }

        $this->setting = $this->get('setting', []);


        return $this->setting[$key] ?? [];
    }

    /**
     * @param array $setting
     * @return static
     */
    public function setSetting(array $setting): self
    {
        $this->setting = $setting;
        return $this;
    }

    public function hasSetting($key)
    {
        return array_key_exists($key, $this->get('setting', []));
    }

    /**
     * @return array
     */
    public function getGet(): array
    {
        $get = $this->get('get', []);
        if (!$get) {
            $get = ['*'];
        }


        $unique = array_unique($get);

        $duplicates = array_diff_assoc($get, $unique);

        if ($duplicates) {
            throw new \Exception("Array have duplication : " . implode(', ', $duplicates));
        }

        $this->get = $unique;

        return $unique;
    }

    /**
     * @param array $get
     * @return static
     */
    public function setGet(array $get): self
    {
        $this->get = $get;
        return $this;
    }

    /**
     * @return ProvideStructures
     */
    public function getController(): ProvideStructures
    {

        if (!$this->controller) {
            $class = $this->get('class');



            if (!class_exists($class)) {
                throw new \InvalidArgumentException("Undefined $class");
            }

            $this->controller = new $class;
        }
        return $this->controller;
    }


    /**
     * Get main controller and joined
     * @return array
     */
    public function getControllers(): array
    {
        if (!$this->hasSetting('join')) {
            return [$this->getController()];
        }

        $joined = $this->getJoin();
        $joined[] = $this;

        foreach ($joined as $collection) {
            if (!$collection instanceof StructureCollection) {
                throw new TypeError("Joined structure collection must be a StructureCollection not " . gettype($collection));
            }
            $controller = $collection->getController();
            $controllers[get_class($controller)] = $controller;
        }

        $controllers = array_values($controllers);

        return $controllers;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }


    public function hasInGetParam($key): bool
    {
        if (!$this->has('get')) {
            return false;
        }

        $get = $this->getGet();

        return in_array($key, $get);
    }


}
