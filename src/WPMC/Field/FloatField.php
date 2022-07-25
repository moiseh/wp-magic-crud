<?php
namespace WPMC\Field;

use Exception;
use WPMC\FieldBase;

class FloatField extends FieldBase
{
    public function getDbType()
    {
        return 'FLOAT';
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
        <input type="float"
                name="<?php echo $this->getName(); ?>"
                id="<?php echo $this->getName(); ?>"
                <?php echo $this->requiredTag(); ?>
                value="<?php echo $this->getValue(); ?>">
        <?php
    }
}