<?php
class WPMC_Field {
    public function initHooks() {
        add_filter('wpmc_validation_errors', array($this, 'validateFormData'), 10, 2);
        add_action('wpmc_field_render', array($this, 'renderCommonFieldTypes'), 10, 2);
        add_action('wpmc_db_creating_fields', array($this, 'defineDbCommonFieldTypes'), 10, 2);
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
                case 'text':
                    $fields[$name]['db_type'] = 'VARCHAR(255)';
                break;
            }
        }

        return $fields;
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
        }
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
        $values = $field['choices'];
        $attr = $this->buildHtmlAttributes($field);

        ?>
        <select <?php echo $attr; ?>>
            <?php foreach ( $values as $key => $label ): ?>
                <?php $selected = ( $key == $field['value'] ) ? 'selected' : ''; ?> 
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