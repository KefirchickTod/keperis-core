<?php


namespace src\Page\Components\Table;


use Closure;
use ErrorException;

class ActionButton
{
    /**
     * @var Closure
     */
    protected $customerInit;
    /**
     * @var array
     */
    protected $initKey =
        [
            'link',
            'icon',
            'sort',
            'position',
            'id',
            'api',
            'otherId',
            'type',
            'attr',
            'roles'
        ];
    private $doneInit = false;
    /**
     * @var array
     */
    private $html = [];
    /**
     * @var bool   true - star | false - end
     */
    private $position = false;
    /**
     * @var null|array $row
     */
    private $row;
    /**
     * @var string $result
     */
    private $result = "";
    /**
     * @var null|string
     */
    private $name = null;
    /**
     * @var array
     */
    private $error = [];
    /**
     * @var null|array
     */
    private $action;

    function __construct($row = null, $action = null)
    {
        if ($action) {
            $this->action[] = $action;
        }
        if ($row) {
            $this->row = $row;
        }
    }

    /**
     * @return ActionButton
     */
    public static function action()
    {
        return new static();
    }

    public function deleteAction($key)
    {
        if (array_key_exists($key, $this->action)) {
            unset($this->action[$key]);
        }
    }

    /**
     * @param $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        if (count($this->action) > 2) {
            // debug(end($this->action));
            $lastName = key(end($this->action));

            $this->action[$name] = $this->action[$lastName];
        } else {
            $action[$name] = $this->action;
            unset($this->action);
            $this->action = $action;
        }
        return $this;
    }

    /**
     * @param array $row
     * @return $this
     */
    public function setRow(array $row)
    {
        $this->row = $row;
        return $this;
    }

    function __debugInfo()
    {
        var_dump($this->error);
    }

    function __toString(): string
    {
        return implode(' ', $this->init()->html) . PHP_EOL;
    }

    protected function init()
    {

        try {
            if (!isset($this->action) || empty($this->action)) {
                throw new Exception("Empty option array");
            }
            $this->doneInit = true;


            $roleFilter = [];
            foreach ($this->action as $action) {
                foreach ($action as $name => $value) {

                    if (in_array($name, $this->initKey) && !empty($value)) {
                        if (array_key_exists('roles', $action)) {
                            $roleFilter = $action['roles'];
                        }
                        $this->position = $name == 'position' ? ($value == 'start' ? false : true) : $this->position;
                        $value = is_array($value) ? $value : [$value];

                        if ($name == 'link') {

                            foreach ($value as $num => $data) {
                                if (array_key_exists($num, $roleFilter) && !role_check($roleFilter[$num])) {
                                    continue;
                                }
                                if (isset($action['api']) && $action['settingApi']) {
                                    $api = \src\bcerpapi::sendRequest($action['api']);
                                    $data = $this->setOtherIds($action['settingApi'], $data, $this->row, $api);
                                }
                                if (isset($action['id']) && $action['id'] == true) {
                                    if (preg_match("/%_id_%/", $data)) {

                                        $data = preg_replace("/%_id_%/",
                                            isset($this->row['id']) ? $this->row['id'] : '1', $data);
                                        $data = isset($action['otherId']) ? $this->setOtherIds($action['otherId'],
                                            $data, $this->row) : $data;

                                    } elseif (isset($action['otherId'])) {

                                        $data = $this->setOtherIds($action['otherId'], $data, $this->row);
                                    }
                                } else {
                                    $data = isset($action['otherId']) ? $this->setOtherIds($action['otherId'], $data,
                                        $this->row) : $data;
                                }


                                $this->html[] = isset($action['callback'][$num]) ? $this->insideEventFunction($action['callback'][$num],
                                    $data) : (isset($action['attr']) && in_array($num,
                                    array_keys($action['attr'])) ? $this->creatHTML($num, null, null,
                                    $action['attr']) : $this->creatHTML($num, $data, $action['icon'][$num]));

                            }
                        }

                    } else {
                        throw new ErrorException("Action key !isset");
                    }
                }
            }
            $this->result = implode(" ", $this->html);
        } catch (ErrorException $exception) {
            $this->setErrorMassage($exception->getMessage());
        }
        return $this;
    }

    private function setOtherIds(array $otherId, $link, $row, $api = null)
    {
        // var_dump($link);
        foreach ($otherId as $value) {

            if (preg_match("~$value~", $link) || preg_match("~%_" . $value . "_%~",
                    $link) || preg_match("/%_" . $value . "_%/", $link)) {
                @$link = preg_replace("/%_" . $value . "_%/", ($api ? $api[$row[$value]] : $row[$value]), $link);

            }
        }
        return $link;
    }

    private function insideEventFunction($callback, $params)
    {
        try {

            if (!is_object($callback)) {
                throw new ErrorException("Callback must be object");
            }
            $function = call_user_func($callback, $this->action, $this->row, $params);
            if (isset($function['action'])) {
                $this->action = $function['action'];
            }
            return (isset($function['html']) ? $function['html'] : (is_array($function) ? function () use ($function) {
                unset($function['action']);
                return implode('', $function);
            } : $function));

        } catch (ErrorException $exception) {
            error_log($exception->getMessage());
            $this->setErrorMassage($exception->getMessage());
        }
        return $params;
    }

    private function setErrorMassage($massage)
    {
        $this->error[] = $massage;
    }

    public function withRow($row)
    {
        $clone = clone $this;
        $clone->row = $row;
        return $clone;
    }

    /**
     * @param $id
     * @param $link
     * @param $icon
     * @param null $attr
     * @return mixed
     */
    private function creatHTML($id, $link, $icon, $attr = null)
    {
        if ($attr) {
            return $this->creatHtmlByAttr($id, $attr);
        }
        return html()
            ->a([
                'href' => $link,
                'target' => $this->getTarget($id),
            ])
            ->i(['class' => $icon])
            ->end('i')
            ->end('a')->render(true);
    }

    /**
     * @param $id
     * @param $attr
     * @return mixed
     */
    private function creatHtmlByAttr($id, $attr)
    {
        if (!isset($attr[$id]['text']) && $this->action[0]['icon'][$id]) {
            $attr[$id]['text'] = html()->i(['class' => $this->action[0]['icon'][$id]])->end('i')->render(true);

        }

        if ($this->row) {
            foreach ($this->row as $name => $value) {
                $name = trim($name);
                foreach ($attr[$id] as &$rep) {
                    if (preg_match("~$name~", $rep)) {
                        $rep = preg_replace("/%_" . $name . "_%/", $value, $rep);

                    }
                }
            }
        }
        return html()->a($attr[$id])->end('a')->__toString();

    }

    /**
     * @param $id
     * @return array|string
     */
    private function getTarget($id)
    {
        $target = "_blank";
        if (isset($this->action[0]['notarget'])) {
            $target = array_diff(is_array($this->action[0]['notarget']) ? $this->action[0]['notarget'] : explode(',',
                $this->action[0]['notarget']), ['', null, ' ', false]);
            if (in_array($id, $target)) {
                $target = '_self';
            } else {
                $target = '_blank';
            }
        }
        return $target;
    }


    /**
     * @return string
     */
    public function getResult()
    {
        if ($this->doneInit == true) {
            return $this->result;
        }
        return $this->init()->getResult();
    }

    public function __clone()
    {
        $this->doneInit = false;
        $this->html = [];
        $this->name = null;
        $this->result = "";
        return $this;
    }
}