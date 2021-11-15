<?php

namespace Keperis\Eloquent\Provide;


use Keperis\Eloquent\Provide\Exception\ProvideTemplateException;
use Keperis\Eloquent\Provide\Template\ProvideTemplateInterface;

abstract class ProvideTemplate implements ProvideTemplateInterface
{
    /**
     * List of ignore column in property sql setting
     * @var string[]
     */
    public static $exception = ['table', 'id', 'size'];

    /**
     * List of default select settings
     * @var array
     */
    protected static $guard = [
        'select',
        'as',
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

    public function convertTemplate($template)
    {

        $select = is_array($template['select']) ? $template['select'] : [$template['select']];

        if (array_key_exists('templates', $template)) {

            if (sizeof($select) > 1) {

                foreach ($select as $i => $value){
                    $template['templates'] = preg_replace("/%_select_{$i}_%/", $select[$i], $template['templates']);
                }


            } else {
                $template['template'] = preg_replace('/%_select_%/', $select, $template['templates']);
            }
        }

        return $template;

    }

    /**
     * Get template if invalid format throw exception
     * @param string $key
     * @return mixed
     */
    public function getTemplate(string $key)
    {
        if (!$this->temp->has($key)) {
            throw new ProvideTemplateException(sprintf("Invalid key [%s] for find template ", $key));
        }

        $template = $this->temp->get($key);

        if (in_array($key, self::$exception)) {
            return $template;
        }

        foreach (self::$guard as $item) {
            if (!array_key_exists($item, $template)) {
                throw new \RuntimeException(sprintf("Get invalid template block and cant find item [%s]", $item));
            }
        }

        return $template;
    }


    /**
     * Get all templates from class
     * @param string ...$keys
     * @return array
     */
    public function getTemplates(...$keys)
    {
        if (!$keys) {
            return $this->temp->toArray();
        }

        $result = [];
        foreach ($keys as $key) {

            array_push($result, $this->getTemplate($key));
        }
        return $result;
    }

}