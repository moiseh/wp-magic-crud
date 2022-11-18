<?php
namespace WPMC\Field;

use WPMC\FieldBase;

class TextField extends FieldBase
{
    public function getDbType()
    {
        return 'VARCHAR(255)';
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

    public function render()
    {
        ?>
        <input type="text"
                name="<?php echo $this->getName(); ?>"
                id="<?php echo $this->getName(); ?>"
                value="<?php echo $this->getValue(); ?>"
                size="60">
        <?php
    }
}