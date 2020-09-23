<?php
class WPMC_Entity {
    public $fields = [];
    public $tableName;
    public $displayField;
    public $defaultOrder;
    public $identifier;
    public $singular;
    public $plural;

    function __construct($options = array()) {
        if ( !empty($options['fields']) ) {
            $this->fields = $options['fields'];
        }

        if ( !empty($options['identifier']) ) {
            $this->identifier = $options['identifier'];
        }

        if ( !empty($options['singular']) ) {
            $this->singular = $options['singular'];
        }

        if ( !empty($options['plural']) ) {
            $this->plural = $options['plural'];
        }

        if ( !empty($options['table_name']) ) {
            $this->tableName = $options['table_name'];
        }

        if ( !empty($options['default_order']) ) {
            $this->defaultOrder = $options['default_order'];
        }

        if ( !empty($options['display_field']) ) {
            $this->displayField = $options['display_field'];
        }
    }

    function init() {
        add_action('admin_menu', array($this, 'admin_menu'));
    }

    function admin_menu() {        
        $identifier = $this->identifier();

        if ( !apply_filters("wpmc_show_menu", true, $this) ) {
            return;
        }

        $capability = 'manage_saas';
        $addLabel = __('Adicionar novo', 'wp-magic-crud');
        $listingPage = array($this, 'listing_page_handler');
        $formPage = array($this, 'form_page_handler');

        add_menu_page($this->plural, $this->plural, $capability, $identifier, $listingPage);
        add_submenu_page($identifier, $this->plural, $this->plural, $capability, $identifier, $listingPage);
       
        if ( $this->can_create() ) {
            add_submenu_page($identifier, $addLabel, $addLabel, $capability, $this->form_page_identifier(), $formPage);
        }
    }

    function listing_page_handler() {
        $identifier = $this->identifier();
        do_action("wpmc_before_entity");
        do_action("wpmc_before_entity_{$identifier}", $this);

        $table = new WPMC_List_Table($this);
        $table->execute_page_handler();
    }

    function form_page_handler() {
        $identifier = $this->identifier();
        do_action("wpmc_before_entity");
        do_action("wpmc_before_entity_{$identifier}", $this);

        $form = new WPMC_Form($this);
        $form->execute_page_handler();
    }

    function identifier() {
        return $this->identifier;
    }

    function form_page_identifier() {
        return $this->identifier() . '_form';
    }

    function listing_url() {
        return get_admin_url(get_current_blog_id(), 'admin.php?page='.$this->identifier());
    }

    function create_url() {
        return get_admin_url(get_current_blog_id(), 'admin.php?page='.$this->form_page_identifier());
    }

    function update_url($id) {
        return get_admin_url(get_current_blog_id(), 'admin.php?page='.$this->form_page_identifier().'&id='.$id);
    }

    function current_page() {
        return !empty($_REQUEST['page']) ? $_REQUEST['page'] : '';
    }

    function is_creating() {
        return ( $this->current_page() == $this->form_page_identifier() ) && empty($_REQUEST['id']);
    }

    function is_updating() {
        return ( $this->current_page() == $this->form_page_identifier() ) && !empty($_REQUEST['id']);
    }

    function is_listing() {
        return ( $this->current_page() == $this->identifier() );
    }

    function get_fields() {
        return $this->fields;
    }

    function get_creatable_fields() {
        $fields = [];
        foreach ( $this->fields as $name => $field ) {
            if ( empty($field['restrict_to']) || in_array('add', $field['restrict_to']) ) $fields[$name] = $field;
        }
        return $fields;
    }

    function get_updatable_fields() {
        $fields = [];
        foreach ( $this->fields as $name => $field ) {
            if ( empty($field['restrict_to']) || in_array('edit', $field['restrict_to']) ) $fields[$name] = $field;
        }
        return $fields;
    }

    function get_listable_fields() {
        $fields = [];
        foreach ( $this->fields as $name => $field ) {
            if ( empty($field['restrict_to']) || in_array('list', $field['restrict_to']) ) $fields[$name] = $field;
        }
        return $fields;
    }

    function get_sortable_fields() {
        $listableFields = array_keys($this->get_listable_fields());
        $fields = [];
        foreach ( $this->fields as $name => $field ) {
            if ( empty($field['restrict_to']) || in_array('sort', $field['restrict_to']) && in_array($name, $listableFields) ) $fields[$name] = $field;
        }
        return $fields;
    }

    function get_view_fields() {
        $fields = [];
        foreach ( $this->fields as $name => $field ) {
            if ( empty($field['restrict_to']) || in_array('view', $field['restrict_to']) ) $fields[$name] = $field;
        }
        return $fields;
    }

    function can_create() {
        $creatableFields = array_keys($this->get_creatable_fields());

        foreach ( $this->fields as $name => $field ) {
            if ( in_array($name, $creatableFields) ) {
                return true;
            }
        }

        return false;
    }

    function can_manage($ids) {
        if ( !is_array($ids) ) {
            $ids = [$ids];
        }

        return apply_filters('wpmc_can_manage', $this, $ids);
    }

    function check_can_manage($ids) {
        $canManage = $this->can_manage($ids);

        if ( !$canManage ) {
            throw new Exception('You cannot edit other users id');
        }
    }

    function build_options($ids = []) {
        $db = new WPMC_Database();
        return $db->buildEntityOptionsList($this, $ids);
    }

    function delete($ids) {
        global $wpdb;
        $this->check_can_manage($ids);

        foreach ( (array)$ids as $id ) {
            $wpdb->delete($this->tableName, array('id' => $id));
        }
    }

    function find_by_id($id) {
        $this->check_can_manage($id);

        $db = new WPMC_Database();
        return $db->findByEntityId($this, $id);
    }

    function save_db_data($item) {
        $db = new WPMC_Database();
        return $db->saveEntityData($this, $item);
    }
}