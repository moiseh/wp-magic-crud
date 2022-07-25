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
        return current(explode(' ', $entity->getDatabase()->getDefaultOrder()));
    }

    public function getDefaultOrderMode() {
        $entity = $this->entity;
        $exp = explode(' ', $entity->getDatabase()->getDefaultOrder());
        
        if ( count($exp) > 1 ) {
            return $exp[1];
        }

        return 'ASC';
    }

    /**
     * @return \Illuminate\Database\Query\Builder
     */
    public function buildEloquentQuery($callFieldsAlter = true)
    {
        $entity = $this->entity;
        $table = $entity->getDatabase()->getTableName();
        $fields = $entity->getFieldsObjects();

        /**
         * @var \Illuminate\Database\Query\Builder
         */
        $qb = EloquentDBFacade::table($table);

        if ( $entity->getDatabase()->hasPrimaryKey() ) {
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
        $entity = $this->entity;
        $table = $entity->getDatabase()->getTableName();
        $pkey = $entity->getDatabase()->getPrimaryKey();

        $qb = $this->buildEloquentQuery()->where("{$table}.{$pkey}", $id);
        $row = convertStdToArray( $qb->first() );

        if ( empty($row) && $throwWhenNotExists ) {
            throw new Exception('Record not found', 404);
        }

        $fields = $entity->getFieldsObjects();

        if ( !empty($row[$pkey])) {
            foreach ( $fields as $field ) {
                $row = $field->alterEntityFind($row);
            }
        }

        $row = apply_filters('wpmc_entity_find', $row, $entity);

        return $row;
    }

    public function findByIds($ids = []) {
        if ( empty($ids) ) {
            return [];
        }

        $entity = $this->entity;
        $table = $entity->getDatabase()->getTableName();
        $pkey = $entity->getDatabase()->getPrimaryKey();

        $qb = $this->buildEloquentQuery()->whereIn("{$table}.{$pkey}", $ids);
        $rows = convertStdToArray( $qb->get() );

        return $rows;
    }
}