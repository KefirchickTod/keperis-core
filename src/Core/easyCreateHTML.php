<?php


namespace Keperis\Core;


use Closure;

class easyCreateHTML
{

    public static $toolBar = '';
    /** @var array */
    protected static $noCloseTags = [
        'area',
        'base',
        'br',
        'col',
        'frame',
        'hr',
        'img',
        'input',
        'link',
        'meta',
        'param',
        'text',
    ];
    /* @var string */
    private $html = '';
    /* @var array */
    private $stack = [];


    public static function dataPickerBeetween($parent = '')
    {
        return static::create()
            ->div()
                ->input([
                    'class' => 'form-control litepicker-date',
//                    'readonly' => 'readonly',
                    'data-parent' => $parent,
                ])
            ->end('div')->render(true);


    }

    /**
     * @return easyCreateHTML
     */
    public static function create()
    {
        return new static();
    }

    /**о
     * @param array
     * @param string
     * @return array|string
     */
    public static function popOption(&$options, $name)
    {
        if (array_key_exists($name, $options)) {
            $option = $options[$name];
            unset($options[$name]);
            return $option;
        }
        return '';
    }


    /**
     * Simple foreach create with tags
     * @param array
     * @param string
     * @return easyCreateHTML
     * */
    public function each($setting, $tags = null)
    {
        $attType = true;
        if (is_array($setting)) {
            if (is_int(key($setting))) {
                if (!is_array($setting[0])) {
                    $attType = false;
                }
            }
        }
        $tags = $tags ?: array_pop($this->stack);
        foreach ($setting as $key => $value) {
            if ($attType == false) {
                $this->$tags(['text' => "$value"])->end();
            } else {
                $this->$tags($setting[$key])->end($tags);
            }
        }
        return $this;
    }

    public function breaking()
    {
        return $this;
    }

    public function insert($html)
    {
        if (is_object($html) && $html instanceof Closure) {
            $html = call_user_func($html);
        }
        $this->html .= $html;
        return $this;
    }

    /**
     * @return easyCreateHTML
     */
    public function __call($method, $ps)
    {
        array_unshift($ps, $method);
        return call_user_func_array([$this, 'tag'], $ps);
    }

    public function __toString()
    {
        return $this->render(true);
    }

    /**
     * Render current tags
     * @param boolean $return return or print
     * @return easyCreateHTML|string|void
     *
     */
    public function render($return = false)
    {
        while ($this->stack) {
            $this->end();
        }
        //var_dump($this->html);
        $html = $this->html;
        $this->html = '';
        if ($return) {
            return $html;
        }
        echo $html;
    }

    /**
     * Close tag with name, or last tag
     * @param string $name tag name
     * @return easyCreateHTML
     */
    public function end($name = '')
    {
        if ($this->stack) {
            $name2 = array_pop($this->stack);
        }
        if (!$name) {
            $name = $name2;
        }
        $this->html .= '</' . $name . '>';
        return $this;
    }

    public function __clone()
    {
        $this->html = '';
        $this->stack = [];
    }

    public function clean()
    {
        return clone $this;
    }

    /**
     * @param string $name tag name
     * @param array|string $options tag attributes
     * @return easyCreateHTML
     */
    protected function tag($name, $options = null, $autoClose = true)
    {

        $name = strtolower($name);
        if ($name === 'text') {
            $this->stack[] = $name;
            return $this;
        }
        $close = in_array($name, self::$noCloseTags);
        if ($name === 'html' && is_string($options)) {
            $this->html .= $options;
            return $this;
        }
        $options = $options ?: [];
        $attrs = [];
        $options = (isset($options['text'])) ? $this->pushBack('text', $options) : $options;
        $text = '';
        if (is_array($options)) {
            foreach ($options as $attr => $value) {
                if ($attr === 'text') {
                    $text = $value;
                } else {
                    $attrs[] = $attr . '="' . $this->safe(is_array($value) ? implode(' ', $value) : $value) . '"';
                }
            }
        } elseif (is_string($options)) {
            $attrs = [$options];
        }

        $this->html .= '<' . $name . ($attrs ? ' ' . implode(' ',
                $attrs) : '') . ($close ? '/' : '') . '>' . $text ?: $text;
        if (!$close && $autoClose) {
            $this->stack[] = $name;
        }
        return $this;
    }

    /**
     * @param $key
     * @param $arr
     * @return mixed
     */
    public static function pushBack($key, $arr)
    {
        $r = $arr[$key];
        unset($arr[$key]);
        $arr[$key] = $r;
        return $arr;
    }

    /**
     * Return safe html string
     * @param string|array $value
     * @return string
     */
    public static function safe($value)
    {
        if (is_array($value)) {
            foreach ($value as &$item) {
                $item = self::safe($item);
            }
            return $value;
        } else {
            if (is_scalar($value)) {
                return htmlspecialchars('' . $value, ENT_QUOTES);
            }
        }
        return "TYPE ERROR " . gettype($value);
    }

}