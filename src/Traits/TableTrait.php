<?php


namespace src\Traits;


use Error;
use src\Container;

use src\Interfaces\Buttons;
use src\Interfaces\Paginator;
use src\View\View;

/**
 * Trait TableTrait
 * @package App\src\Traits
 * @property $structure Structure;
 * @property $dataArray array
 * @property $title
 * @property $filter
 * @property $action
 */
trait TableTrait
{

    /**
     * @var PageCreator
     */
    protected $page;
    /**
     * @var Paginator
     */
    protected $paginator;
    /**
     * @var Buttons
     */
    protected $buttons;

    /**
     * @var ProvideTable
     */
    protected $table;

    public function __construct(Container $container)
    {
        parent::__construct($container);
        if ($this->structure && $this->dataArray) {
            $this->page = new PageCreator($this->structure, $this->dataArray);
        }
        $this->paginator = new PageCreatePaginator();
        $this->buttons = new PageCreateButtons();
        $this->table = new ProvideTable();
    }


    protected function setValue(array $list)
    {
        [$this->title, $this->dataArray, $this->action] = $list;
    }

    protected function init()
    {
        $this->page = $this->getPageCreator();
        $this->paginator = new PageCreatePaginator();
        $this->buttons = new PageCreateButtons();
        $this->table = new ProvideTable();
        //debug(setting());
        $this->paginator->setSetting(setting()->get('page')['paginator']);


    }

    protected function getPageCreator()
    {
        if (!$this->isAttached()) {
            $this->page = new PageCreator($this->structure, $this->dataArray);
        }
        return $this->page;
    }

    protected function isAttached()
    {
        return $this->page instanceof PageCreator;
    }

    /**
     * @return PageCreator
     */
    protected function preRender()
    {
        $this->initTableSetting();
        if (!$this->isAttached()) {
            throw new Error("PageCreator is undefined");
        }
        foreach ([
                     'button'    => $this->buttons,
                     'table'     => $this->table,
                     'paginator' => $this->paginator,
                 ] as $name => $item) {
            if ($item) {
                $this->page->prepare($item);
            }
        }
        return $this->page;
    }

    protected function initTableSetting($setting = null)
    {

        $page = $setting ?: setting()->get('page');

        $private = role_check('user.show.private');
        $this->table->add(function ($data, $setting, $next) use ($private) {
            if (array_key_exists('private', $data)) {
                $private = intval($data['private']) === 0 && !$private;
                if ($private) {
                    foreach (['mobile', 'email'] as $trash) {
                        $value[$trash] = '';
                    }
                }
            }
            foreach ($data as $key => &$value) {
                if (array_key_exists($key, $setting)) {
                    $dictionary = $setting[$key]['dictionary'] ?? false;
                    if ($dictionary) {

                        $value = \container()->dictionary->get(intval($value));

                    }
                }
            }

            return $next($data, $setting);
        });

        $this->table->setting(
            $page['table'] ?? $page,
            $this->title, $this->filter,
            ($this->action ?? []));
    }

    protected function render(View $resource = null, $str = false, $gzip = false, $setting = null)
    {


        $this->initTableSetting($setting);

        $result = [];
        if ($resource) {
            $resource->with([
                'buttons'   => $this->buttons,
                'table'     => $this->table,
                'page'      => $this->page,
                'paginator' => $this->paginator,
            ]);
            return $resource->render();
        }

        if (!$this->isAttached()) {
            throw new Error("PageCreator is undefined");
        }

        if (env('APP_MODE', 'default') === 'xhr') {

            $result = \container()->request->isXhr() ? $this->createContnet($gzip) : [];
        } else {
            $result = $this->createContnet($gzip);
        }

        //var_dump($result);
        return $str === false ? $result : join('', $result);
    }

    private function createContnet($gzip = false)
    {
        $result = [];
        foreach ([
                     'button'    => $this->buttons,
                     'table'     => $this->table,
                     'paginator' => $this->paginator,
                 ] as $name => $item) {
            if ($item) {

                $result[$name] = $gzip ? gzencode($this->page->execute($item)) : $this->page->execute($item);

            }
        }
        return $result;
    }
}