<?php


namespace Keperis\Eloquent\Provide\Builder;


use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Expression;
use Keperis\Eloquent\Provide\Exception\StructureValidatorException;
use Keperis\Eloquent\Provide\ProvideTemplate;
use Keperis\Eloquent\Provide\StructureCollection;
use Keperis\Eloquent\Provide\Template\ProvideTemplateInterface;
use Keperis\MiddlewareTrait;


class StructureQueryBuilder implements BuilderInterface
{

    use MiddlewareTrait;

    /**
     * @var StructureCollection
     */
    protected $structure;

    /**
     * @var Builder
     */
    private $table;


    /**
     * Parsed pattern from all structures {get} key
     * @var array
     */
    protected $patterns = [];


    protected static $resolveControllers = [];

    public function __construct(StructureCollection $structure)
    {

        $this->structure = $structure;

        /**
         * @return \PDO
         */
        $pdo = static function () {
            $c = container()->connection->getPdo();
            $c->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $c->setAttribute(\PDO::ATTR_CASE, \PDO::CASE_NATURAL);
            $c->exec("SET time_zone = '" . date('P') . "'");
            $c->exec('SET names utf8');
            return $c;
        };

        $this->table = new Builder(container()->connection->setPdo($pdo()));
        $this->table->fromRaw($this->structure->getController()->getOriginTableName());

        $this->boot();
    }


    /**
     * Check if is valid pattern format
     * Each pattern in structure must pass a middlewere
     * @param array|null $pattern
     * @return bool
     */
    private function isValid(?array $pattern)
    {
        if (!is_array($pattern)) {
            return false;
        }
        return array_key_exists('render', $pattern);
    }

    /**
     * @param array|null $pattern
     * @param ProvideTemplate $controller
     * @return array|null
     */
    public function __invoke(?array $pattern, ProvideTemplate $controller): ?array
    {
        if ($this->isValid($pattern)) {
            return $pattern;
        }

        $pattern['render'] = ProvideTemplate::convertTemplate($pattern)['select'];

        return $pattern;

    }

    /**
     * @param array|null $pattern
     * @return array
     */
    protected function convertToFormat(?array $pattern)
    {
        if (!$pattern) {
            throw new StructureValidatorException("Invalid format for convert pattern to special format, can be only array type");
        }

        $newPatterns = [];
        foreach ($pattern as $controller => $value) {
            /** @var ProvideTemplate $controller */
            $controller = self::$resolveControllers[$controller];
            foreach ($value as $item) {
                $p = $this->callMiddlewareStack($controller->getTemplate($item), $controller);
                if (!$p) {
                    throw new \ParseError(sprintf("Cant parse pattern for controller [%s]", get_class($controller)));
                }
                $newPatterns[] = $p;
            }

        }

        return $newPatterns;
    }

    /**
     * Parse all get param form structure
     * @throws \Exception
     */
    protected function boot()
    {


        self::$resolveControllers[get_class($this->structure->getController())] = $this->structure->getController();

        $patterns = [get_class($this->structure->getController()) => $this->structure->getGet()];

        $join = $this->structure->getJoin();

        foreach ($join as $item) {
            if (!$item) {
                throw new StructureValidatorException("Invalid join parse format");
            }

            $controller = $item->getController();


            self::$resolveControllers[get_class($controller)] = $controller;
            $patterns[get_class($controller)] = $item->getGet();

        }

        $this->patterns = $this->convertToFormat($patterns);
    }


    public function createLine()
    {
        if ($this->structure->hasSetting('line')) {
            $line = $this->structure->getSetting('line');

            foreach ($line as $type => $value) {
                switch ($type) {
                    case 'select':
                        $this->table->selectRaw($value);
                        break;

                    case 'join':
                        foreach ($value as $join) {
                            if (!array_key_exists('table', $join)) {
                                throw new StructureValidatorException('Invalid join format for line builder');
                            }
                            $this->table->join($join['table'], function ($j) use ($join) {
                                $j->on(self::raw($join['on']), self::raw(''), self::raw(''));
                                if (array_key_exists('where', $join)) {
                                    $j->whereRaw($join['where']);
                                }
                            });
                        }
                        break;
                }
            }
        }
    }

    public function createSelect()
    {

        foreach ($this->patterns as $template) {

            $q = "{$template['render']} " . (array_key_exists('as', $template) ? "as {$template['as']}" : "");


            $this->table->selectRaw($q);
        }


    }

    public function createWhere()
    {

        if ($this->structure->hasSetting('where')) {
            $where = $this->structure->getSetting('where');

            if (is_array($where)) {
                $where = implode(" AND ", $where);
            }

            $this->table->whereRaw($where);
        }
    }


    public function createJoin()
    {
        /** @var  StructureCollection[] $join */
        $joins = $this->structure->getJoin();


        foreach ($joins as $join) {

            $table = $join->getController()->getOriginTableName();
            $on = $join->get('on');

            $type = $join->get('join_type', $join->get('type_join', $join->get('type')));

            $clouser = function ($j) use ($on) {
                $on = explode('=', $on);
                $j->on(self::raw($on[0]), self::raw($on[1]));
            };

            switch (strtolower($type)) {
                case 'left':
                    $this->table->leftJoin($table, $clouser);
                    break;
                case 'right':
                    $this->table->rightJoin($table, $clouser);
                    break;
                case 'inner':
                default:
                    $this->table->join($table, $clouser);
                    break;
            }


            if ($join->hasSetting('where')) {
                $this->table->whereRaw($join->getSetting('where'));
            }
        }
    }


    /**
     * @param string $pattern
     * @return string
     */
    private function replacePatternOrNot(string $pattern)
    {
        $replace = function ($v) {
            $controllers = $this->structure->getControllers();

            foreach ($controllers as $controller) {
                if ($controller->hasTemplate($v)) {
                    return $controller->getTemplate($v);
                }
            }
            return $v;
        };

        if (strpos(',', $pattern) === false) {
            return $replace($pattern);
        }

        $pattern = array_map(function ($val) use ($replace) {
            return $replace(trim($val));
        }, explode(',', $pattern));

        return implode(', ', $pattern);
    }


    public function createGroupBy()
    {
        if ($this->structure->hasSetting('group')) {

            $group = $this->replacePatternOrNot($this->structure->getSetting('group'));

            $this->table->groupByRaw($group);
        }

    }

    public function createOrderBy()
    {
        if ($this->structure->hasSetting('order')) {
            $order = $this->structure->getSetting('order');

            if (strpos('a_', $order) === false) {
                $this->table->orderBy($order);
            } else {

                $order = preg_replace('/a_/', '', $order);
                $this->table->orderByDesc($order);
            }
        }
    }

    public function createLimit()
    {
        if ($this->structure->hasSetting('limit')) {
            $q = explode(',', $this->structure->getSetting('limit'));

            [$limit, $offset] = array_map(function ($v) {
                return intval(trim($v));
            }, $q);

            $this->table->limit($limit)->offset($offset);
        }
    }

    /**
     * Building to query
     * @return BuilderInterface
     */
    public function build()
    {
        $this->createSelect();
        $this->createJoin();
        $this->createWhere();
        $this->createOrderBy();
        $this->createGroupBy();
        $this->createLine();

        return $this;
    }

    /**
     * @return string
     */
    public function toSql(): string
    {
        return $this->table->toSql();
    }


    private static function raw(string $value){
        return new Expression($value);
    }
}