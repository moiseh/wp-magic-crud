<?php
namespace WPMC;

use Exception;
use WPMC\DB\EloquentDBFacade;

abstract class FieldBase
{
    /**
     * @var Entity
     */
    private $rootEntity;

    /**
     * @var string
     * @required
     */
    private $name;

    /**
     * @var string
     * @required
     */
    private $type;

    private $required;
    private $default;
    private $value;
    private $label;
    private $customDisplay;

    private $isCreatable;
    private $isEditable;
    private $isListable;
    private $isSortable;
    private $isViewable;

    public function __construct()
    {
    }

    public function toArray() {
        $field = [];
        // $field['name'] = $this->getName();
        $field['type'] = $this->getType();
        $field['label'] = $this->getLabel();

        if ( isset($this->required) ) $field['required'] = $this->getRequired();
        if ( isset($this->default) ) $field['default'] = $this->getDefault();
        if ( isset($this->value) ) $field['value'] = $this->getValue();
        if ( isset($this->customDisplay) ) $field['custom_display'] = $this->getCustomDisplay();

        if ( isset($this->isCreatable) ) $field['creatable'] = $this->isCreatable();
        if ( isset($this->isEditable) ) $field['editable'] = $this->isEditable();
        if ( isset($this->isListable) ) $field['listable'] = $this->isListable();
        if ( isset($this->isSortable) ) $field['sortable'] = $this->isSortable();
        if ( isset($this->isViewable) ) $field['viewable'] = $this->isViewable();

        return $field;
    }

    public function setRootEntity(Entity $entity) {
        $this->rootEntity = $entity;
    }

    public function getRootEntity() {
        return $this->rootEntity;
    }

    public function getName() {
        return $this->name;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    public function setLabel($label)
    {
        $this->label = $label;
        return $this;
    }

    public function setRequired($required)
    {
        $this->required = $required;
        return $this;
    }

    public function setListable($isListable)
    {
        $this->isListable = $isListable;

        return $this;
    }

    public function setCustomDisplay($customDisplay)
    {
        $this->customDisplay = $customDisplay;
        return $this;
    }

    public function getType() {
        return $this->type;
    }

    public function getLabel() {
        return $this->label;
    }

    public function getDefault() {
        return $this->default;
    }

    public function getRequired() {
        return $this->required;
    }

    public function getValue() {
        return $this->value;
    }

    public function setValue($value) {
        $this->value = $value;
    }

    public function hasValue() {
        return !empty($this->value);
    }

    public function getCustomDisplay() {
        return $this->customDisplay;
    }

    public function isCreatable() {
        return isset($this->isCreatable) ? $this->isCreatable : true;
    }

    public function isEditable() {
        return isset($this->isEditable) ? $this->isCreatable : true;
    }

    public function isListable() {
        return isset($this->isListable) ? $this->isListable : true;
    }

    public function isSortable() {
        $isSortable = isset($this->isSortable) ? $this->isSortable : true;
        return $this->isPrimitiveType() && $isSortable;
    }

    public function isViewable() {
        return isset($this->isSortable) ? $this->isSortable : true;
    }

    public function defaultValue() {
        return $this->getValue() ?: $this->getDefault();
    }

    public function validateDefinitions() {
        $customDisplay = $this->getCustomDisplay();

        if ( !empty($customDisplay) && !function_exists($customDisplay) ) {
            throw new Exception('Invalid custom_display function: ' . $customDisplay);
        }
    }

    public function hasRenderer() {
        return true;
    }

    public function getDbType() {
        return 'VARCHAR(255)';
    }

    public function getDbReferences() {
        return '';
    }

    public function getForeignKeyStatement() {
        return '';
    }

    public function isPrimitiveType() {
        return true;
    }

    protected function allowGenericSearch() {
        return false;
    }

    public function afterDbTableCreated() {
        
    }

    public function alterEloquentQuery(\Illuminate\Database\Query\Builder $qb)
    {
        if ( $this->isPrimitiveType() ) {
            $name = $this->getName();
            $entity = $this->getRootEntity();
            $table = $entity->getDatabase()->getTableName();

            $qb->addSelect(EloquentDBFacade::raw("{$table}.{$name}"));
        }
    }

    public function applyGenericSearchFilter(\Illuminate\Database\Query\Builder $qb, $search)
    {
        if ( $this->isPrimitiveType() && $this->allowGenericSearch() ) {
            $name = $this->getName();
            $table = $this->getRootEntity()->getDatabase()->getTableName();

            $qb->orWhere("{$table}.{$name}", $search);
        }
    }

    public function applySpecificSearchFilter(\Illuminate\Database\Query\Builder $qb, $value)
    {
        if ( $this->isPrimitiveType() ) {
            $name = $this->getName();
            $table = $this->getRootEntity()->getDatabase()->getTableName();

            $qb->where("{$table}.{$name}", $value);
        }
    }

    public function alterEntityFind($row = [])
    {
        return $row;
    }

    public function afterEntityDataSaved($item = []) {
        return true;
    }

    public function formatValue($value, $item)
    {
        $customDisplay = $this->getCustomDisplay();

        if ( !empty($customDisplay) ) {
            $value = $customDisplay( $value, $item );
        }
        
        return $value;
    }

    public function formatListTableRows($rows)
    {
        $name = $this->getName();

        foreach ( $rows as $key => $row ) {
            if ( array_key_exists($name, $row) ) {
                $value = $row[$name];
                $rows[$key][$name] = $this->formatValue($value, $row);
            }
        }

        return $rows;
    }

    public function render() {
        
    }

    public function renderSafe() {
        if ( $this->hasRenderer()) {
            $this->render();
        }
    }

    public function renderWithLabel() {
        ?>
        <p>
            <label for="<?php echo $this->getName(); ?>">
                <?php echo esc_html__($this->getLabel()); ?>:
            </label>
            <br>
            <?php $this->renderSafe(); ?>
        </p>
        <?php
    }

    protected function requiredTag() {
        return $this->getRequired() ? 'required' : '';
    }
}