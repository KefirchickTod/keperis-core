<?php


namespace Keperis\Structure;



class ProvideRegister
{
    /**
     * @var int;
     */
    public static $id;
    protected static $data = [];
    protected static $queryArray = [];
    protected static $result = [];
    protected static $userId = [];

    protected static $foundClass = [];

    public static function set($key, $value, $query = false)
    {
        if ($query != false) {
            self::$queryArray[$key] = $query;
        } else {
            self::$data[$key] = $value;
        }
    }

    public static function get($key, $query = false)
    {
        if ($query != false) {
            return isset(self::$queryArray[$key]) ? self::$data[$key] : null;
        }
        return isset(self::$data[$key]) ? self::$data[$key] : null;
    }

    public static function removeAll()
    {
        self::$data = [];
        self::$queryArray = [];
    }

    public static function removeData($key, $query = false)
    {
        if ($query != false) {
            if (array_key_exists($key, self::$queryArray)) {
                unset(self::$queryArray[$key]);
            }
        } else {
            if (array_key_exists($key, self::$data)) {
                unset(self::$data[$key]);
            }
        }
    }

    public static function setFoundClasses($name, $value)
    {
        if (!isset(self::$foundClass[$name])) {
            self::$foundClass[$name] = $value;
        }

    }

    public static function getFoundClasses($name = null)
    {
        if ($name) {
            return isset(self::$foundClass[$name]) ? self::$foundClass[$name] : null;
        }
        return self::$foundClass;
    }
}