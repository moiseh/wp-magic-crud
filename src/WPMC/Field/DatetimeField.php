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