<?php
namespace WPMC\Field;

use WPMC\DB\EloquentDBFacade;
use WPMC\FieldBase;

class VirtualField extends FieldBase
{
    private $sqlRaw;

    public function toArray()
    {
        $arr = parent::toArray();
        $arr['sql_raw'] = $this->getSqlRaw();

        return $arr;
    }

    public function getSqlRaw()
    {
        return $this->sqlRaw;
    }

    public function setSqlRaw($sqlRaw)
    {
        $this->sqlRaw = $sqlRaw;
        return $this;
    }

    public function isPrimitiveType()
    {
        return false;
    }

    public function isCreatable()
    {
        return false;
    }

    public function isEditable()
    {
        return false;
    }

    public function isSortable()
    {
        return false;
    }

    public function alterEloquentQuery(\Illuminate\Database\Query\Builder $qb)
    {
        parent::alterEloquentQuery($qb);

        $name = $this->getName();
        $sqlRaw = $this->getSqlRaw();

        $qb->addSelect(EloquentDBFacade::raw("{$sqlRaw} AS {$name}"));
        // $qb->leftJoin($refTable, "{$rootTable}.{$name}", '=', "{$refTable}.id");
    }

    public function applyGenericSearchFilter(\Illuminate\Database\Query\Builder $qb, $search)
    {
        // $qb->orWhere("{$sqlRaw}", 'like', "%{$search}%");
    }

    public function render()
    {
    }
}