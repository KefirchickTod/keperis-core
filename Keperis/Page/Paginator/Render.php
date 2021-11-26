<?php

namespace Keperis\Page\Paginator;

use Illuminate\Database\Query\Builder;
use Keperis\Page\Component;
use Keperis\View\ViewFactory;

class Render implements Component
{

    /**
     * @var Paginator
     */
    protected $paginator;

    /**
     * @var string
     */
    public static $defaultTemplate = 'paginator';

    /**
     * @var null|string
     */
    public static $defaultTemplateDir = null;

    public function __construct(Paginator $paginator)
    {
        $this->paginator = $paginator;

        if (is_null(static::$defaultTemplateDir)) {
            static::$defaultTemplateDir = __DIR__ . '/resource';
        }
    }

    /**
     * @return \Keperis\View\View
     */
    protected function getView(): \Keperis\View\View
    {
        return ViewFactory::makeWithOwnValidator(new View(),
            static::$defaultTemplate)->withDir(static::$defaultTemplateDir);
    }



    /**
     * @return string
     */
    public function render(): string
    {
        $paginator = $this->paginator->paginate();

        $view = $this->getView();

        $view->with(compact('paginator'));


        return $view->render();

    }
}
