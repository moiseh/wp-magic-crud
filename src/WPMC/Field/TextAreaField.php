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
        $rows = 5;

        ?>
        <textarea name="<?php echo $this->getName(); ?>"
            rows="<?php echo $rows; ?>"
            cols="<?php echo $cols; ?>"><?php echo $this->getValue(); ?></textarea>
        <?php
    }
}