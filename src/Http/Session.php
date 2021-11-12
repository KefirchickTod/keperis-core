<?php


namespace src\Http;


use src\Interfaces\SessionInterface;

/**
 * Class Session
 * @package App\src\Http
 * @author Zakhar
 */
class Session implements SessionInterface
{

    const key = 'flash_massage';

    const SUCCESS = 1;
    const ERROR = 0;
    const WARNING = 3;
    const ALL = -1;
    public $template = [];
    protected $htmlTemp;
    protected $types = [
        self::SUCCESS,
        self::ERROR,
        self::WARNING,
    ];
    protected $statusTest = [
        self::ERROR   => 'Error',
        self::SUCCESS => 'Success',
    ];
    protected $cssClass = [
        self::SUCCESS => 'alert-success',
        self::ERROR   => 'alert-danger',
        //todo self::WARNING
    ];

    private $massage = '';
    private $type = null;

    public function __construct(array $item = [])
    {
        $this->htmlTemp = setting()->session['htmlTemp'];

        if (empty($_SESSION[self::key])) {
            $_SESSION[self::key] = [];
        }
    }

    public function succussMassage($key = 'success')
    {
        if ($this->has($key, self::SUCCESS)) {
            return $this->get($key, self::SUCCESS, '');
        }
        return '';
    }

    public function offsetHas($key)
    {
        foreach ($this->types as $type) {
            if ($this->hasMassage($type) && isset($_SESSION[self::key][$type][$key])) {
                return $type;
            }
        }
        return null;
    }

    public function has(string $key, int $type = null): bool
    {
        //var_dump($this->isEmpty());
        if ($this->isEmpty()) {
            return false;
        }

        if (is_null($key)) {

            throw new \RuntimeException("Key of session is null");
        }

        if (!is_null($type) && $this->hasMassage($type)) {
            return isset($_SESSION[self::key][$type][$key]);
        }

        foreach ($this->types as $type) {
            if ($this->hasMassage($type) && isset($_SESSION[self::key][$type][$key])) {
                return true;
            }
        }
        return false;
//        foreach ($_SESSION[self::key] as $value) {
//
//
//            if ((is_array($value) && array_key_exists($key, $value)) || $key === $value) {
//                return true;
//            }
//        }
    }

    public function isEmpty(): bool
    {
        return empty($_SESSION[self::key]);
    }

    public function hasMassage(int $type): bool
    {

        return array_key_exists($type, $_SESSION[self::key]);
    }


    public function get(string $key, $type = Session::SUCCESS, $default = null)
    {

        if ($type === Session::ALL && $this->has($key)) {
            return $this->get($key, $this->offsetHas($key), 'error');
        }

        if ($this->has($key, $type)) {
            $this->massage = $_SESSION[self::key][$type][$key];
            $this->type = $type;
            $this->remove($key, $type);

            return $this->massage;
        }
        return $default;
    }

    public function cleanType($type = null){
        if($type){
            $_SESSION[self::key][$type] = [];
            return;
        }
        foreach ($this->types as $type){
            $_SESSION[self::key][$type] = [];
        }
        return;
    }
    /**
     * @param string|string[] $key
     */
    public function remove($key, $type = null): void
    {
        if($this->isEmpty()){
            return;
        }

        if($type === -1){
            $this->cleanType();
        }

        if(!$this->has($key, $type)){
            return;
        }

        if($type && array_key_exists($key, $_SESSION[self::key][$type])){
            unset($_SESSION[self::key][$type][$key]);
            return;
        }

        foreach ($this->types as $value){
            if(isset($_SESSION[self::key][$value])){
                if(array_key_exists($key, $_SESSION[self::key][$value])){
                    unset($_SESSION[self::key][$value][$key]);
                    return;
                }
            }
        }
        throw new \RuntimeException("Cant remove $key, $type from session");

    }


    /**
     * @param null $massage
     * @return string|string[]|null
     */
    public function render($massage = null, $type = null)
    {
        $this->type = $type ?: $this->type;
        if (!$this->htmlTemp) {
            return '';
        }
        if (!$this->massage && !$massage) {

            return '';
        }
        if ($this->type === null) {

            return '';
        }
        $this->massage = $this->massage ?: $massage;
        $this->massage = preg_replace(["~{%_cssClass_%}~", "~{%_statusText_%}~", "~{%_massage_%}~"], [
            $this->cssClass[$this->type],
            $this->statusTest[$this->type],
            $this->massage,
        ], $this->htmlTemp);
        return $this->massage;
    }

    public function errorMassage($key = 'error')
    {
        if ($this->has($key, self::ERROR)) {
            return $this->get($key, self::ERROR, '');
        }
        return '';
    }

    public function size(int $type)
    {
        if (!$this->isEmpty()) {
            return count($_SESSION[self::key][$type]);
        }
        return 0;
    }

    public function success($value, $key = null)
    {
        return $this->set($key ?: "success", $value, self::SUCCESS);
    }

    public function set(string $key, $value, int $massageType)
    {
        $_SESSION[self::key][$massageType][$key] = $value;
        return $this;
    }

    public function error($value, $key = null)
    {
        return $this->set($key ?: "error", $value, self::ERROR);
    }

    public function __clone()
    {
        $this->clear();

        return $this;
    }

    public function clear(): void
    {
        $_SESSION[self::key] = [];
        $this->type = null;
        $this->massage = '';
    }

    public function __get($name)
    {
        foreach ($this->types as $type) {
            $get = $this->get($name, $type, null);
            if ($get) {
                return $get;
            }
        }
        return null;
    }

    public function isError(): bool
    {
        return $this->hasMassage(self::ERROR);
    }

    public function isSuccess(): bool
    {
        return $this->hasMassage(self::SUCCESS);
    }

}