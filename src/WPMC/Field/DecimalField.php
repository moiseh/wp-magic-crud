<?php
namespace WPMC\Field;

use Exception;
use WPMC\FieldBase;

class DecimalField extends FieldBase
{
    public function getDbType()
    {
        return 'DECIMAL(10,2)';
    }

    public function validate($value, $values)
    {
        if (!is_numeric($value) ) {
            throw new Exception( __('Invalid decimal', 'wp-magic-crud') );
        }
    }

    public function render()
    {
        ?>
        <input type="decimal"
                name="<?php echo $this->getName(); ?>"
                id="<?php echo $this->getName(); ?>"
                <?php echo $this->requiredTag(); ?>
                value="<?php echo $this->getValue(); ?>">
        <?php
    }
}