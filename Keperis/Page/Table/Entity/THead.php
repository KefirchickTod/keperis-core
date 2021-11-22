<?php


namespace Keperis\Page\Table\Entity;


use Keperis\Collection;
use Keperis\Page\Components\Table;

class THead implements TableEntity
{

    const DEFAULT_WIDTH = 'auto';
    const ACTION_COLUMN = 'event_o';
    /**
     * Array of keys to binding in TBody
     * @var array
     */
    protected $bindings = [
        'names' => null,
    ];
    private $title;


    public function __construct(array $title)
    {
        $this->title = new Collection($title);

    }


    /**
     * @return string
     */
    public function render(): string
    {

        $th = [];

        $title = $this->title->getIterator();


        while ($title->valid()) {

            [$key, $value] = [$title->key(), $title->current()];

            if ($this->getCurrentValueSetting('name', $value) === true) {
                $this->addBindings('names', $key);
            }

            $width = $this->getCurrentValueSetting('width', $value, self::DEFAULT_WIDTH);
            if ($key === self::ACTION_COLUMN) {
                $width = '55px';
            }

            if ($this->getCurrentValueSetting('sort', $value) === true) {
                $content = $this->createSortTemplate($key, $value);
            } else {
                $content = "<span style='text-align: center; width: 100%; display: block'>" . $this->getCurrentValueSetting('text',
                        $value, 'NotFound') . "</span>";
            }


            $th[] = "<th scope='col' style='position: relative; vertical-align: middle; text-align:center; width: {$width}'>{$content}</th>";

            $title->next();

        }

        $th = implode(PHP_EOL, $th);

        $result = "<thead class='thead-light'><tr>{$th}</tr></thead>";

        return $result;

    }

    private function getCurrentValueSetting(string $key, array $current, $default = null)
    {
        if (!$this->hasCurrentValueSetting($key, $current)) {
            return $default;
        }

        return $current[$key];
    }

    private function hasCurrentValueSetting(string $key, array $current)
    {
        if (array_key_exists($key, $current)) {
            return true;
        }
        return false;
    }

    /**
     * @param string $key
     * @param $value
     * @return void
     */
    public function addBindings(string $key, $value): void
    {
        if (!array_key_exists($key, $this->bindings)) {
            throw new \RuntimeException("Undefined key: $key for bindings");
        }
        $this->bindings[$key][] = $value;

    }

    protected function createSortTemplate(string $key, $value)
    {
        $sort = container()->request->getUri()->getParseQuery()['sort'] ?? '';

        $originKey = $key;
        if ($sort === $key) {
            $key = "a_{$key}";
        }

        return html()->div('style = "cursor:pointer;" class="sort-icon-box" data-sort = "' . $key . '"')
            ->span()
            ->insert($value['text'])
            ->end('span')
            ->i([
                'class' => $this->getSortClass($originKey, $key),
            ])->end('div')->render(true);
    }

    private function getSortClass($nameForSort, $nameOfValue)
    {
        if (get('sort')) {
            if (get('sort') === $nameForSort) {
                $sortClass = 'fa fa-sort-desc';
            } else {
                if (preg_match("~a_~", get('sort')) &&
                    explode('_', get('sort'))[1] == $nameOfValue
                ) {
                    $sortClass = 'fa fa-sort-asc';
                } else {
                    $sortClass = 'fa fa-sort';
                }
            }
        } else {
            $sortClass = 'fa fa-sort';
        }
        return $sortClass;
    }


    public function register(Table $table): Table
    {
        return $table;
    }
}
