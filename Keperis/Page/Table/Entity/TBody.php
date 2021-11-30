<?php


namespace Keperis\Page\Table\Entity;


use Illuminate\Support\Str;
use Keperis\MiddlewareProvideTableTrait;
use Keperis\Page\Table\Table;
use stdClass;

class TBody implements TableEntity
{

    use MiddlewareProvideTableTrait, TableReplacerTrait;

    protected static $TEMPLATE = "<span class='tr-content {%additional%}' style='-webkit-line-clamp : {%line%}' {%attributes%}>{%content%}</span>";

    protected $fetchResult;
    private $key;
    /**
     * @var array|ActionButton
     */
    protected $action = null;

    protected $dynamicData = [];

    public function __construct($row, ?array $keys = [], ?array $action = [])
    {
        $this->key = $keys;
        $this->fetchResult = $row;
        $this->action = $action;

    }

    public function add(callable $callback)
    {
        $this->addMiddleware($callback);
        return $this;
    }


    public function register(Table $table)
    {
        if ($this->action) {
            $this->action = new ActionButton(null, $this->action);
        }

        return $table;
    }

    public function render(): string
    {


        $result = '';

        foreach ($this->fetchResult as $count => $row) {
            if($row instanceof stdClass) {
                $row = (array)$row;
            }

            if (!array_key_exists('id', $row)) {
                $row['id'] = $count;
            }

            $result .= "<tr data-table-row-id='{$row['id']}' data-table-count='{$count}'>";
            $row = $this->callMiddlewareStack($row, $this->key);;


            foreach ($this->key as $key => $setting) {
                if (!array_key_exists($key, $row)) {
                    continue;
                }
                $content = $row[$key] ?? "";
                $result .= "<td>{$content}</td>";
            }

            $result .= "</tr>";

        }

        $result = "<tbody>{$result}</tbody>";

        return $result;
    }


    protected function hasActionTd(array $row, string $key): bool
    {
        if (!$this->action || !$this->action instanceof ActionButton) {
            return false;
        }


        return $key === THead::ACTION_COLUMN;

    }


    /**
     * Check if already create tr content (by other middlewhere)
     * @param string|null $content
     * @return bool
     */
    public function alreadyCreated(?string $content): bool
    {
        if (is_null($content)) {
            return false;
        }
        if (Str::contains($content, 'tr-content')) {
            return true;
        }
        return false;

    }

    public function __invoke($row)
    {


        foreach ($this->key as $key => $setting) {


            if ($this->hasActionTd($row, $key)) {
                $row[$key] = $this->action->withRow($row)->getResult();
                continue;
            }
//            if ($this->hasDynamicTd($setting)) { //todo at trait or other class
//
//                continue;
//            }

            if (!array_key_exists($key, $row)) {
                error_log("Key $key not exist in row");
                continue;
            }


            $line = $setting['line'] ?? 8;

            $content = $row[$key];


            if ($this->alreadyCreated($content)) {
                continue;
            }

            $row[$key] = $this->renderTrContent($content, '', $line, [
                'data-id' => $row['id'],
                'title'   => addslashes($content),
            ]);

        }
        return $row;
    }

}

