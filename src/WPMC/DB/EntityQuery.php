<?php

namespace WPMC\DB;

use Exception;
use WPMC\Entity;

class EntityQuery
{
    public function __construct(private Entity $entity)
    {
    }

    public function getDefaultOrderCol() {
        $entity = $this->entity;
        return $entity->getDatabase()->getDefaultOrder();
        // return current(explode(' ', $entity->getDatabase()->getDefaultOrder()));
    }

    public function getDefaultOrderWithoutMode()
    {
        $defaultOrder = $this->getDefaultOrderCol();
        
        if ( substr($defaultOrder, -4) == 'DESC' ) {
            return substr($defaultOrder, 0, -4);
        }
        else if ( substr($defaultOrder, -3) == 'ASC' ) {
            return substr($defaultOrder, 0, -3);
        }

        return $defaultOrder;
    }

    public function getDefaultOrderMode() {
        $entity = $this->entity;
        $defaultOrder = $this->getDefaultOrderCol();
        
        if ( substr($defaultOrder, -4) == 'DESC' ) {
            return 'DESC';
        }
        else if ( substr($defaultOrder, -3) == 'ASC' ) {
            return 'ASC';
        }

        return 'ASC';
    }

    /**
     * @return \Illuminate\Database\Query\Builder
     */
    public function buildEloquentQuery($callFieldsAlter = true)
    {
        $entity = $this->entity;
        $db = $entity->getDatabase();
        $table = $db->getTableName();
        $fields = $entity->getFieldsObjects();

        /**
         * @var \Illuminate\Database\Query\Builder
         */
        $qb = EloquentDBFacade::table($table);

        if ( $db->hasPrimaryKey() ) {
            $qb->select(EloquentDBFacade::raw("{$table}.id"));
        }

        if ( $callFieldsAlter ) {
            foreach ( $fields as $field ) {
                $field->alterEloquentQuery($qb);
            }
        }

        return $qb;
    }
    
    /**
     * @return \Illuminate\Database\Query\Builder
     */
    public function buildQueryWithSearch($search = null, $searchFields = [])
    {
        $qb = $this->buildEloquentQuery();

        if ( !empty($search) ) {
            $this->applyEloquentSearch($qb, $search);
        }
        
        if ( !empty($searchFields) ) {
            $this->applySearchFields($qb, $searchFields);
        }

        return $qb;
    }

    /**
     * @return \Illuminate\Database\Query\Builder
     */
    private function applyEloquentSearch(\Illuminate\Database\Query\Builder $qb, $search)
    {
        if ( empty($search) ) {
            return $qb;
        }

        $entity = $this->entity;
        $fields = $entity->getFieldsObjects();
        $search = sanitize_text_field($search);

        $qb->where(function($query) use($fields, $search) {
            foreach ( $fields as $field ) {
                $field->applyGenericSearchFilter($query, $search);
            }
        });

        return $qb;
    }

    /**
     * @return \Illuminate\Database\Query\Builder
     */
    private function applySearchFields(\Illuminate\Database\Query\Builder $qb, $searchFields = [])
    {
        if ( empty($searchFields) ) {
            return $qb;
        }

        $entity = $this->entity;

        foreach ( $searchFields as $name => $value ) {
            if ( $entity->fieldsCollection()->hasField($name) ) {
                $value = sanitize_text_field($value);

                $field = $entity->fieldsCollection()->getFieldObj($name);
                $field->applySpecificSearchFilter($qb, $value);
            }
        }

        return $qb;
    }

    public function paginateData(\Illuminate\Database\Query\Builder $qb, $limit, $page = 1)
    {
        $entity = $this->entity;
        $paginator = $qb->paginate($limit, ['*'], 'page', $page);
        $fields = $entity->getFieldsObjects();

        $paginator->getCollection()->transform(function ($row) use($fields) {

            foreach ( $fields as $field ) {
                $row = (array) $row;
                $row = $field->alterEntityFind($row);

                // $rows = $field->formatListTableRows([ $row ]);
                // $row = $rows[0];
            }

            return $row;
        });

        return $paginator;
    }

    public function findByEntityId($id, $throwWhenNotExists = false) {
        $rows = $this->findByIds([$id]);

        if ( empty($rows[0]) ) {
            if ( $throwWhenNotExists ) {
                throw new Exception('Record not found', 404);
            }
            
            return null;
        }

        return $this->prepareRow($rows[0]);
    }

    public function findByIds($ids = [])
    {
        if ( empty($ids) ) {
            return [];
        }

        $entity = $this->entity;
        $db = $entity->getDatabase();
        $table = $db->getTableName();
        $pkey = $db->getPrimaryKey();

        $query = $this->buildEloquentQuery();
        $qb = $query->whereIn("{$table}.{$pkey}", $ids);
        $rows = convertStdToArray( $qb->get() );

        foreach ( $rows as $key => $row ) {
            $rows[$key] = $this->prepareRow($row);
        }

        return $rows;
    }

    private function prepareRow($row = [])
    {
        $entity = $this->entity;
        $db = $entity->getDatabase();
        $pkey = $db->getPrimaryKey();
        $fields = $entity->getFieldsObjects();

        if ( !empty($row[$pkey])) {
            foreach ( $fields as $field ) {
                $row = $field->alterEntityFind($row);
            }
        }

        $row = apply_filters('wpmc_entity_find', $row, $entity);

        return $row;
    }
}