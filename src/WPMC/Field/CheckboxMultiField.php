<?php
namespace WPMC\Field;

use Exception;
use WPMC\FieldBase;

class CheckboxMultiField extends FieldBase
{
    /**
     * @var array
     * @required
     */
    private $choices;

    public function __construct($field = [])
    {
        // if ( empty($field['choices'])) {
        //     throw new Exception('Missing choices');
        // }

        // $this->choices = $field['choices'];

        parent::__construct($field);
    }

    private function getChoices()
    {
        return $this->choices;
    }

    public function setChoices($choices)
    {
        $this->choices = $choices;
        return $this;
    }

    private function getValues()
    {
        return array_filter( (array) $this->getValue() );
    }

    public function render()
    {
        $list = $this->getChoices();
        $values = $this->getValues();
        $name = $this->getName();

        ?>
        <div class="checkboxes">
        <?php foreach ( $list as $key => $label ): ?>
            <?php $checked = ( in_array($key, $values) ) ? 'checked' : ''; ?> 
            <label for="<?php echo $key; ?>">
                <input type="checkbox" name="<?php echo "{$name}[]"; ?>" value="<?php echo $key; ?>" <?php echo $checked; ?>/>
                <?php echo esc_html__($label); ?>
            </label>
            <br/>
        <?php endforeach; ?>
        </div>
        <?php
    }
}