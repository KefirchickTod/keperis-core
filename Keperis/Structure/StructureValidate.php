<?php

namespace Keperis\Structure;

use Keperis\Structure\Exception\StructureValidatorException;

class StructureValidate implements Validator
{

    /**
     * @var array
     */
    private $structure;

    public function __construct(array $structure)
    {
        $this->structure = $structure;
    }

    /**
     * Check if exist class and check instance
     * @return bool
     */
    protected function hasClass(): bool
    {

        $class = $this->structure['class'] ?? null;

        if (!$class) {
            throw new StructureValidatorException("Undefined class for structure");
        }

        if (is_object($class)) {
            return true;
        }

        if (!class_exists($class)) {
            throw new StructureValidatorException(sprintf("Cant find class [%s]", $class));
        }

        return true;

    }

    /**
     * @return bool
     */
    protected function checkMethods()
    {
        $method = $this->structure['get'] ?? null;

        if (!$method) {
            return false;
        }

        if (!is_array($method)) {
            throw new StructureValidatorException("Get param cant be an non array");
        }

        return true;

    }

    /**
     * Check all join
     * @return bool
     */
    protected function checkJoins()
    {
        $joins = $this->structure['setting']['join'] ?? null;

        if (!$joins) {
            return true;
        }

        foreach ($joins as $join) {

            if (!$join) {
                return false;
            }

            if (!array_key_exists('on', $join)) {
                throw new StructureValidatorException(sprintf("For create joined table need a tag "));
            }

            if (!static::checkValidate($join)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param false $distinct
     * @return bool
     */
    public function validate($distinct = false): bool
    {

        if (!$this->hasClass()) {
            return false;
        }

        try {
            if (!$this->checkMethods()) {
                return false;
            }
        } catch (StructureValidatorException $exception) {
            if ($distinct === true) {
                return false;
            }
        }

        if (!$this->checkJoins()) {
            return false;
        }

        return true;
    }

    public static function checkValidate($structure): bool
    {
        $static = new static($structure);

        return $static->validate();
    }
}