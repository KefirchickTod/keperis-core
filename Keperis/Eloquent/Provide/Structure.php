<?php


namespace Keperis\Eloquent\Provide;


use Keperis\Eloquent\Provide\Exception\StructureValidatorException;

class Structure implements StructureInterface
{

    /**
     * Key (name) structure
     * @var null|string
     */
    protected $key = null;

    /**
     * Body of structure
     * @var null|ProvideStructure
     */
    protected $structure = null;

    /**
     * @var array
     */
    public static $resolved = [];

    /**
     * @param array $structure
     * @param string $key
     * @return static
     */
    public function set(array $structure, string $key)
    {
        if (!StructureValidate::checkValidate($structure)) {
            throw new StructureValidatorException(sprintf("Error of validate structure with key [%s]", $key));
        }

        $clone = clone $this;

        $clone->structure = new ProvideStructure(new StructureCollection($key, $structure));
        $clone->key = $key;

        return $clone;
    }



    public function __clone()
    {
        $this->structure = null;
        $this->key = null;
    }

    /**
     * @param string|null $key
     * @return mixed
     */
    public function get(?string $key = null)
    {
        $key = $key ?: $this->key;

        if (array_key_exists($key, self::$resolved)) {
            return self::$resolved[$key];
        }

        self::$resolved[$key] = [];
        return [];

    }
}