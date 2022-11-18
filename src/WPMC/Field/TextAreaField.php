<?php
namespace WPMC\Field;

use WPMC\FieldBase;

class TextAreaField extends FieldBase
{
    public function getDbType()
    {
        return 'TEXT';
    }

    protected function allowGenericSearch()
    {
        return true;
    }

    public function applyGenericSearchFilter(\Illuminate\Database\Query\Builder $qb, $search)
    {
        $name = $this->getName();
        $table = $this->getRootEntity()->getDatabase()->getTableName();

        $qb->orWhere("{$table}.{$name}", 'like', "%{$search}%");
    }

    public function applySpecificSearchFilter(\Illuminate\Database\Query\Builder $qb, $value)
    {
        $name = $this->getName();
        $table = $this->getRootEntity()->getDatabase()->getTableName();

        $qb->where("{$table}.{$name}", 'like', "%{$value}%");
    }

    public function formatValue($value, $item)
    {
        if ( !empty($value) ) {
            $value = substr($value, 0, 200);
        }

        return $value;
    }

    public function render()
    {
        $cols = 85;
        $rows = 10;

        ?>
        <textarea name="<?php echo $this->getName(); ?>"
            id="<?php echo $this->getName(); ?>"
            rows="<?php echo $rows; ?>"
            cols="<?php echo $cols; ?>"><?php echo $this->getValue(); ?></textarea>
        <?php
    }
}