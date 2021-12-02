<?php

namespace Keperis\Page\Table;

use Keperis\HasEvent;

use Keperis\Page\Component;
use Keperis\Page\Table\Entity\TableEntity;
use Keperis\Page\Table\Entity\TBody;
use Keperis\Page\Table\Entity\TFooter;
use Keperis\Page\Table\Entity\THead;
use Symfony\Component\EventDispatcher\EventDispatcher;


class Table implements Component
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

    /**
     * The event dispatcher instance.
     *
     * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * Check if booted class
     * @var bool
     */
    protected static $isBooted = false;

    public function __construct(array $title, ?array $row = null, ?array $action = null)
    {
        $this->action = $action;
        $this->title = $title;
        $this->row = $row;


        $this->dispatcher = new EventDispatcher();
    }

    /**
     * Register listener after create THEAD
     * @param callable $listener
     * @return $this
     */
    public function theadListener(callable $listener)
    {

        $this->dispatcher->addListener(THead::class, $listener);

        return $this;
    }

    /**
     * Register listener after create TBODY
     * @param callable $listener
     * @return $this
     */
    public function tbodyListener(callable $listener)
    {
        $this->dispatcher->addListener(TBody::class, $listener);

        return $this;
    }

    /**
     * Register listener after create TFOOTER
     * @param callable $listener
     * @return $this
     */
    public function tfooterListener(callable $listener)
    {
        $this->dispatcher->addListener(TFooter::class, $listener);

        return $this;
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
    public function render(): string
    {
        $result = [];

        $components = $this->getComponents();
        foreach ($components as &$component) {
            /**
             * @var $clone Table
             * @var $component TableEntity
             */
            $clone = $component->register($this);

            if($clone->dispatcher->hasListeners(get_class($component))){
                $component = $clone->dispatcher->dispatch($component, get_class($component));
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
            $temp = \Keperis\View\ViewFactory::makeWithOwnValidator(new View(),
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


}
