<?php

namespace Keperis\Page\FilterData;

use Keperis\Eloquent\Provide\StructureCollection;

use Keperis\Structure\ProvideStructures;

abstract class TransformationLogic
{
    /**
     * @var StructureCollection|null
     */
    protected $structure;

    public function __construct(?StructureCollection $structureCollection = null)
    {
        $this->structure = $structureCollection;;
    }

    /**
     * Search all values for type
     * @param string $type
     * @return array
     */
    protected function findValuesByType(string $type = 'string')
    {
        $controllers = $this->structure->getControllers();
        $values = [];
        foreach ($controllers as $controller) {
            /** @param $controller ProvideStructures */
            $values = array_merge($values, $controller->getAllWhereType($type));
        }
        return $values;
    }

    /**
     * Search pattern (sqlSetting) in all controllers structre
     * @param string $key
     * @return mixed|null
     */
    protected function findPattern(string $key)
    {
        $controllers = $this->structure->getControllers();
        foreach ($controllers as $controller) {
            if ($pattern = $controller->getPattern($key)) {
                return $pattern['select'];
            }
        }
        throw new \InvalidArgumentException("Cant find pattern by key: {$key}");
    }

    /**
     * @param $value
     * @return string
     */
    protected function replaceTemplates($value)
    {

        if (!is_array($value)) {
            return $value;
        }
        if ($this->hasTemplate($value)) {
            return preg_replace("~%_select_%~", $value['select'], $value['templates']) ?: $value['select'];
        }
        return $value['select'];
    }

    /**
     * @param array $values
     * @return bool
     */
    protected function hasTemplate(array $values)
    {
        return array_key_exists('templates', $values);
    }

    /**
     * Function for create this object in method in  middleware
     * Using as fat feach
     * @param StructureCollection $structureCollection
     * @return $this
     */
    public static function createFromMiddleware(StructureCollection $structureCollection)
    {
        return new static($structureCollection);
    }

    /**
     * @param $changer ProvideCreate
     * @param $uriBody array
     * @param $next callable
     * @return mixed
     */
    public abstract function __invoke($changer, $uriBody, $next);
}
