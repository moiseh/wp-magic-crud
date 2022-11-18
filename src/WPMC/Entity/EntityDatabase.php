<?php

namespace WPMC\Entity;

use Exception;
use WPMC\DB\EloquentDBFacade;
use WPMC\DB\EntityQuery;
use WPMC\Entity;

class EntityDatabase
{
    /**
     * @var Entity
     */
    private $rootEntity;

    /**
     * @var string
     * @required
     */
    private $table_name;

    /**
     * @var string
     */
    private $primary_key;

    /**
     * @var string
     * @required
     */
    private $default_order;

    /**
     * @var string
     * @required
     */
    private $display_field;

    /**
     * @var bool
     * @required
     */
    private $auto_create_tables = false;

    /**
     * @var bool
     * @required
     */
    private $track_changes = false;

    public function validateDefinitions()
    {
        $entity = $this->getRootEntity();
        $identifier = $entity->getIdentifier();
        $table = $this->getTableName();

        if ( !$this->hasPrimaryKey() && $this->getAutoCreateTables() ) {
            throw new Exception('The both attributes database.primary_key and database.auto_create_tables needs to be filled: ' . $identifier);
        }

        if ( $entity->hasDbTableCleanup() ) {
            $this->getCleanup()->validateDefinitions();
        }

        // check for complex SQL errors
        // find first record (findFirst)
        $first = EloquentDBFacade::table($table)->first();

        if ( !empty($first->id) && $this->hasPrimaryKey() ) {
            $row = $entity->findById( $first->id );
            // var_dump($row);
        }

        $newQuery = new EntityQuery($entity);
        $qb = $newQuery->buildEloquentQuery();
        $first = $qb->first();

        // validate display field
        $displayField = $this->getDisplayField();

        if ( !empty($displayField) ) {
            if ( !$entity->fieldsCollection()->hasField($displayField) ) {
                throw new Exception('Invalid display field: ' . $displayField);
            }
        }
    }

    public function toArray()
    {
        $entity = $this->getRootEntity();

        $arr = [];
        $arr['database']['table_name'] = $this->getTableName();
        $arr['database']['primary_key'] = $this->getPrimaryKey();
        $arr['database']['default_order'] = $this->getDefaultOrder();
        $arr['database']['display_field'] = $this->getDisplayField();
        $arr['database']['auto_create_tables'] = $this->getAutoCreateTables();
        $arr['database']['track_changes'] = $this->getTrackChanges();

        if ( $entity->hasDbTableCleanup() ) {
            $arr['database']['cleanup'] = $this->getCleanup()->toArray();
        }

        return $arr;
    }

    public function getRootEntity()
    {
        return $this->rootEntity;
    }

    public function setRootEntity(Entity $rootEntity)
    {
        $this->rootEntity = $rootEntity;
        return $this;
    }

    /**
     * @var DatabaseCleanup
     */
    private $cleanup;

    public function getTableName()
    {
        return $this->table_name;
    }

    public function setTableName(string $tableName)
    {
        $this->table_name = $tableName;

        return $this;
    }

    public function hasPrimaryKey() {
        return !empty($this->primary_key);
    }

    public function getPrimaryKey()
    {
        return $this->primary_key;
    }

    public function setPrimaryKey($pkey)
    {
        $this->primary_key = $pkey;
        return $this;
    }

    public function getDefaultOrder()
    {
        return $this->default_order;
    }

    public function setDefaultOrder(string $defaultOrder)
    {
        $this->default_order = $defaultOrder;
        return $this;
    }

    public function getDisplayField()
    {
        return $this->display_field;
    }

    public function setDisplayField(string $displayField)
    {
        $this->display_field = $displayField;
        return $this;
    }

    public function getAutoCreateTables()
    {
        return $this->auto_create_tables;
    }

    public function setAutoCreateTables(bool $autoCreateTables)
    {
        $this->auto_create_tables = $autoCreateTables;
        return $this;
    }

    public function getCleanup()
    {
        if ( !empty($this->cleanup) ) {
            $this->cleanup->setRootEntityDatabase($this);
        }
        
        return $this->cleanup;
    }

    public function setCleanup(DatabaseCleanup $cleanup)
    {
        $this->cleanup = $cleanup;
        return $this;
    }

    public function getTrackChanges()
    {
        return $this->track_changes;
    }

    public function setTrackChanges(bool $track_changes)
    {
        $this->track_changes = $track_changes;
        return $this;
    }
}