<?php


namespace Keperis\Core;


use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Keperis\Core\Page\PageCreator;
use Keperis\EventDispatcher\Concerns\DelegatesToDisptacher;
use Keperis\MiddlewareProvideTableTrait;

class provideExport
{

    use DelegatesToDisptacher, MiddlewareProvideTableTrait;

    public static $name = null;
    public static $callback;
    public static $middleware = [];
    private $initForRow;
    private $row = null;
    /**
     * @var int
     */
    private $width = 30;
    /**
     * @var null|array
     * use for debug
     */
    private $notExistingFun = null;
    /**
     * @var string
     * by default
     */
    private $type = 'xls';
    /**
     * @var bool
     */
    private $limit = false;
    /**
     * @var array
     */
    private $noexportOptions =
        [
            'event_o',
        ];
    /**
     * @var array
     */
    private $title;
    /**
     * @var string;
     */
    private $curentName;
    /**
     * @var Spreadsheet
     */
    private $excel;
    /**
     * @var array
     */
    private $dataStructure = [];


    /**
     * provideExport constructor.
     * @return  provideExport
     */
    function __construct()
    {
        PageCreator::$script = false;
        $this->excel = new Spreadsheet();
        return $this;
    }

    /**
     * @param array $dataArray
     * @param array $title
     * @param string $name
     * @param null $init
     * @param array $row
     * @return array|null
     * @throws \Exception
     */
    public static function exportTable(array $dataArray, array $title = null, string $name = '', $init = null, $row = [])
    {

        if (valid(container()->get('request')->getUri()->getParseQuery(), 'export', post('export', null))) {

            if (isset($dataArray[key($dataArray)]['setting']['limit'])) {
                unset($dataArray[key($dataArray)]['setting']['limit']);
            }
            self::$name = html_entity_decode(strip_tags(htmlspecialchars_decode(($name == '' ? self::$name : $name))));
            return self::creatCls()->setDataStructure($dataArray)->setTitle($title)->setLimit(null)->setInitForRow($init)->setRow($row)->export();
        }
        return null;
    }

    public function export($render = false)
    {
        $this->bindMiddleware();
        if ($render != false) {
            return null;
        }
        $row = structure()->set($this->dataStructure);
        if (is_object($this->initForRow)) {
            $row = $row->getData($this->initForRow, key($this->dataStructure));
        } else {
            $row = $row->get(key($this->dataStructure));
        }


        //  debug($this->title);
        set_time_limit(300);
        $this->excel->setActiveSheetIndex(0);
        $sheet = $this->excel->getActiveSheet();

        $sheet->setTitle('Page')->getDefaultRowDimension();

        $sheet->getDefaultRowDimension()->setRowHeight(35);
        $column_index = 0;

        foreach ($this->title as $key => $value) {
            if (in_array($key, $this->noexportOptions)) {
                continue;
            }
            $width = isset($value['width']) ? $this->calculateWidth((int)$value['width']) : $this->width;
            $value = strip_tags(isset($value['text']) ? $value['text'] : $value);
            $sheet->setCellValueByColumnAndRow($column_index + 1, 1, $value);
            $sheet->getColumnDimensionByColumn(($column_index + 1))->setWidth($width)->setAutoSize(true);
            $column_index++;
        }
        $sheet
            ->getStyle('A1:' . $sheet->getHighestColumn() . '1')
            ->getFont()
            ->setBold(true);

        $rowIndex = 2;

        $row = $this->filterRowByRole($row);

        foreach ($row as $values) {
            $values = $this->callMiddlewareStack($values, $this->title);



            $column_index = 1;
            foreach ($this->title as $key => $titles) {
                if (in_array($key, $this->noexportOptions)) {
                    continue;
                }



                $id = isset($titles['name']) && $titles['name'] == true ? true : false;
                $titles = isset($titles['title']) ? $titles['title'] : $key;
                $width = isset($value['width']) ? $this->calculateWidth($value['width']) : $this->width;
                if (isset($values[$titles])) {
                    $values[$titles] = $id == true ? getNameById($values[$titles]) : htmlspecialchars_decode($values[$titles],
                        ENT_QUOTES);

                    if (is_int($values[$titles])) {
                        if (in_array($titles, ['phone1', 'mobile'])) {
                            //$sheet->getStyleByColumnAndRow($column_index, $rowIndex)->getNumberFormat()->setFormatCode('_-* # ##0_-;-* # ##0_-;_-* "-"_-;_-@_-');
                            $values[$titles] = ' ' . $values[$titles];
                        }
                        $sheet->setCellValueByColumnAndRow
                        (
                            $column_index,
                            $rowIndex,
                            $values[$titles]
                        );
                        $sheet->getColumnDimensionByColumn($column_index)->setWidth($width);
                    } else {
                        if (in_array($titles, ['phone1', 'mobile'])) {
                            //$sheet->getStyleByColumnAndRow($column_index, $rowIndex)->getNumberFormat()->setFormatCode('_-* # ##0_-;-* # ##0_-;_-* "-"_-;_-@_-');
                            $values[$titles] = ' ' . $values[$titles];
                        }
                        $sheet->setCellValueByColumnAndRow
                        (
                            $column_index,
                            $rowIndex,
                            html_entity_decode(strip_tags(htmlspecialchars_decode($values[$titles])))
                        );
                        $sheet->getColumnDimensionByColumn($column_index)->setWidth($width);
                        $sheet->getStyleByColumnAndRow($column_index, $rowIndex)->getAlignment()->setWrapText(true);

                    }
                } else {
                    $sheet->setCellValueByColumnAndRow($column_index, $rowIndex, '');
                }
                $column_index++;
            }
            $rowIndex++;
        }

        return [
            $this->excel,
            slug(self::$name) ?: $this->curentName . date("m.d.y.g"),
        ];
//
//        saveExcelFile($this->excel, slugify(self::$name) ?: $this->curentName . date("m.d.y.g"), 'xlsx');

    }

    private function bindMiddleware()
    {
        if (!self::$middleware) {
            return;
        }

        foreach (self::$middleware as $value) {
            $this->addMiddleware($value);
        }
    }

    private function calculateWidth($var)
    {
        $var = is_string($var) ? (int)$var : $var;
        return ($var / 72) * 10;
    }

    protected function filterRowByRole($row)
    {
        $origin = $row;

        if (role_check('export.allow.300')) {
            $row = array_slice($origin, 0, 300);
        }

        if (role_check('export.allow.1000')) {
            $row = array_slice($origin, 0, 1000);
        }

        if (role_check('export.allow.all')) {
            $row = $origin;
        }


        return $row;

    }

    public function setRow($row)
    {
        $this->row = $row;
        return $this;
    }

    public function setInitForRow($init)
    {
        $this->initForRow = $init;
        return $this;
    }

    /**
     * @param string default
     * @return $this
     * set limit of select from sql
     */
    public function setLimit($default)
    {
        if ($default) {
            $this->dataStructure[$this->curentName]['setting']['limit'] = $default;
            $this->limit = $default;
        } else {
            unset($this->dataStructure[$this->curentName]['setting']['limit']);
        }
        return $this;
    }

    /**
     * @param array $title
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @param $dataStructure array
     * @return $this
     * set data structure for parsing by ProvideStructure
     */
    public function setDataStructure($dataStructure, $name = null)
    {
        if ($name) {
            $this->dataStructure[$name] = $dataStructure;
            $this->curentName = $name;
            return $this;
        }
        $name = key($dataStructure);
        $this->curentName = $name;
        $this->dataStructure[$name] = $dataStructure[$name];
        return $this;
    }

    /**
     * @return provideExport
     */
    public static function creatCls()
    {
        return new static();
    }

    public static function add(callable $callable)
    {
        self::$middleware[] = $callable;
    }

    public function __invoke($data)
    {
        return $data;
    }

    public function addEvent(callable $calback)
    {
        $this->listener->add($calback, get_class($this));
    }

    public function setName(string $name)
    {
        $this->curentName = $name;
        return $this;
    }

    /**
     * @param string
     * @return $this
     * set type of file for exit
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @param array|$options
     * @return $this
     * delete from dataStructure data that should not be exported
     */
    public function noexport($options)
    {
        $options = (is_array($options)) ? $options : [$options];
        $this->dataStructure[$this->curentName]['get'] = array_diff(
            $this->dataStructure[$this->curentName]['get'],
            $options
        );
        $this->noexportOptions = $options;
        return $this;
    }

    /**
     * @param array $options
     * @return $this
     * set own quick options
     */
    public function options(array $options)
    {
        foreach ($options as $nameOfOptions => $value) {
            call_user_func([$this, $nameOfOptions], $value);
        }
        return $this;
    }

    /**
     * @param $name
     * @param $arguments
     * parsing not existing function and save data
     */
    function __call($name, $arguments)
    {
        $this->notExistingFun[] =
            [
                'name'     => $name,
                'argument' => $arguments,
            ];
    }

    function __debugInfo()
    {
        var_dump($this->dataStructure);
        if ($this->notExistingFun) {
            var_dump($this->notExistingFun);
        }
    }

    public function setStack($stack)
    {
        $this->stack = $stack;
    }
}