<?php


namespace Keperis\Eloquent\Provide\Builder;


use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Keperis\Eloquent\Provide\StructureCollection;

class StructureQueryBuilder implements BuilderInterface
{

    /**
     * @var StructureCollection
     */
    protected $structure;

    /**
     * @var Builder
     */
    private $table;


    protected $query = [];

    public function __construct(StructureCollection $structure)
    {
        $this->structure = $structure;
        $this->table = DB::table($structure->getController()->getOriginTableName());
    }

    public function createSelect()
    {

        $templates = $this->structure->getController()->getTemplates($this->structure->getGet());

        foreach ($templates as $template) {
            if (array_key_exists('join', $template)) {
                $this->query['join'][] = $template['join'];
            }
            $this->query['select'][] = $this
                ->structure
                ->getController()
                ->convertTemplate($template['select']);
        }

    }

    public function createWhere()
    {
        if($this->structure->hasSetting('where')){
            $this->query['where'][] = $this->structure->getSetting('where');
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
                $j->on(DB::raw($on), DB::raw(''), DB::raw(''));
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

    public function createGroupBy()
    {
        if ($this->structure->has('group')) {
            $this->table->groupBy($this->structure->get('group'));
        }
    }

    public function createOrderBy()
    {
    }

    public function createLimit()
    {

    }

    /**
     * Building to query
     * @return BuilderInterface
     */
    public function build()
    {
        return $this;
    }
}