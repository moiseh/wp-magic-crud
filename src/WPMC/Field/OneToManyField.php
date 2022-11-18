<?php
namespace WPMC\Field;

use Exception;
use WPMC\DB\EntityQuery;
use WPMC\FieldBase;
use WPMC\UI\InlineFieldsTable;
use WPMC\UI\OneToManyGridFormatter;

class OneToManyField extends FieldBase
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
    private $ref_column;

    /**
     * @var bool
     * @required
     */
    private $delete_related_data;

    /**
     * @var bool
     * @required
     */
    private $is_manageable;

    public function validateDefinitions()
    {
        // this will throw if entity not exists
        $refEntity = $this->getRefEntity();

        // check if related entity table column exists
        $table = $refEntity->getDatabase()->getTableName();
        $column = $this->getRefColumn();

        if ( !psTableColumnExists($table, $column) ) {
            throw new Exception("The column not exists for the table {$table} => {$column}");
        }

        parent::validateDefinitions();
    }

    public function toArray()
    {
        $arr = parent::toArray();
        $arr['ref_entity'] = $this->ref_entity;
        $arr['ref_column'] = $this->ref_column;
        $arr['is_manageable'] = $this->is_manageable;
        $arr['delete_related_data'] = $this->delete_related_data;

        return $arr;
    }

    public function getRefColumn()
    {
        return $this->ref_column;
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

    public function setRefColumn($refColumn)
    {
        $this->ref_column = $refColumn;
        return $this;
    }

    public function setDeleteRelatedData(bool $delete)
    {
        $this->delete_related_data = $delete;
        return $this;
    }

    public function getDeleteRelatedData()
    {
        return $this->delete_related_data;
    }

    public function setIsManageable(bool $isManageable)
    {
        $this->is_manageable = $isManageable;
        return $this;
    }

    public function getIsManageable()
    {
        return $this->is_manageable;
    }

    public function isPrimitiveType()
    {
        return false;
    }

    /**
     * @return \Illuminate\Database\Query\Builder
     */
    public function buildRelatedRowsQuery($relationId) {
        $fieldRefColumn = $this->getRefColumn();

        $refEntity = $this->getRefEntity();
        $refTable = $refEntity->getDatabase()->getTableName();

        $entityQuery = new EntityQuery($refEntity);

        $query = $entityQuery->buildEloquentQuery();
        $query->where("{$refTable}.{$fieldRefColumn}", $relationId);
        $query->orderByRaw( $entityQuery->getDefaultOrderCol() );

        return $query;
    }

    private function getRelatedRows($relationId) {
        $query = $this->buildRelatedRowsQuery($relationId);
        $rows = convertStdToArray( $query->get() );

        return $rows;
    }

    public function alterEntityFind($row = [])
    {
        $relationId = $row['id'];
        $name = $this->getName();

        $row[$name] = $this->getRelatedRows($relationId);

        return $row;
    }

    public function afterEntityDataSaved($item = [])
    {
        $refEntity = $this->getRefEntity();
        $groupName = $this->getName();
        $fieldRefColumn = $this->getRefColumn();
        $pkey = $refEntity->getDatabase()->getPrimaryKey();
        $relationId = $item[$pkey];
        $relatedData = !empty($_REQUEST[$groupName]) ? $_REQUEST[$groupName] : null;

        if ( empty($relatedData) && !empty($item[$groupName]) ) {
            $relatedData = $item[$groupName];
        }

        if ( !empty($relatedData) && ( $relationId > 0 ) )
        {
            $refItems = [];
            foreach ( $relatedData as $refItem ) {
                $refItems[] = array_map('sanitize_text_field', $refItem);
            }

            $savedIds = [];

            foreach ( $refItems as $refItem ) {
                $refItem[$fieldRefColumn] = $relationId;
                $savedIds[] = $refEntity->saveDbData($refItem);
            }
            
            // delete removed data from form
            $relatedRows = $this->getRelatedRows($relationId);

            foreach ( $relatedRows as $row ) {
                if ( !in_array($row[$pkey], $savedIds) ) {
                    $refEntity->delete($row[$pkey]);
                }
            }
        }

        return true;
    }

    public function deleteRelatedData($id, $item = [])
    {
        $refEntity = $this->getRefEntity();
        $refPkey = $refEntity->getDatabase()->getPrimaryKey();
        $label = $this->getLabel();
        $groupName = $this->getName();
        $relatedData = $item[$groupName];

        if ( empty($relatedData) ) {
            return;
        }

        if ( !$this->getDeleteRelatedData() ) {
            throw new Exception("The field {$label} does not allow to removed related data");
        }

        foreach ( $relatedData as $relatedRow ) {
            $relatedId = $relatedRow[ $refPkey ];
            $refEntity->delete($relatedId);
        }
    }

    public function formatListTableRows($rows)
    {
        $formatter = new OneToManyGridFormatter($this);
        return $formatter->formatRows($rows);
    }

    public function renderWithLabel()
    {
        if ( $this->getIsManageable() ) {
            return parent::renderWithLabel();
        }
    }

    public function render() {
        $refEntity = $this->getRefEntity();
        $refFields = $refEntity->getFieldsObjects();
        $refColumn = $this->getRefColumn();
        $groupName = $this->getName();
        $refItems = $this->getValue() ?: [];
        $addTitle = sprintf(__("Add %s", 'wp-magic-crud'), $refEntity->getMenu()->getSingular());

        $inlineFields = new InlineFieldsTable($refFields, $refColumn, $groupName);
        $inlineFields->setCurrentItems($refItems);
        $inlineFields->setAddTitle($addTitle);
        $inlineFields->renderFieldsTable();
    }
}