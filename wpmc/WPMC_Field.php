<?php
class WPMC_Field {
    public $type = 'text';
    public $required;
    public $name;
    public $value;
    public $label;
    public $render_label = true;
    public $ref_entity;
    public $ref_column;
    public $item = [];
    public $options = [];

    public function __construct($attrs = []) {
        foreach ( $attrs as $attr => $val ) {
            $this->{$attr} = $val;
        }
    }

    public function render() {
        $label = $this->label;

        if ( $this->render_label ) {
            ?>
            <p>			
                <label for="name"><?php echo $label; ?>:</label>
                <br>
                <?php $this->renderFieldContent(); ?>
            </p>
            <?php
        }
        else {
            $this->renderFieldContent();
        }
    }

    private function renderFieldContent() {
        $type = $this->type;
        $options = $this->options;
        $name = $this->name;
        $item = $this->item;
        $value = !empty($item[$name]) ? $item[$name] : $this->value;

        if ( is_string($value) ) {
            $value = esc_attr($value);
        }

        $options['name'] = $name;
        $options['id'] = $name;
    
        if ( empty($options['value']) ) {
            $options['value'] = $value;
        }

        if ( $this->required ) {
            $options['required'] = 'required';
        }

        if ( method_exists($this, $type) ) {
            $this->{$type}($options);
        }
        else {
            do_action('wpmc_field_render', $this, $options);
        }
    }

    public function text($options) {
        $attr = $this->build_attr($options);

        ?>
        <input type="text" <?php echo $attr; ?>>
        <?php
    }

    public function email($options) {
        $attr = $this->build_attr($options);

        ?>
        <input type="email" <?php echo $attr; ?>>
        <?php
    }

    public function integer($options) {
        $attr = $this->build_attr($options);

        ?>
        <input type="number" <?php echo $attr; ?>>
        <?php
    }

    public function textarea($options) {
        unset($options['value']);
        if ( empty($options['cols']) ) $options['cols'] = 85;
        if ( empty($options['rows']) ) $options['rows'] = 3;
        if ( empty($options['maxlength']) ) $options['maxlength'] = 240;
        
        $attr = $this->build_attr($options);

        ?>
        <textarea <?php echo $attr; ?>><?php echo $this->value; ?></textarea>
        <?php
    }

    public function select($options) {
        $values = $options['choices'];
        $attr = $this->build_attr($options);

        ?>
        <select <?php echo $attr; ?>>
            <?php foreach ( $values as $key => $label ): ?>
                <?php $selected = ( $key == $options['value'] ) ? 'selected' : ''; ?> 
                <option value="<?php echo $key; ?>" <?php echo $selected; ?>><?php echo $label; ?></option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    public function checkbox_multi($options) {
        $list = $options['choices'];
        $values = !empty($options['value']) ? $options['value'] : [];
        $name = $options['name'];

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

    private function build_attr($options = []) {
        $required = !empty($options['required']) && $options['required'] ? 'required' : '';
        $attr = '';
        
        foreach ( $options as $key => $opt ) {
            if ( in_array($key, ['required']) || is_array($opt) ) {
                continue;
            }

            $attr .= " {$key}=\"{$opt}\"";
        }

        $attr .= " {$required}";

        return $attr;
    }
}