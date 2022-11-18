<?php
namespace WPMC\UI;

use WPMC\Entity;
use WPMC\FieldBase;

class Form {
    private $editingRecord = null;

    /**
     * @var Entity
     */
    private $entity;

    function __construct(Entity $entity) {
        $this->entity = $entity;
    }

    private function get_context_fields() {
        $entity = $this->entity;

        return $this->isCreating() ?
            $entity->fieldsCollection()->getCreatableFields() :
            $entity->fieldsCollection()->getUpdatableFields();
    }

    private function currentPage() {
        return !empty($_REQUEST['page']) ? sanitize_text_field($_REQUEST['page']) : '';
    }

    private function isCreating() {
        $entity = $this->entity;
        return ( $this->currentPage() == CommonAdmin::formPageIdentifier($entity) ) && empty($_REQUEST['id']);
    }

    private function isUpdating() {
        $entity = $this->entity;
        return ( $this->currentPage() == CommonAdmin::formPageIdentifier($entity) ) && !empty($_REQUEST['id']);
    }

    function metabox_identifier() {
        $entity = $this->entity;
        return $entity->getIdentifier() . '_form_meta_box';
    }

    function execute_page_handler() {
        $entity = $this->entity;

        if ( isset($_REQUEST['nonce']) && wp_verify_nonce($_REQUEST['nonce'], WPMC_ROOT_DIR) ) {
            $this->process_form_post();
        }
        else {
            if ( $this->isUpdating() ) {
                $item = $this->get_editing_record();

                if (!$item) {
                    wpmc_flash_message( __('Record not found', 'wp-magic-crud'), 'error' );
                }
            }
            else {
                $item = $this->form_default_values();
            }
        }
        
        $pkey = $entity->getDatabase()->getPrimaryKey();
        $pkValue = !empty($item[$pkey])  ? $item[$pkey] : ( !empty($_REQUEST[$pkey]) ? $_REQUEST[$pkey] : '' );
        $singular = $entity->getMenu()->getSingular();

        $title = $this->isUpdating() ? __('Manage', 'wp-magic-crud') : __('Add', 'wp-magic-crud');
        $title .= ' ' . $singular;

        $identifier = $entity->getIdentifier();
        $formHandler = array($this, 'render_form_content');
        $metaIdentifier = $this->metabox_identifier();
        add_meta_box($metaIdentifier, $title, $formHandler, $identifier, 'normal', 'default');

        $listingUrl = wpmc_entity_admin_url($entity);

        do_action('wpmc_before_form_render', $this);

        ?>
        <div class="wrap">
            <div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
            <h2>
                <?php echo $singular ?>
                <a class="add-new-h2" href="<?php echo $listingUrl; ?>">
                    <?php _e('back to list', 'wp-magic-crud')?>
                </a>
            </h2>
            <?php wpmc_flash_render(); ?>
            <form id="form_<?php echo $identifier; ?>" class="form-meta-box" method="POST">
                <input type="hidden" name="nonce" value="<?php echo wp_create_nonce(WPMC_ROOT_DIR); ?>"/>
                <input type="hidden" name="id" value="<?php echo $pkValue; ?>"/>
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

        $postData = array_map(function($val){
            return stripslashes_deep($val);
        }, $postData);

        $default = $this->form_default_values();
        $item = shortcode_atts($default, $postData);
        $validationErrors = $this->validate_form($item);
        $entity = $this->entity;

        if (empty($validationErrors)) {
            try {
                $id = $this->entity->saveDbData($item);

                wpmc_flash_message(__('Data successfully saved', 'wp-magic-crud'));
                wpmc_redirect( wpmc_entity_admin_url($entity) );
            }
            catch (\Exception $e) {
                wpmc_flash_message($e->getMessage(), 'error');
            }
        }
        else {
            $errors = [];
            $entity = $this->entity;
            $fc = $entity->fieldsCollection();

            foreach ( $validationErrors as $key => $error ) {
                if ( $fc->hasField($key) ) {
                    $field = $fc->getFieldObj($key);
                    $errors[] = sprintf('<b>%s:</b> %s', $field->getLabel(), $error);
                }
                else {
                    $errors[] = $error;
                }
            }

            wpmc_flash_message(implode('<br />', $errors), 'error');
        }
    }

    function validate_form($item) {
        $errors = [];
        $fields = $this->get_context_fields();

        foreach ( $fields as $field ) {
            $name = $field->getName();

            if ( $field->getRequired() && empty($item[$name]) ) {
                $errors[$name] = __('Mandatory field', 'wp-magic-crud');
            }
        }

        return apply_filters('wpmc_validation_errors', $errors, $fields);
    }

    function get_editing_record() {
        $row = $this->editingRecord;
        $entity = $this->entity;

        if ( is_null($this->editingRecord) ) {
            if ( $this->isUpdating() ) {
                $id = absint( sanitize_text_field($_REQUEST['id']) );
                $row = $entity->findById($id);
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
        $fields = $this->get_context_fields();

        foreach ( $fields as $field ) {
            $values[$field->getName()] = '';
        }

        return $values;
    }

    function render_form_content() {
        do_action('wpmc_fields_render_before', $this->entity);

        foreach ( $this->get_context_fields() as $field ) {
            $this->render_field($field);
        }

        do_action('wpmc_fields_render_after', $this->entity);
        
        $this->submitButton();

        do_action('wpmc_form_render_after', $this->entity);
    }

    private function submitButton()
    {
        if ( empty($label) ) {
            $label = __('Save', 'wp-magic-crud');
        }

        $label = esc_html__($label);

        ?>
        <input type="submit" value="<?php echo $label; ?>" id="submit" class="button-primary" name="submit">
        <?php
    }

    function render_field(FieldBase $field)
    {
        $this->trySetFieldValue($field);
        $field->renderWithLabel();
    }

    function trySetFieldValue(FieldBase $field) {
        $name = $field->getName();

        if ( !$field->hasValue() ) {
            $item = $this->get_editing_record();

            if ( !empty($item[$name]) ) {
                $field->setValue( $item[$name] );
            }
            else {
                $field->setValue( $field->getDefault() );
            }
        }

        return null;
    }

    /**
     * @return  Entity
     */ 
    public function getRootEntity()
    {
        return $this->entity;
    }
}