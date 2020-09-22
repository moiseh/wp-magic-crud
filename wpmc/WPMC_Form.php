<?php
class WPMC_Form {
    private $editingRecord = null;

    /**
     * @var WPMC_Entity
     */
    private $entity;

    function __construct(WPMC_Entity $entity) {
        $this->entity = $entity;
    }

    function process_form_post($postData = []) {
        if ( empty($postData) ) {
            $postData = $_REQUEST;
        }

        $default = $this->form_default_values();
        $item = shortcode_atts($default, $postData);
        $post_errors = $this->validate_form($item);
        $id = !empty($item['id']) ? $item['id'] : null;

        if ( $id > 0 ) {
            $this->entity->check_can_manage($id);
        }

        if (empty($post_errors)) {
            try {
                $this->entity->save_db_data($item);
            }
            catch (Exception $e) {
                $this->entity->add_alert($e->getMessage(), 'error');
            }

            $this->entity->add_alert(__('Dados gravados com sucesso.', 'wpbc'));
        } else {
            $this->entity->add_alert(implode('<br />', $post_errors), 'error');
        }
    }

    function validate_form($item) {
        $errors = [];
        $fields = $this->entity->is_creating() ? $this->entity->get_creatable_fields() : $this->entity->get_updatable_fields();

        foreach ( $fields as $name => $field ) {
            $required = isset($field['required']) && $field['required'];
            $label = $field['label'];
            $type = $field['type'];

            if ( !empty($item[$name]) ) {
                switch($type) {
                    case 'email':
                        if (!is_email($item['email'])) {
                            $errors[] = __(sprintf('<b>%s</b> não é um e-mail válido', $label), 'wpbc');
                        }
                    break;
                    case 'integer':
                        // !absint(intval($item['phone'])))
                    break;
                }
            }
            else if ( $required ) {
                $errors[] = __(sprintf('<b>%s</b> é obrigatório', $label), 'wpbc');
            }
        }

        return $errors;
    }

    function get_editing_record() {
        $row = $this->editingRecord;

        // TODO: Checar se ID pertence ao do usuario logado para as Entity que devem ser protegidas / restringidas

        if ( is_null($this->editingRecord) ) {
            if ( isset($_REQUEST['id']) ) {
                $id = absint($_REQUEST['id']);
                $row = $this->entity->find_by_id($id);
            }

            $row = array_merge((array)$row, $_REQUEST);
            $this->editingRecord = $row;
        }
        
        return $row;
    }

    function set_editing_record($row) {
        $this->editingRecord = $row;
    }

    function form_default_values() {
        $values = [];
        $values['id'] = 0;
        $fields = $this->entity->is_creating() ? $this->entity->get_creatable_fields() : $this->entity->get_updatable_fields();

        foreach ( $fields as $name => $field ) {
            $values[$name] = '';
        }

        return $values;
    }

    function render_form_content() {
        foreach ( $this->entity->fields as $name => $field ) {
            ?>
            <p>
                <?php $this->render_label($name, $field); ?>
                <?php $this->render_field($name); ?>
            </p>
            <?php
        }

        $this->form_button();
    }

    function render_label($name, $field) {
        ?>		
        <label for="<?php echo $this->name; ?>"><?php echo $field['label']; ?>:</label>
        <br>
        <?php
    }

    function render_field($name, $options = []) {
        if ( !empty($this->entity->fields[$name]) ) {
            $field = $this->entity->fields[$name];
            $creating = in_array($name, array_keys($this->entity->get_creatable_fields())) && $this->entity->is_creating();
            $updating = in_array($name, array_keys($this->entity->get_updatable_fields())) && $this->entity->is_updating();

            if ( !$creating && !$updating ) {
                return;
            }

            // $field['name'] = $name;
            // do_action('wpmc_field_render', $field);
            
            $obj = new WPMC_Field($field);
            $obj->name = $name;
            $obj->item = $this->get_editing_record();
            $obj->options = $options;
            $obj->render();
        }
    }

    function form_button($label = null) {
        if ( empty($label) ) {
           $label = __('Salvar', 'wpbc');
        }

        ?>
        <input type="submit" value="<?php echo $label ?>" id="submit" class="button-primary" name="submit">
        <?php
    }
}