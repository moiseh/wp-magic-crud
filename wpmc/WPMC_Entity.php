<?php
class WPMC_Entity {
    private $fields = [];
    private $tableName;
    private $displayField;
    private $defaultOrder;
    private $identifier;
    private $singular;
    private $plural;
    private $menuIcon;

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

        if ( !empty($options['menu_icon']) ) {
            $this->menuIcon = $options['menu_icon'];
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

        $capability = 'activate_plugins';
        $addLabel = __('Adicionar novo', 'wp-magic-crud');
        $listingPage = array($this, 'listing_page_handler');
        $formPage = array($this, 'form_page_handler');
        $plural = $this->get_plural();

        add_menu_page($plural, $plural, $capability, $identifier, $listingPage, $this->menuIcon);
        add_submenu_page($identifier, $plural, $plural, $capability, $identifier, $listingPage);
       
        if ( $this->can_create() ) {
            add_submenu_page($identifier, $addLabel, $addLabel, $capability, $this->form_page_identifier(), $formPage);
        }
    }

    function listing_page_handler() {
        do_action("wpmc_before_entity", $this);
        do_action("wpmc_before_list", $this);

        $table = new WPMC_List_Table($this);
        $table->execute_page_handler();
    }

    function form_page_handler() {
        do_action("wpmc_before_entity", $this);
        do_action("wpmc_before_form", $this);

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

    function get_action_url($action, $id) {
        return get_admin_url(get_current_blog_id(), 'admin.php?page='.$this->identifier().'&action='.$action.'&id='.$id);
    }

    function get_action_link($action, $id, $label) {
        return sprintf('<a href="%s">%s</a>', $this->get_action_url($action, $id), $label);
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

    function get_singular() {
        return $this->singular;
    }

    function get_plural() {
        return $this->plural;
    }

    function get_table() {
        return $this->tableName;
    }

    function get_display_field() {
        return $this->displayField;
    }

    function get_default_order() {
        return $this->defaultOrder;
    }

    function get_fields() {
        return $this->fields;
    }

    function get_field($name) {
        return $this->fields[$name];
    }

    function has_field($name) {
        return !empty($this->fields[$name]);
    }

    function get_creatable_fields() {
        $fields = [];
        foreach ( $this->get_fields() as $name => $field ) {
            if ( empty($field['restrict_to']) || in_array('add', $field['restrict_to']) ) $fields[$name] = $field;
        }
        return $fields;
    }

    function get_updatable_fields() {
        $fields = [];
        foreach ( $this->get_fields() as $name => $field ) {
            if ( empty($field['restrict_to']) || in_array('edit', $field['restrict_to']) ) $fields[$name] = $field;
        }
        return $fields;
    }

    function get_listable_fields() {
        $fields = [];
        foreach ( $this->get_fields() as $name => $field ) {
            if ( empty($field['restrict_to']) || in_array('list', $field['restrict_to']) ) $fields[$name] = $field;
        }
        return $fields;
    }

    function get_sortable_fields() {
        $listableFields = array_keys($this->get_listable_fields());
        $fields = [];
        foreach ( $this->get_fields() as $name => $field ) {
            if ( empty($field['restrict_to']) || in_array('sort', $field['restrict_to']) && in_array($name, $listableFields) ) $fields[$name] = $field;
        }
        return $fields;
    }

    function get_view_fields() {
        $fields = [];
        foreach ( $this->get_fields() as $name => $field ) {
            if ( empty($field['restrict_to']) || in_array('view', $field['restrict_to']) ) $fields[$name] = $field;
        }
        return $fields;
    }

    function can_create() {
        $creatableFields = array_keys($this->get_creatable_fields());

        foreach ( $this->get_fields() as $name => $field ) {
            if ( in_array($name, $creatableFields) ) {
                return true;
            }
        }

        return false;
    }

    function back_to_home() {
        wpmc_redirect($this->listing_url());
    }

    function build_options($ids = []) {
        $db = new WPMC_Database();
        return $db->buildEntityOptionsList($this, $ids);
    }

    function delete($ids) {
        global $wpdb;

        $ids = (array) apply_filters('wpmc_before_delete_ids', $ids, $this);
        $table = $this->get_table();

        foreach ( $ids as $id ) {
            $wpdb->delete($table, array('id' => $id));
        }
    }

    function find_by_id($id) {
        $db = new WPMC_Database();
        return $db->findByEntityId($this, $id);
    }

    function save_db_data($item) {
        $db = new WPMC_Database();
        return $db->saveEntityData($this, $item);
    }
}