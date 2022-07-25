<?php
namespace WPMC\Field;

use WPMC\FieldBase;

class DateField extends FieldBase
{
    public function getDbType()
    {
        return 'DATE';
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