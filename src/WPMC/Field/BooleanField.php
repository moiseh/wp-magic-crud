<?php
namespace WPMC\Field;

use WPMC\FieldBase;
use WPMC\FieldResolver;

class BooleanField extends FieldBase
{
    public function getDbType()
    {
        return 'BOOLEAN';
    }

    public function render()
    {
        $field = FieldResolver::buildField([
            'type' => 'select',
            'name' => $this->getName(),
            'value' => $this->getValue(),
            'required' => true,
            'choices' => [1 => __('Yes'), 0 => __('No')],
        ]);

        $field->renderSafe();
    }

    public function formatValue($value, $item)
    {
        return $value ? __('Yes', 'wp-magic-crud') : __('No', 'wp-magic-crud');
    }
}