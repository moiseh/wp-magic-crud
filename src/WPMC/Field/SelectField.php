<?php
namespace WPMC\Field;

use WPMC\FieldBase;

class SelectField extends FieldBase
{
    /**
     * @var array
     * @required
     */
    private $choices;

    public function formatValue($value, $item)
    {
        if ( !empty($this->choices[$value]) ) {
            $value = $this->choices[$value];
        }

        return $value;
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

    public function toArray()
    {
        $arr = parent::toArray();
        $arr['choices'] = $this->choices;

        return $arr;
    }

    public function render()
    {
        $isRequired = $this->getRequired();
        $choices = $this->getChoices();
        $value = $this->getValue();

        ?>
        <select <?php echo $this->requiredTag(); ?>
                name="<?php echo $this->getName(); ?>"
                id="<?php echo $this->getName(); ?>">
            <?php if ( !$isRequired ): ?>
                <option value=""><?php echo sprintf('< %s >',__('none')); ?></option>
            <?php endif; ?>
            <?php foreach ( $choices as $key => $label ): ?>
                <?php $selected = ( $key == $value ) ? 'selected' : ''; ?> 
                <option value="<?php echo $key; ?>" <?php echo $selected; ?>><?php echo esc_html__($label); ?></option>
            <?php endforeach; ?>
        </select>
        <?php
    }
}