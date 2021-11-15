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

    public function __construct(StructureCollection $structure)
    {
        $this->structure = $structure;
        $this->table = DB::table($structure->getController()->getOriginTableName());
    }

    public function createSelect()
    {
        $templates = $this->structure->getController()->getTemplates($this->structure->getGet());


    }

    public function createWhere()
    {
    }

    private function merge(BuilderInterface $builder)
    {
    }


    public function createJoin()
    {
    }

    public function createGroupBy()
    {
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