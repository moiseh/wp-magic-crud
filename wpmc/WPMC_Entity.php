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
    private $parentMenu;
    private $displayMenu;

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
        
        if ( !empty($options['parent_menu']) ) {
            $this->parentMenu = $options['parent_menu'];
        }

        if ( isset($options['display_menu']) ) {
            $this->displayMenu = $options['display_menu'];
        }

        if ( !empty($options['default_order']) ) {
            $this->defaultOrder = $options['default_order'];
        }

        if ( !empty($options['display_field']) ) {
            $this->displayField = $options['display_field'];
        }
    }

    // function init_hooks() {
    //     add_action('admin_menu', array($this, 'admin_menu'));
    // }

    function get_identifier() {
        return $this->identifier;
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

    function get_display_menu() {
        return ( $this->displayMenu !== false );
    }

    function get_default_order_col() {
        return current(explode(' ', $this->defaultOrder));
    }

    function get_default_order_mode() {
        $exp = explode(' ', $this->defaultOrder);
        
        if ( count($exp) > 1 ) {
            return $exp[1];
        }

        return 'ASC';
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
        return array_filter($this->get_fields(), function ($field) {
            return !isset($field['creatable']) || ( $field['creatable'] !== false );
        });
    }

    function get_updatable_fields() {
        return array_filter($this->get_fields(), function ($field) {
            return !isset($field['editable']) || ( $field['editable'] !== false );
        });
    }

    function get_listable_fields() {
        return array_filter($this->get_fields(), function ($field) {
            return !isset($field['listable']) || ( $field['listable'] !== false );
        });
    }

    function get_sortable_fields() {
        return array_filter($this->get_listable_fields(), function ($field) {
            return !isset($field['sortable']) || ( $field['sortable'] !== false );
        });
    }

    function get_view_fields() {
        return array_filter($this->get_fields(), function ($field) {
            return !isset($field['viewable']) || ( $field['viewable'] !== false );
        });
    }

    function can_create() {
        $creatableFields = $this->get_creatable_fields();
        return ( count($creatableFields) > 0 );
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

    function form_page_identifier() {
        return $this->get_identifier() . '_form';
    }

    function listing_url($filters = array()) {
        $url = 'admin.php?page='.$this->get_identifier();

        // filters used for specific columns
        // check WPMC_List_Table->build_listing_query() method to check the filter logic
        if ( !empty($filters) ) {
            $url .= '&' . http_build_query($filters);
        }

        return get_admin_url(get_current_blog_id(), $url);
    }

    function create_url() {
        return get_admin_url(get_current_blog_id(), 'admin.php?page='.$this->form_page_identifier());
    }

    function update_url($id) {
        return get_admin_url(get_current_blog_id(), 'admin.php?page='.$this->form_page_identifier().'&id='.$id);
    }

    function get_action_url($action, $id) {
        return get_admin_url(get_current_blog_id(), 'admin.php?page='.$this->get_identifier().'&action='.$action.'&id='.$id);
    }

    function get_action_link($action, $id, $label) {
        return sprintf('<a href="%s">%s</a>', $this->get_action_url($action, $id), $label);
    }

    function current_page() {
        return !empty($_REQUEST['page']) ? sanitize_text_field($_REQUEST['page']) : '';
    }

    function is_creating() {
        return ( $this->current_page() == $this->form_page_identifier() ) && empty($_REQUEST['id']);
    }

    function is_updating() {
        return ( $this->current_page() == $this->form_page_identifier() ) && !empty($_REQUEST['id']);
    }

    function is_listing() {
        return ( $this->current_page() == $this->get_identifier() );
    }

    function admin_menu() {        
        $identifier = $this->get_identifier();

        if ( !$this->get_display_menu() ) {
            return;
        }

        $capability = 'activate_plugins';
        $addLabel = __('Create new', 'wp-magic-crud');
        $listingPage = array($this, 'listing_page_handler');
        $formPage = array($this, 'form_page_handler');
        $plural = $this->get_plural();

        if ( !empty($this->parentMenu) ) {
            add_submenu_page($this->parentMenu, $plural, $plural, $capability, $identifier, $listingPage);    
        }
        else {
            add_menu_page($plural, $plural, $capability, $identifier, $listingPage, $this->menuIcon);
            add_submenu_page($identifier, $plural, $plural, $capability, $identifier, $listingPage);
        }

        if ( $this->can_create() ) {
            if ( !empty($this->parentMenu) ) {
                add_submenu_page($identifier, null, null, $capability, $this->form_page_identifier(), $formPage);
            }
            else {
                add_submenu_page($identifier, $addLabel, $addLabel, $capability, $this->form_page_identifier(), $formPage);
            }
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
}