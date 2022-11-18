<?php
namespace WPMC\Field;

use Exception;
use WPMC\DB\EloquentDBFacade;
use WPMC\FieldBase;
use WPMC\FieldResolver;

class BelongsToField extends FieldBase
{
    /**
     * @var string
     * @required
     */
    private $ref_entity;

    public function getDbType()
    {
        return 'INTEGER';
    }

    public function getForeignKeyStatement()
    {
        $refEntity = $this->getRefEntity();
        $refTable = $refEntity->getDatabase()->getTableName();
        $thisColumn = $this->getName();

        if ( $thisColumn != 'video_build_id' ) {
            // deve fazer uma checagem se a FK ja existe, caso exista, nao executar pois causa erro mysql
            // return "FOREIGN KEY (`{$thisColumn}`) REFERENCES {$refTable}(id),";
        }
    }

    public function getRefEntity()
    {
        return wpmc_get_entity( $this->ref_entity );
    }

    public function setRefEntity($refEntity)
    {
        $this->ref_entity = $refEntity;
        return $this;
    }

    public function getRefEntityTable()
    {
        $refTable = $this->getRefEntity()->getDatabase()->getTableName();

        return $refTable;
    }

    public function validateDefinitions()
    {
        // this will throw if entity not exists
        $refEntity = $this->getRefEntity();
        $refTable = $refEntity->getDatabase()->getTableName();

        if ( !psTableExists($refTable) ) {
            throw new Exception('Reference table not exists: ' . $refTable);
        }

        // check for slow or invalid queries
        $refEntity->buildOptions();

        parent::validateDefinitions();
    }

    public function alterEloquentQuery(\Illuminate\Database\Query\Builder $qb)
    {
        parent::alterEloquentQuery($qb);

        $name = $this->getName();

        $refEntity = $this->getRefEntity();
        $refTable = $refEntity->getDatabase()->getTableName();

        $rootEntity = $this->getRootEntity();
        $rootTable = $rootEntity->getDatabase()->getTableName();
        
        $identifier = $refEntity->getIdentifier();
        $displayField = $refEntity->getDatabase()->getDisplayField();

        $qb->addSelect(EloquentDBFacade::raw("{$refTable}.{$displayField} AS {$identifier}"));
        $qb->leftJoin($refTable, "{$rootTable}.{$name}", '=', "{$refTable}.id");
    }

    public function applyGenericSearchFilter(\Illuminate\Database\Query\Builder $qb, $search)
    {
        $refEntity = $this->getRefEntity();
        $refTable = $refEntity->getDatabase()->getTableName();
        $displayField = $refEntity->getDatabase()->getDisplayField();

        $qb->orWhere("{$refTable}.{$displayField}", 'like', "%{$search}%");
    }

    public function formatListTableRows($rows)
    {
        $name = $this->getName();
        $refEntity = $this->getRefEntity();
        $refIdentifier = $refEntity->getIdentifier();

        foreach ( $rows as $key => $row ) {
            if ( !empty($row[$refIdentifier]) ) {
                $rows[$key][$name] = $row[$refIdentifier];
            }
        }

        return $rows;
    }

    public function toArray()
    {
        $arr = parent::toArray();
        $arr['ref_entity'] = $this->ref_entity;

        return $arr;
    }

    public function render()
    {
        $refEntity = $this->getRefEntity();

        $field = FieldResolver::buildField([
            'name' => $this->getName(),
            'type' => 'select',
            'value' => $this->getValue(),
            'choices' => $refEntity->buildOptions(),
        ]);

        $field->render();
    }
}