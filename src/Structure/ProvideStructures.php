<?php


namespace src\Structure;


use src\Collection;

abstract class ProvideStructures extends Collection
{

    const namespace = 'App\src\Structure\ProvideStructures\\';
    /**
     * List of ignore column in property sql setting
     * @var string[]
     */
    public static $exception = ['table', 'id', 'prefix', 'size'];
    /**
     * List of default select settings
     * @var array
     */
    protected static $guard = [
        'select',
        'as',
        'type'
    ];


    /**
     * List of additional select setting
     * @var array
     */
    protected static $additional = [
        'join',
        'templates',
    ];

    /**
     * @var string
     */
    protected $name;

    /**
     * Array of setting slq
     * @var array
     */
    protected $sqlSetting = [];

    /**
     * @var string
     */
    protected $factoryName;

    /**
     * @var array
     */
    protected $pattern = [];

    /**
     * @var array
     */
    protected $columns;

    function __construct(array $item = [])
    {
        $item = $this->sqlSetting;
        parent::__construct($item);

        $this->columns = container()->connection->getSchemaBuilder()->getColumnListing($this->getOriginTableName());
    }


    /**
     * Retrun name for cache
     * @return string
     */
    public function getFactoryName()
    {
        return $this->factoryName;
    }

    /**
     * @return string
     */
    public function getOriginTableName()
    {
        if(!$this->has('table')){
            throw new \Error("Cant find table in Provide Structure (setting)");
        }
        return $this->get('table');
    }


    /**
     * Get all keys from sql setting where inner setting type is searching type
     * @param string $type
     * @return array
     */
    public function getAllWhereType(string $type)
    {
        $result = [];
        foreach ($this->sqlSetting as $name => $value) {
            if (!is_array($value)) {
                continue;
            }
            if (!array_key_exists('type', $value)) {
                continue;
            }

            if ($value['type'] === $type) {
                $result = array_merge($result, [$name => $value]);
            }
        }

        return $result;

    }

    public static function getAll($structure = true)
    {
        $static = new static();
        $get = array_keys($static->getPattern());
        foreach ($get as $key => $value) {
            if (in_array($value, self::$exception)) {
                unset($get[$key]);
            }
        }
        structure()->delete('getAll');
        return $structure === true ? [
            'getAll' =>
                [
                    'get' => $get,
                    'class' => static::class,
                ],
        ] : $get;


    }

    public function getPattern($key = false)
    {

        if ($key == false) {
            return $this->all();
        }
        return $this->get($key);
    }

    public function getTableName(): string
    {
        if (!isset($this->name)) {
            $this->name = $this->getPattern('table');
        }

        return $this->name;
    }

    public function getTemplate($key = null)
    {
        return $this->pattern[$key] ?? $this->pattern;
    }


    public function historyPatternGet(): array
    {
        return [
            $this->getPattern('historyData'),
            $this->getPattern('source'),
            $this->getPattern('historyDescription'),
            $this->getPattern('historyAuthor'),
        ];
    }

}
