<?php
class WPMC_Field_Common {
    public function initHooks() {
        add_filter('wpmc_validation_errors', array($this, 'validateFormData'), 10, 2);
        add_action('wpmc_field_render', array($this, 'renderCommonFieldTypes'), 10, 2);
        add_action('wpmc_db_creating_fields', array($this, 'defineDbCommonFieldTypes'), 10, 2);
        add_filter('wpmc_entity_list', array($this, 'entityList'), 10, 2);
    }


    function renderCommonFieldTypes($field = [], $entity = null) {
        switch($field['type']) {
            case 'textarea':
                $this->textarea($field);
            break;
            case 'integer':
                $this->integer($field);
            break;
            case 'email':
                $this->email($field);
            break;
            case 'text':
                $this->text($field);
            break;
            case 'checkbox_multi':
                $this->checkbox_multi($field);
            break;
            case 'select':
                $this->select($field);
            break;
            case 'boolean':
                $field['choices'] = [1 => __('Yes'), 0 => __('No')];
                $this->select($field);
            break;
        }
    }

    function entityList($rows, WPMC_Entity $entity) {
        foreach ( $entity->get_listable_fields() as $name => $field ) {
            switch ( $field['type'] ) {
                case 'select':

                    if ( !empty($field['choices']) ) {
                        foreach ( $rows as $key => $row ) {
                            if ( !empty($row[$name]) && !empty($field['choices'][ $row[$name] ]) ) {
                                $rows[$key][$name] = $field['choices'][ $row[$name] ];
                            }
                        }
                    }
                break;
            }
        }

        return $rows;
    }

    function validateFormData($errors, $fields = []) {
        foreach ( $fields as $name => $field ) {
            if ( !empty($_REQUEST[$name]) ) {
                switch($field['type']) {
                    case 'email':
                        if (!is_email($_REQUEST[$name]) ) {
                            $errors[$name] = __('Invalid e-mail', 'wp-magic-crud');
                        }
                    break;
                    case 'integer':
                        if (!is_numeric($_REQUEST[$name]) ) {
                            $errors[$name] = __('Invalid number', 'wp-magic-crud');
                        }
                    break;
                    case 'text':
                        // $errors[$name] = __('Test validation error', 'wp-magic-crud');
                    break;
                }   
            }
        }

        return $errors;
    }


    function defineDbCommonFieldTypes($fields = [], $table) {
        foreach ( $fields as $name => $field ) {
            switch($field['type']) {
                case 'textarea':
                    $fields[$name]['db_type'] = 'TEXT';
                break;
                case 'integer':
                    $fields[$name]['db_type'] = 'INTEGER';
                break;
                case 'boolean':
                    $fields[$name]['db_type'] = 'BOOLEAN';
                break;
                case 'text':
                    $fields[$name]['db_type'] = 'VARCHAR(255)';
                break;
            }
        }

        return $fields;
    }

    public function text($field) {
        $attr = $this->buildHtmlAttributes($field);

        ?>
        <input type="text" <?php echo $attr; ?>>
        <?php
    }

    public function email($field) {
        $attr = $this->buildHtmlAttributes($field);

        ?>
        <input type="email" <?php echo $attr; ?>>
        <?php
    }

    public function integer($field) {
        $attr = $this->buildHtmlAttributes($field);

        ?>
        <input type="number" <?php echo $attr; ?>>
        <?php
    }

    public function textarea($field) {
        $value = $field['value'];
        unset($field['value']);

        if ( empty($field['cols']) ) $field['cols'] = 85;
        if ( empty($field['rows']) ) $field['rows'] = 3;
        if ( empty($field['maxlength']) ) $field['maxlength'] = 240;
        
        $attr = $this->buildHtmlAttributes($field);

        ?>
        <textarea <?php echo $attr; ?>><?php echo $value; ?></textarea>
        <?php
    }

    public function select($field) {
        $isRequired = ( !empty($field['required']) && $field['required'] );
        $values = $field['choices'];
        $value = $this->getDefaultValue($field);
        $attr = $this->buildHtmlAttributes($field);

        ?>
        <select <?php echo $attr; ?>>
            <?php if ( !$isRequired ): ?>
                <option value=""><?php echo sprintf('< %s >',__('none')); ?></option>
            <?php endif; ?>
            <?php foreach ( $values as $key => $label ): ?>
                <?php $selected = ( $key == $value ) ? 'selected' : ''; ?> 
                <option value="<?php echo $key; ?>" <?php echo $selected; ?>><?php echo $label; ?></option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    public function checkbox_multi($field) {
        $list = $field['choices'];
        $values = !empty($field['value']) ? $field['value'] : [];
        $name = $field['name'];

        ?>
        <div class="checkboxes">
        <?php foreach ( $list as $key => $label ): ?>
            <?php $checked = ( in_array($key, $values) ) ? 'checked' : ''; ?> 
            <label for="<?php echo $key; ?>">
                <input type="checkbox" name="<?php echo "{$name}[]"; ?>" value="<?php echo $key; ?>" <?php echo $checked; ?>/>
                <?php echo $label; ?>
            </label>
            <br/>
        <?php endforeach; ?>
        </div>
        <?php
    }

    public function getDefaultValue($field) {
        if ( !empty($field['value']) ) {
            return $field['value'];
        }

        if ( !empty($field['default']) ) {
            return $field['default'];
        }

        return null;
    }

    private function buildHtmlAttributes($field = []) {
        $htmlAttr = '';

        // allowed html field types
        $allowedTypes = ['name', 'id', 'value', 'rows', 'cols', 'maxlength'];
        
        // build the html attributes
        foreach ( $field as $key => $opt ) {
            if ( !is_array($opt) && in_array($key, $allowedTypes) ) {
                $htmlAttr .= " {$key}=\"{$opt}\"";
            }
        }

        if ( !empty($field['required']) && $field['required'] ) {
            $htmlAttr .= ' required';
        }

        return $htmlAttr;
    }
}