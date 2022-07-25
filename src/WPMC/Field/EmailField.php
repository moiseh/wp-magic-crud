<?php
namespace WPMC\Field;

use Exception;

class EmailField extends TextField
{
    public function validate($value, $values)
    {
        if (!is_email($value) ) {
            throw new Exception( __('Invalid e-mail', 'wp-magic-crud') );
        }
    }

    public function render()
    {
        ?>
        <input type="email"
                name="<?php echo $this->getName(); ?>"
                id="<?php echo $this->getName(); ?>"
                <?php echo $this->requiredTag(); ?>
                value="<?php echo $this->getValue(); ?>">
        <?php
    }
}