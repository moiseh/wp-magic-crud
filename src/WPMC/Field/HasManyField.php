<?php
namespace WPMC\Field;

use Exception;
use WPMC\DB\EntityQuery;
use WPMC\FieldBase;
use WPMC\FieldResolver;
use WPMC\UI\CommonHtml;

class HasManyField extends FieldBase
{
    /**
     * @var string
     * @required
     */
    private $ref_entity;

    /**
     * @var string
     * @required
     */
    private $pivot_table;

    /**
     * @var string
     * @required
     */
    private $pivot_left;

    /**
     * @var string
     * @required
     */
    private $pivot_right;

    public function toArray()
    {
        $arr = parent::toArray();
        $arr['ref_entity'] = $this->ref_entity;
        $arr['pivot_table'] = $this->getPivotTable();
        $arr['pivot_left'] = $this->getPivotLeft();
        $arr['pivot_right'] = $this->getPivotRight();

        return $arr;
    }

    public function isPrimitiveType()
    {
        return false;
    }

    public function getRefEntity() {
        return wpmc_get_entity( $this->ref_entity );
    }

    public function getPivotTable() {
        return $this->pivot_table;
    }

    public function getPivotLeft() {
        return $this->pivot_left;
    }

    public function getPivotRight() {
        return $this->pivot_right;
    }

    public function setRefEntity($refEntity)
    {
        $this->ref_entity = $refEntity;
        return $this;
    }

    public function setPivotTable($pivotTable)
    {
        $this->pivot_table = $pivotTable;
        return $this;
    }

    public function setPivotLeft($pivotLeft)
    {
        $this->pivot_left = $pivotLeft;
        return $this;
    }

    public function setPivotRight($pivotRight)
    {
        $this->pivot_right = $pivotRight;
        return $this;
    }

    public function validateDefinitions()
    {
        $pivotTable = $this->getPivotTable();
        $pivotLeft = $this->getPivotLeft();
        $pivotRight = $this->getPivotRight();

        if ( !psTableExists($pivotTable) ) {
            throw new Exception('Pivot table not exists: ' . $pivotTable);
        }

        if ( !psTableColumnExists($pivotTable, $pivotLeft) ) {
            throw new Exception('Pivot table left column not exists: ' . $pivotTable . '.' . $pivotLeft);
        }

        if ( !psTableColumnExists($pivotTable, $pivotRight) ) {
            throw new Exception('Pivot table right column not exists: ' . $pivotTable . '.' . $pivotRight);
        }

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

    public function afterDbTableCreated()
    {
        global $wpdb;

        $charset = $wpdb->get_charset_collate();

        $rootEntity = $this->getRootEntity();
        $leftTable = $rootEntity->getDatabase()->getTableName();
        
        $refEntity = $this->getRefEntity();
        $rightTable = $refEntity->getDatabase()->getTableName();

        $pivotTable = $this->getPivotTable();
        $pivotLeft = $this->getPivotLeft();
        $pivotRight = $this->getPivotRight();

        $sql = "CREATE TABLE {$pivotTable} (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `{$pivotLeft}` INTEGER NOT NULL,
            `{$pivotRight}` INTEGER NOT NULL,
            FOREIGN KEY (`{$pivotLeft}`) REFERENCES {$leftTable}(`id`),
            FOREIGN KEY (`{$pivotRight}`) REFERENCES {$rightTable}(`id`),
            PRIMARY KEY (`id`)
        ) {$charset}";

        dbDelta($sql);
    }

    private function getIdsValues()
    {
        $values = $this->getValue();

        // check if it's full related data
        if ( is_array($values) && ( count($values, COUNT_RECURSIVE) != count($values) ) ) {
            return $this->extractRelatedIds($values);
        }

        return $values;
    }

    private function listRelationIds($leftColumnId) {
        global $wpdb;

        $pivotTable = $this->getPivotTable();
        $pivotLeft = $this->getPivotLeft();
        $pivotRight = $this->getPivotRight();

        $sql = $wpdb->prepare("SELECT * FROM {$pivotTable} WHERE {$pivotLeft} = %d", $leftColumnId);
        $rows = $wpdb->get_results($sql, ARRAY_A);
        psCheckDbError($rows);
        
        $list = [];

        foreach ( $rows as $_row ) {
            $list[] = $_row[ $pivotRight ];
        }

        return $list;
    }

    private function findRelatedRows($relatedIds = [])
    {
        $refEntity = $this->getRefEntity();

        $qb = new EntityQuery($refEntity);
        return $qb->findByIds($relatedIds);
    }

    private function extractRelatedIds($relatedRows = [])
    {
        $refEntity = $this->getRefEntity();
        $pkey = $refEntity->getDatabase()->getPrimaryKey();
        $ids = [];

        foreach ( $relatedRows as $row ) {
            $ids[] = $row[$pkey];
        }

        return $ids;
    }

    public function alterEntityFind($row = [])
    {
        $rootEntity = $this->getRootEntity();
        $pkey = $rootEntity->getDatabase()->getPrimaryKey();
        $name = $this->getName();
        $relatedIds = $this->listRelationIds($row[$pkey]);

        $row[$name] = $this->findRelatedRows($relatedIds);

        return $row;
    }

    public function afterEntityDataSaved($item = [])
    {
        global $wpdb;

        $pivotTable = $this->getPivotTable();
        $pivotLeft = $this->getPivotLeft();
        $pivotRight = $this->getPivotRight();
        $name = $this->getName();
        $relatedData = (array) $item[$name];

        $delete = $wpdb->delete($pivotTable, array($pivotLeft => $item['id']));
        psCheckDbError($delete);

        foreach ( $relatedData as $referenceId ) {
            $row = [
                $pivotLeft => $item['id'],
                $pivotRight => $referenceId,
            ];

            $save = $wpdb->insert($pivotTable, $row);
            psCheckDbError($save);
        }
    }

    public function formatListTableRows($rows)
    {
        $refEntity = $this->getRefEntity();
        $name = $this->getName();

        foreach ( $rows as $key => $row ) {
            $ids = $this->listRelationIds($row['id']);

            if ( empty($ids) ) {
                continue;
            }

            $list = $refEntity->buildOptions($ids);
            $rows[$key][$name] = CommonHtml::buildHtmlList($list);
        }

        return $rows;
    }

    public function render()
    {
        $refEntity = $this->getRefEntity();

        $field = FieldResolver::buildField([
            'type' => 'checkbox_multi',
            'name' => $this->getName(),
            'value' => $this->getIdsValues(),
            'choices' => $refEntity->buildOptions(),
        ]);

        $field->render();
    }
}