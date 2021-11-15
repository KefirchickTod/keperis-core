<?php

namespace Keperis\Page\Components;

use Keperis\Interfaces\ProvideMask;
use Keperis\Page\Component;
use Keperis\Page\Components\Table\TableEntity;
use Keperis\Page\Components\Table\TBody;
use Keperis\Page\Components\Table\TFooter;
use Keperis\Page\Components\Table\THead;
use Keperis\Page\Components\Table\View\TableViewValidator;

class TableComponent extends Component
{

    /**
     * If null using default table template
     * //todo bugs with dir
     * @var string|null
     */
    public static $resourceTemplate = null;

    /**
     * Left|Right action buttons
     * @var array|null
     */
    private $action = null;
    /**
     * Data column for table body (result of db query)
     * @var array|null
     */
    private $row = null;
    /**
     * Table header <thead>
     * @var array
     */
    private $title;

    //private static $resolve = [];

    public function __construct(array $title, ?array $row = null, ?array $action = null)
    {
        $this->action = $action;
        $this->title = $title;
        $this->row = $row;
    }


    /**
     * Get components for rendering and dispatching
     * @return array
     */
    protected function getComponents()
    {
        return [
            $this->thead(),
            $this->tbody(),
            $this->tfooter(),
        ];
    }

    /**
     * Render table after process component
     * @return string
     */
    public function table()
    {
        $result = [];

        $components = $this->getComponents();
        foreach ($components as &$component) {
            /**
             * @var $clone TableComponent
             * @var $component TableEntity
             */
            $clone = $component->register($this);

            if ($clone->hasEvent(get_class($component))) {
                $component = $clone->dispatch($component);
            }
            $result[] = $component->render();
        }


        $content = $this->replaceByTemplate(implode(PHP_EOL, $result));

        return $content;
    }

    /**
     * Render by template table component
     * @param string $content
     * @return string
     */
    protected function replaceByTemplate(string $content): string
    {

        $name = "table";
        if (!is_null(static::$resourceTemplate)) {
            $name = static::$resourceTemplate;
        };
        try {
            $temp = \Keperis\View\ViewFactory::makeWithOwnValidator(new TableViewValidator(),
                $name)->withDir(__DIR__ . '/resource')->render();
        } catch (\Exception $exception) {
            error_log($exception->getMessage());
            return "<table>{$content}</table>";
        }

        $content = preg_replace([
            "/{%content%}/",
        ], $content, $temp);


        return $content;
    }

    /**
     * Create tbody
     * @return TBody
     */
    public function tbody()
    {
        return new TBody($this->row, $this->title, $this->action);
    }

    /**
     * Create thead
     * @return THead
     */
    public function thead()
    {
        return new THead($this->title);
    }

    /**
     * Create tfooter
     * @return TFooter
     */
    public function tfooter()
    {
        return new TFooter();
    }


    /**
     * Create static object with parsing data
     * @param ProvideMask $mask
     * @param string $key
     * @return static
     */
    public static function createByMask(ProvideMask $mask, string $key)
    {
        $title = $mask->getTitle($key);

        $data = $mask->getMask($key);
        $row = structure()->set($data)->get(key($data));


        $action = $mask->getAction($key);

        return new static($title, $row, $action);
    }


    /**
     * @param EloquentModelTable $modelTable
     * @return static
     */
    public static function createByModel(EloquentModelTable $modelTable)
    {
        return static::createByMask($modelTable->mask(), $modelTable->getMaskKey());
    }


    public static function createByStructure(array $structure, array $title, ?array $action = [])
    {
        $row = structure()->set($structure)->get(key($structure));

        return new static($title, $row, $action);
    }



}
