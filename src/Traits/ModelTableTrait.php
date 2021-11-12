<?php


namespace Keperis\Traits;


use Keperis\Core\Page\PageCreateButtons;
use Keperis\Core\Page\PageCreatePaginator;
use Keperis\Core\Page\PageCreator;
use Keperis\Core\Page\ProvideTable;
use Keperis\Core\provideExport;
use Keperis\Interfaces\Buttons;
use Keperis\Structure\ProvideFilter;

trait ModelTableTrait
{

    protected $export;
    /**
     * @var ProvideTable
     */
    protected $provideTable;

    /**
     * @var Buttons
     */
    protected $button;

    /**
     * @var PageCreatePaginator
     */
    protected $navigation;

    private $seeded = false;
    private $callback = [];

    /**
     * @var null|string
     */
    protected $exportTitle = null;
    /**
     * @var bool|string
     */
    protected $exportRole;

    public function addCallback(callable $callback)
    {
        $this->callback[] = $callback;

        return $this;
    }


    /**
     * @param string|bool $role
     * @return $this
     */
    public function setExportRole($role)
    {
        $this->exportRole = $role;
        return $this;
    }

    /**
     * @param string $title
     * @return $this
     */
    public function setExportTitle(string $title)
    {
        $this->exportTitle = $title;
        return $this;
    }

    public function addAsEventCallback()
    {
        if (!$this->callback) {
            throw new \RuntimeException("Undefiend callback to add");
        }
        $callback = end($this->callback);
        return $this->addExportCallback($callback);
    }

    public function addExportCallback(callable $callback)
    {
        provideExport::add($callback);
        return $this;
    }

    protected function seedCommand()
    {
        if ($this->seeded != false) {
            return;
        }
        $this->provideTable = new ProvideTable();

        $this->button = new PageCreateButtons();

        $this->navigation = new PageCreatePaginator();

        $this->tableCallback();

        $this->seeded = true;
    }

    protected function tableCallback()
    {
        if (!$this->callback) {
            return;
        }
        foreach ($this->callback as $callback) {
            $this->provideTable->add($callback);
        }

        return;
    }

    protected function initPageSetting(string $key, &$data, $title)
    {

        if ($this->exportRole) {
            PageCreator::$export_allow = is_bool($this->exportRole) ? $this->exportRole : role_check($this->exportRole);
        }

        if ($this->exportTitle && !PageCreator::$export_title) {
            PageCreator::$export_title = $this->exportTitle;
        }


        if (!PageCreator::$row_init) {
            PageCreator::$row_init = $this->getCallbackRow($key);
        }

        PageCreator::$title = $title;

        PageCreator::init($data, $this->getCallbackRequest($key));

        if (container()->request->isXhr()) {
            PageCreator::$script = false;
        }
    }

    /**
     * @param string $key
     * @return \Closure|null
     * Callback mutator for rendering table callback
     */
    protected function getCallbackRow(string $key)
    {
        if (!$key) {
            throw new \RuntimeException("Undefined key for get callback");
        }

        $method = "get" . ucfirst($key) . "Callback";
        if (method_exists($this, $method)) {
            return function ($row) use ($method) {
                return call_user_func([$this, $method], $row);
            };
        }
        return null;

    }

    protected function getCallbackRequest(string $key)
    {
        if (!$key) {
            throw new \RuntimeException("Undefined key for get callback");
        }

        $method = "get" . ucfirst($key) . "RequestCallback";
        if (method_exists($this, $method)) {
            return function (&$data, &$filter, &$get) use ($method) {
                return $this->$method($data, $filter, $get);
            };
        }
        return null;

    }

    protected function createTableAsArray(PageCreator $creator)
    {
        $result = [
            'table'     => $creator->execute($this->provideTable),
            'button'    => $creator->execute($this->button),
            'paginator' => $creator->execute($this->navigation),
        ];
        return $result;
    }

    protected function createTable(PageCreator $creator)
    {

        $result = $creator->execute($this->button) .
            $creator->execute($this->provideTable) .
            $creator->execute($this->navigation);

        return $result;
    }

    protected function setSettingPaginator($setting = [])
    {
        if (!$setting) {
            $setting = setting()->get('page')['paginator'];
        }
        $this->navigation->setSetting($setting);
    }

    protected function setButtonSetting(string $key)
    {
        $setting = $this->getButtongSetting($key);
        if (!$setting) {
            return;
        }
        foreach ($setting as $key => $value) {
            $this->button->addSetting($key, $value);
        }
    }

    /**
     * @param string $key
     * @return |null
     */
    protected function getButtongSetting(string $key)
    {
        if (!$key) {
            throw new \RuntimeException("Undefined key for get callback");
        }

        $method = "get" . ucfirst($key) . "ButtonCallback";
        if (method_exists($this, $method)) {
            return $this->$method();

        }
        return null;
    }

    protected function setActionAndTitle($action, $title)
    {


        $this->provideTable->setting(setting()->get('page')['table'], $title, new ProvideFilter(), $action);
    }


}