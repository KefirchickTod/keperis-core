<?php


namespace src\Structure;


use App\Provides\ProvideStructures\bcUser;
use Closure;
use Error;
use Exception;
use src\MiddlewareProvideTableTrait;

class Structure
{

    use  MiddlewareProvideTableTrait;

    const dirToStructures = ROOT_PATH . '/app/Provides/ProvideStructures/';
    /**
     * пространсто для класів паттернів
     */
    private $bindClouser = false;
    public $memory;
    public $time;
    /**
     * @var array
     */
    public $setting = [];
    /**
     * @var array
     */
    public $get = [];
    protected $mainName;
    /**
     * @var array
     */
    protected $classes = [];
    /**
     * @var null|array
     */
    protected $structure;
    private $foundClasses = [];


    /**
     * @var ProvideSetter
     */
    private $setter;


    private $error;

    function __construct(array $structure = null)
    {
        if ($structure) {
            $this->structure = null;
        }
        $this->memory = memory_get_usage();
        $this->time = microtime(true);
    }

    public function __invoke($data)
    {
        return $data;
    }

    public function add(callable $callable){
        $this->addMiddleware($callable);
        return $this;
    }

    /**
     * @param array|null $structure
     * @return Structure
     * створюєм власний екзепляр за статичним методом
     */
    public static function creats(array $structure = null)
    {
        return new static($structure);
    }

    /**
     * @param array|null $structure
     * @return $this
     * Додаємо настройки для структури
     */

    public function set(array $structure = null)
    {
        $this->creat($structure ? $structure : $this->structure);
        return $this;
    }

    /**
     * @param array $structure
     * @return bool
     * Створуєм структура за настройками
     */
    protected function creat(array $structure): bool
    {
        try {
            foreach ($structure as $name => $value) {
                if (!is_array($value) || $this->validation($value) == false) {
                    continue;
                }
                $this->mainName = !$this->mainName ? $name : $this->mainName;
                if (isset($value['setting']['join'])) {
                    $this->creat($value['setting']['join']);
                }

                $this->get[$name] = $value['get'] = is_array($value['get']) ? array_unique($value['get']) : [$value['get']];

                $this->classes[$name] = $this->getStructureClass($value, $name);

                if (isset($value['setting'])) {
                    $this->setting($value['setting'], $name);
                }
            }

        } catch (Error $error) {
            var_dump($structure, $name, $value, $error->getMessage());

            debug($error->getMessage());
            $this->error = $error;
            error_log("line = {$error->getLine()}, massage = {$error->getMessage()}, key = {$name}");
            unset($structure[$name]);
            unset($this->structure[$name]);
            return $this->creat($structure);
        }
        return true;
    }

    private function validation(array $value): bool
    {
        if (isset($value['setting']) || isset($value['get']) || isset($value['class'])) {
            return true;
        }
        return false;
    }

    private function getStructureClass(array $value, string $name): ProvideStructures
    {

        if($value['class'] === 'auto'){
            $value['class'] = "App\Provides\ProvideStructures\\bc".ucfirst($name);
        }
        $class =  $value['class'];
        if(class_exists($class)){
            return new $class;
        }

        $value['class'] = "App\Provides\ProvideStructures\\".$value['class'];
        $class = $value['class'];

        return  new $class;
    }

    /**
     * @param $value
     * @return string|null
     * Проскановуємо дерикторію з структурами і повертаємо імя структири яке містить $value
     */
    public function findClassByValue(string $value)
    {
        $this->foundClasses = ProvideRegister::getFoundClasses() ?: $this->foundClasses;
        if ($this->foundClasses) {
            foreach ($this->foundClasses as $className => $pattern) {
                if (array_key_exists($value, $pattern)) {
                    return ProvideStructures::namespace . $className;
                }
            }
            return null;
        } else {
            if(!is_dir(self::dirToStructures)){
                throw new \RuntimeException("Not found dir ".self::dirToStructures);

            }
            $files = array_diff(scandir(self::dirToStructures), ['..', '.', '']);

            foreach ($files as $file) {
                try {
                    $file = explode('.', $file)[0];
                    $classNameSpace = ProvideStructures::namespace . $file;
                    $cls = new $classNameSpace;
                    if (method_exists($classNameSpace, 'getPattern')) {
                        $pattern = $cls->getPattern();
                        ProvideRegister::setFoundClasses($file, $pattern);
                        $this->foundClasses[$file] = $pattern;
                    }
                } catch (Error $error) {
                    error_log($error);
                    continue;
                }
            }
        }
        return $this->findClassByValue($value);

    }

    /**
     * @param array $setting
     * @param string $key
     * Додаємо настройки для запроса
     */
    public function setting(array $setting, string $key)
    {
        $this->setting[$key] = $setting;
    }

    public function name(string $name)
    {
        $this->mainName = $name;
        return $this;
    }

    public function delete($key)
    {
        foreach ([$this->classes, $this->get, $this->setting] as &$value) {
            if (isset($value[$key])) {
                unset($value[$key]);
            }
        }
        ProvideRegister::removeData($key);
    }
    public function isError() : bool {
        return !($this->error || $this->error !== false);
    }

    public function bindClouser($boolvar = true){
        $this->bindClouser = $boolvar;
    }
    /**
     * @param Closure $callback
     * @param string $key
     * @return mixed
     * виконуэмо callback функцію для даних з базиданих
     */
    public function getData(Closure $callback, string $key = 'user')
    {


        if($this->bindClouser === true){
            $callback = Closure::bind($callback, $this);
        }


        return call_user_func($callback, $this->get($key), $this->setter);
    }

    /**
     * @param string $key
     * @param bool $queryArray
     * @return array|null
     * получаємо дані або null
     */
    public function get(string $key = 'user', bool $queryArray = false): array
    {
        if ($this->isEmpty($key)) {
            return [];
        }
        try {
            $this->setter = new ProvideSetter($this, $key);
        } catch (Exception $exception) {
            error_log($exception);
            return [];
        }
        if ($queryArray == true) {
            return $this->setter->getValue(true);
        }
        $result = ProvideRegister::get($key) && !isset(ProvideRegister::get($key)[0]['size']) ? ProvideRegister::get($key) : $this->setter->getValue();

        if(!empty($result[0]) && $result[0] instanceof \RuntimeException){
            $this->error = true;
            return [false];
        }

        ProvideRegister::set($key, $result);


//        $result = array_map(function ($value){
//            return $this->callMiddlewareStack($value);
//        }, $result);

        //$this->callMiddlewareStack($result);

        return $result;
    }

    public function isEmpty(string $key = ''): bool
    {
        if (!$key) {
            return $this->isEmpty(key($this->classes));
        }
        if (!isset($this->classes[$key])) {
            foreach (array_keys($this->classes) as $name) {
                if ($this->classes[$name]->getFactoryName() == $key) {
                    return false;
                }
            }
        }
        return !isset($this->classes[$key]);
    }

    /**
     * @param $key
     * @return ProvideStructures
     * повертаємо обєкт паттерн
     */
    public function classes($key = null): ProvideStructures
    {
        if (!$key) {
            throw new Error("Null given in " . __METHOD__);

        }

        if (!$key && key($this->classes)) {
            return $this->classes(key($this->classes));
        }

        if (!isset($this->classes[$key])) {
            foreach (array_keys($this->classes) as $name) {
                if ($this->classes[$name]->getFactoryName() == $key) {
                    return $this->classes[$name];
                }
            }
            if ($this->structure[$key]) {
                debug(__LINE__, __METHOD__);
                $class = $this->findClassByValue($this->structure[$key]['get'][0]);
                return new $class;
            }
            //return new ($this->findClassByValue($this->structure[$key]['get'][0]));
        }
        return $this->classes[$key];

    }

    /**
     * @return Error получаємо поилки
     */
    public function error(): Error
    {
        return $this->error;
    }

    public function pattern(): array
    {
        $list = [];
        foreach ($this->classes as $item) {
            /** @var $item ProvideStructures */
            $list = array_merge($list, $item->getPattern());
        }
        return $list;
    }

    public function findBySelector(string $selector)
    {
        $result = [];
        foreach ($this->classes as $name => $class) {
            /** @var $class ProvideStructures */
            $pattern = $class->getPattern();

            foreach ($pattern as $name_selector => $value) {
                if (is_array($value) && isset($value['select'])) {

                    if (trim($value['select']) == trim($selector) || stristr($selector, $value['select']) !== false) {

                        $result[] = $name_selector;
                    }
                } elseif (valid($value, 'join') && (trim($value['join']) == trim($selector) || stristr($selector,
                            $value['join']) !== false)) {

                    $result[] = $name_selector;
                }
            }
        }
        return !$result ? null : array_unique($result);
    }

    public function clean()
    {
        $this->classes = [];
        $this->get = [];
        $this->setting = [];
        ProvideRegister::removeAll();
        return $this;

    }

    public function all($class)
    {
        if (!is_object($class)) {
            $class = new $class;
        }

        try {
            if (!$this->isAttached($class)) {
                throw new Exception("Class $class must instance of ProvideStructures");
            }
            /** @var  $class ProvideStructures */
            $data = $class->getPattern();
            unset($data['id'], $data['prefix'], $data['id'], $data['all']);
            $get = array_keys($data);
            return $get;

        } catch (Exception $exception) {
            error_log($exception->getMessage());
        }
        return [];

    }

    public function isAttached($class)
    {
        return $class instanceof ProvideStructures;
    }

    public function getError(){
        return $this->error;
    }
}