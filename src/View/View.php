<?php


namespace Keperis\View;


use Keperis\View\RenderInterface;

/**
 * Class View
 * @package src\View
 * @author Zahar Pylypchuck
 * @version 0.1
 */
class View
{

    /**
     * @var RenderInterface
     */
    private $render;

    /**
     * @var string File name
     */
    private $file;

    /**
     * @var array Varibles in files
     */
    private $data = [];


    /**
     * View constructor.
     * @param RenderInterface $render
     * @param string $file
     * @param array $data
     */
    public function __construct(RenderInterface $render, string $file, $data = [])
    {
        $this->render = $render;
        $this->file = $file;
        $this->with($data);
    }

    /**
     * @param $data
     * Set values for template
     */
    public function with($data)
    {
        if (!is_array($data)) {
            $data = [$data];
        }
        $this->data = array_merge($data);
    }

    /**
     * @return string

     */
    public function __toString()
    {
        return $this->render();
    }

    /**
     * @return string
     * Return rendered file
     */
    public function render(): string
    {
        return $this->render->render($this->file, $this->getValues());
    }

    /**
     * Set global dir
     * @param $dir
     * @return $this
     */
    public function withDir($dir){

        $this->file = "$dir.{$this->file}";
        return $this;
    }

    public function getValue(string $key, $default = null){
        return $this->data[$key] ?? $default;
    }

    public function getValues(){
        return $this->data;
    }


}
