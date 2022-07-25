<?php
namespace WPMC\Field;

use Exception;
use WPMC\FieldBase;

class IntegerField extends FieldBase
{
    public function getDbType()
    {
        return 'INTEGER';
    }

    public function validate($value, $values)
    {
        if (!is_numeric($value) ) {
            throw new Exception( __('Invalid number', 'wp-magic-crud') );
        }
    }

    public function render()
    {
        ?>
        <input type="number"
                name="<?php echo $this->getName(); ?>"
                id="<?php echo $this->getName(); ?>"
                <?php echo $this->requiredTag(); ?>
                value="<?php echo $this->getValue(); ?>">
        <?php
    }
}