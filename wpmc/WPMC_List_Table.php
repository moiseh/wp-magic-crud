<?php

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
require_once(ABSPATH . 'wp-admin/includes/template.php');

class WPMC_List_Table extends WP_List_Table {
    /**
     * @var WPMC_Entity
     */
    private $entity;

    function __construct(WPMC_Entity $entity) {
        $this->entity = $entity;

        parent::__construct(array(
            'singular' => $entity->singular,
            'plural'   => $entity->plural,
        ));
    }

    function get_columns() {
        $cols = [];

        foreach ( $this->entity->get_listable_fields() as $name => $field ) {
            $cols[$name] = $field['label'];
        }

        return $cols;
    }

    function get_sortable_columns() {
        $cols = [];

        foreach ( $this->entity->get_sortable_fields() as $name => $field ) {
            $cols[$name] = [ $name, true ];
        }

        return $cols;
    }

    function column_default($item, $col)
    {
        if ( $col == $this->entity->displayField ) {
            $actions = $this->get_actions($item);
            return sprintf('%s %s', $item['name'], $this->row_actions($actions));
        }

        return $item[$col];
    }

    function get_actions($item) {
        $identifier = $this->entity->form_page_identifier();

        $actions = array(
            'edit' => sprintf('<a href="?page=%s&id=%s">%s</a>', $identifier, $item['id'], __('Editar', 'wpbc')),
            'delete' => sprintf('<a href="?page=%s&action=delete&id=%s" onclick="return confirm(\'Confirma exclusão?\')">%s</a>', $_REQUEST['page'], $item['id'], __('Excluir', 'wpbc')),
        );

        return $actions;
    }

    function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="id[]" value="%s" />',
            $item['id']
        );
    }

    function get_bulk_actions()
    {
        $actions = array(
            'delete' => 'Excluir'
        );
        return $actions;
    }

    function process_bulk_action()
    {
        global $wpdb;

        $ids = isset($_REQUEST['id']) ? $_REQUEST['id'] : [];
        $ids = implode(',', (array)$ids);

        if (!empty($ids)) {
            $this->entity->check_can_manage($ids);
        }

        switch($this->current_action()) {
            case 'delete':
                $this->entity->delete($ids);
            break;
        }
    }

    function get_per_page() {
        return 10;
    }

    function prepare_items()
    {
        global $wpdb;

        $columns = [];
        $columns['cb'] = '<input type="checkbox" />';
        $columns += $this->get_columns();

        $sortable = $this->get_sortable_columns();
        $hidden = array();
        
        $this->_column_headers = array($columns, $hidden, $sortable);

        $this->process_bulk_action();

        $query = $this->build_listing_query();
        $items = $query->get();
        
        $this->items = apply_filters('wpsc_entity_list', $items, $this->entity);

        $total_items = $query->getCountRows();
        $per_page = $this->get_per_page();

        $this->set_pagination_args(array(
            'total_items' => $total_items, 
            'per_page' => $per_page,
            'total_pages' => ceil($total_items / $per_page) 
        ));
    }

    /**
     * @return WPMC_Query_Builder
     */
    function build_listing_query() {
        $db = new WPMC_Database();
        $query = $db->buildListingQuery($this->entity);

        return apply_filters('wpsc_listing_query', $query, $this->entity);
    }

    function prepare_sql_listing($sql = array()) {
        return $sql;
    }
}
