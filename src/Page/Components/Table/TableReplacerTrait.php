<?php

namespace src\Page\Components\Table;

trait TableReplacerTrait
{
    protected static $TEMPLATE = "<span class='tr-content {%additional%}' style='-webkit-line-clamp : {%line%}' {%attributes%}>{%content%}</span>";


    protected function renderTrContent($content = '', $additional = '', $line = 8, $attribute = [])
    {
        $pattern = [

            "/{%additional%}/",
            "/{%line%}/",
            "/{%attributes%}/",
            "/{%content%}/",
        ];
        if (is_array($attribute)) {
            $attribute = implode(PHP_EOL . ' ', array_map(function ($val, $attr) {
                return "$attr='{$val}'";
            }, $attribute, array_keys($attribute)));
        }


        $replacement = [
            $additional,
            $line,
            $attribute,
            $content,

        ];


        return preg_replace($pattern, $replacement, self::$TEMPLATE);
    }

    /**
     * Rerun array of keys by filtering content of setting
     * @param string $key
     * @param null $setting
     * @return int[]|string[]
     */
    protected function keyOf(string $key, $setting = null)
    {
        $setting = array_filter($setting, function ($val) use ($key) {
            return array_key_exists($key, $val);
        });

        return array_keys($setting);
    }
}
