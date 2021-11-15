<?php

namespace Keperis\Eloquent\Provide;

use Keperis\Collection;

abstract class ProvideTemplate
{
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
        'type',
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
     * Templates for render queries
     * @var array
     */
    protected $temp = [];


    public function __construct()
    {
        $this->temp = collect($this->temp);
    }

    /**
     * Get resolve name for quick copy obj
     * @return string
     */
    abstract public function getResolveName(): string;

    /**
     * @return string
     */
    public function getOriginTableName()
    {
        if (!$this->temp->has('table')) {
            throw new \Error("Cant find table in Provide Structure (setting)");
        }
        return $this->temp->get('table');
    }

    /**
     * Get all keys from sql setting where inner setting type is searching type
     * @param string $type
     * @return array
     */
    public function getAllWhereType(string $type)
    {
        $result = [];
        $tmp = $this->temp->toArray();

        foreach ($tmp as $name => $value) {
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


}