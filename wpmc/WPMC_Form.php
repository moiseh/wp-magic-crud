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

    function metabox_identifier() {
        return $this->entity->identifier() . '_form_meta_box';
    }

    function execute_page_handler() {
        if ( isset($_REQUEST['nonce']) && wp_verify_nonce($_REQUEST['nonce'], basename(__FILE__)) ) {
            $this->process_form_post();
        }
        else {
            if (isset($_REQUEST['id'])) {
                $item = $this->get_editing_record();

                if (!$item) {
                    wpmc_flash_message( __('Registro não encontrado', 'wp-magic-crud'), 'error' );
                }
            }
            else {
                $item = $this->form_default_values();
            }
        }
    
        $singular = $this->entity->singular;
        $title  = ( isset($_REQUEST['id']) ? __('Gerenciar', 'wp-magic-crud') : __('Adicionar', 'wp-magic-crud') );
        $title .= ' ' . $singular;

        $identifier = $this->entity->identifier();
        $formHandler = array($this, 'render_form_content');
        $metaIdentifier = $this->metabox_identifier();
        add_meta_box($metaIdentifier, $title, $formHandler, $identifier, 'normal', 'default');

        $listingUrl = $this->entity->listing_url();

        ?>
        <div class="wrap">
            <div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
            <h2>
                <?php echo $singular ?>
                <a class="add-new-h2" href="<?php echo $listingUrl; ?>">
                    <?php _e('voltar para a lista', 'wp-magic-crud')?>
                </a>
            </h2>

            <?php WPFlashMessages::show_flash_messages(); ?>

            <form id="form_<?php echo $identifier; ?>" class="form-meta-box" method="POST">
                <input type="hidden" name="nonce" value="<?php echo wp_create_nonce(basename(__FILE__))?>"/>
                
                <input type="hidden" name="id" value="<?php echo $item['id'] ?>"/>

                <div class="metabox-holder" id="poststuff">
                    <div id="post-body">
                        <div id="post-body-content">
                            <?php do_meta_boxes($identifier, 'normal', []); ?>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <?php
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
                $id = $this->entity->save_db_data($item);

                wpmc_flash_message(__('Dados gravados com sucesso.', 'wp-magic-crud'));
                wpmc_redirect( $this->entity->update_url($id) );
            }
            catch (Exception $e) {
                wpmc_flash_message($e->getMessage(), 'error');
            }
        } else {
            wpmc_flash_message(implode('<br />', $post_errors), 'error');
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
                            $errors[] = __(sprintf('<b>%s</b> não é um e-mail válido', $label), 'wp-magic-crud');
                        }
                    break;
                    case 'integer':
                        // !absint(intval($item['phone'])))
                    break;
                }
            }
            else if ( $required ) {
                $errors[] = __(sprintf('<b>%s</b> é obrigatório', $label), 'wp-magic-crud');
            }
        }

        return $errors;
    }

    function get_editing_record() {
        $row = $this->editingRecord;

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

            // get field value
            if ( empty($field['value']) ) {
                $item = $this->get_editing_record();
                if ( !empty($item[$name]) ) {
                    $field['value'] = $item[$name];
                }
            }
            
            $field['name'] = $name;
            wpmc_render_field($field, $this->entity);
        }
    }

    function form_button($label = null) {
        if ( empty($label) ) {
           $label = __('Salvar', 'wp-magic-crud');
        }

        ?>
        <input type="submit" value="<?php echo $label ?>" id="submit" class="button-primary" name="submit">
        <?php
    }
}