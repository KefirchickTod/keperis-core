<?php


namespace Keperis\Eloquent\Concerns;


use Keperis\Core\Page\PageCreator;
use Keperis\Interfaces\ProvideMask as ProvideMaskInterface;
use Keperis\Traits\ModelTableTrait;

trait MaskConcerns
{

    use ModelTableTrait;

    /**
     * @var ProvideMaskInterface
     */
    protected $mask;

    /**
     * Using for render table component
     * @var null|string
     */
    protected $maskKey = null;

    public function table(string $key, string $titleKey = null, string $actionKey = null)
    {


        [$dataStructure, $title, $action] = [
            $this->mask()->getMask($key),
            $this->mask()->getTitle($titleKey ?: $key),
            $this->mask()->getAction($actionKey ?: $key),
        ];

        $this->seedCommand();

        $this->initPageSetting($key, $dataStructure, $title);
        $this->setSettingPaginator();
        $this->setActionAndTitle($action, $title);
        $this->setButtonSetting($key);


        $page = new PageCreator(structure(), $dataStructure);


        if (container()->request->isXhr()) {
            return $this->createTableAsArray($page);
        }
        return $this->createTable($page);
    }

    /**
     * @return ProvideMaskInterface
     */
    public function mask()
    {
        if (!$this->mask) {
            throw new \RuntimeException("Undefined variable mask");
        }

        if (!is_object($this->mask)) {
            $this->mask = new $this->mask;
        }

        if (!$this->mask instanceof ProvideMaskInterface) {
            throw new \TypeError("Error type for mask");
        }

        return $this->mask;
    }

    public function withMask($mask)
    {
        if (is_object($mask)) {

            if (!class_exists($mask)) {
                throw new \InvalidArgumentException("Invalid mask name");
            }

            $mask = new $mask;
        }

        if (!$mask instanceof ProvideMaskInterface) {
            throw new \TypeError("Error type for mask");
        }

        $this->mask = $mask;

        return $this;
    }


    public function getMaskKey()
    {
        return $this->maskKey;
    }

}
