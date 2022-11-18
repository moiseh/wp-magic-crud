<?php

namespace WPMC\DB;

use WPMC\Entity;

class EntityOptionsList
{
    public function __construct(private Entity $entity)
    {
    }

    /**
     * @return array
     */
    public function buildOptionsList($ids = [])
    {
        $entity = $this->entity;
        $table = $entity->getDatabase()->getTableName();
        $pkey = $entity->getDatabase()->getPrimaryKey();

        $table = $entity->getDatabase()->getTableName();
        $displayField = $entity->getDatabase()->getDisplayField();
        $entQuery = new EntityQuery($entity);
        $defaultOrder = $entQuery->getDefaultOrderCol();

        $qb = EloquentDBFacade::table($table);
        $qb->select($pkey, $displayField);

        if ( !empty($ids) ) {
            $qb->whereIn($pkey, $ids);
        }
        
        $qb->orderByRaw($defaultOrder);

        $rows = $qb->get();
        $opts = [];
        
        foreach ( $rows as $row ) {
            $row = (array) $row;
            $opts[ $row[$pkey] ] = $row[$displayField];
        }

        return $opts;
    }
}