<?php
namespace WPMC\Field;

use Exception;
use WPMC\FieldBase;

class DatetimeField extends FieldBase
{
    public function getDbType()
    {
        return 'DATETIME';
    }

    public function applyGenericSearchFilter(\Illuminate\Database\Query\Builder $qb, $search)
    {
        $name = $this->getName();
        $table = $this->getRootEntity()->getDatabase()->getTableName();

        $qb->orWhere("{$table}.{$name}", 'like', "%{$search}%");
        // $qb->orWhereRaw("DATE({$table}.{$name})", $search);
    }

    public function render()
    {
        ?>
        <input type="text"
                name="<?php echo $this->getName(); ?>"
                id="<?php echo $this->getName(); ?>"
                <?php echo $this->requiredTag(); ?>
                value="<?php echo $this->getValue(); ?>">
        <?php
    }
}