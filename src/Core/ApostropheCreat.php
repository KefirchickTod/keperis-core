<?php


namespace src\Core;

class ApostropheCreat
{

    /**
     * @var array
     */
    private $patternConsonants =
        [
            'б',
            'п',
            'в',
            'м',
            'ф',
            'к'
        ];
    /**
     * @var array
     */
    private $patternLoud =
        [
            'я',
            'ю',
            'є',
            'ї',
        ];

    private $loud = ['а', 'о', 'у', 'е', 'и', 'і'];

    /**
     * @var string $result
     */
    private $result;

    private $str;

    /**
     * ApostropheCreat constructor.
     * @param null|string $str
     */
    function __construct($str = null)
    {
        if ($str) {
            $this->str = $str;
        }
        return $this;
    }

    /**
     * @return ApostropheCreat
     */
    public static function creat()
    {
        return new static();
    }

    public function setStr(string $str)
    {
        $this->str = $str;
        return $this;
    }

    public function render()
    {
        return $this->init()->result;
    }

    protected function init()
    {

        $this->result = $this->clean($this->str);
        foreach ($this->patternConsonants as $consonant) {
            foreach ($this->patternLoud as $loud) {
                if (preg_match("/" . $consonant . $loud . "/", $this->str)) {
                    $this->result = join("$consonant'$loud", explode("$consonant" . "$loud", $this->str));
                }
            }
        }
        $this->str = $this->result;
        $strArray = str_split($this->str, 2);
        foreach ($strArray as $key => $word) {
            if ($key < 2) {
                continue;
            }
            foreach ($this->patternLoud as $loud) {
                if (preg_match("/р$loud/", $this->str)) {
                    if (in_array($strArray[$key - 1], $this->patternLoud) && !in_array($strArray[$key - 2],
                            $this->patternLoud)) {
                        $this->result = join("р'$loud", explode("р$loud", $this->str));
                    }
                }
            }
        }
        return $this;
    }

    public function clean(string $str)
    {
        if (preg_replace('~`~', "'", $str)) {
            $str = preg_replace('~`~', "'", $str);
        }
        return $str;
    }


}