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
        $value = !empty($item[$name]) && is_string($item[$name]) ? esc_attr($item[$name]) : $this->value;

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
            do_action("wpmc_field_render", $this);
        }
    }

    private function text($options) {
        $attr = $this->build_attr($options);

        ?>
        <input type="text" <?php echo $attr; ?>>
        <?php
    }

    private function email($options) {
        $attr = $this->build_attr($options);

        ?>
        <input type="email" <?php echo $attr; ?>>
        <?php
    }

    private function integer($options) {
        $attr = $this->build_attr($options);

        ?>
        <input type="number" <?php echo $attr; ?>>
        <?php
    }

    private function textarea($options) {
        unset($options['value']);
        if ( empty($options['cols']) ) $options['cols'] = 85;
        if ( empty($options['rows']) ) $options['rows'] = 3;
        if ( empty($options['maxlength']) ) $options['maxlength'] = 240;
        
        $attr = $this->build_attr($options);

        ?>
        <textarea <?php echo $attr; ?>><?php echo $this->value; ?></textarea>
        <?php
    }

    private function select($options) {
        $values = $options['select_values'];
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