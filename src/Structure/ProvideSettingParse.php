<?php


namespace Keperis\Structure;


class ProvideSettingParse
{


    public $saveInput = [];
    private $patterns;
    private $input;

    function __construct(array $patterns = null, $input = null)
    {
        $this->patterns = $patterns;
        if ($input) {

            $this->input = $input;
        }
    }

    public function getSaveInput()
    {
        return $this->saveInput;
    }

    public function setPatterns(array $pattern)
    {
        $this->patterns = $pattern;
        return $this;
    }

    private function reloadfun($value, $checkId = true)
    {


        if (memory_get_usage() > 3140848) {
            return (new ProvideSettingParse($this->patterns, $value))->parsePattern();
        }
        return $this->parsePattern($value, $checkId);
    }

    public function parsePattern($input = null, $checkId = true)
    {
        if (!$this->patterns) {
            return false;
        }
        $input = $input ? $input : $this->input;
        if (explode(' ', $input) && count(explode(' ', $input)) > 1) {
            $result = [];
            $input = array_diff(explode(' ', $input), ['']);
            foreach ($input as $value) {
                $result[] = $this->reloadfun($value, $checkId);
            }

            return preg_replace('/! =/', '!=', join(' ', $result));
        }
        if (isset($this->patterns[$input])) {
            $this->saveInput [] = $input;
            return $this->checkOnId($input) == false && $checkId == true ? $this->patterns[$input]['select'] : $this->checkOnId($input);
        }
        return $input;
    }

    private function checkOnId($value)
    {
        if ($value === 'id') {

            return explode('AS', trim($this->patterns[$value]))[0];
        }
        return false;
    }

}