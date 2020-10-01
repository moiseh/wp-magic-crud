<?php
require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
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
            'singular' => $entity->get_singular(),
            'plural'   => $entity->get_plural(),
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

    function column_default($item, $col) {
        if ( $col == $this->entity->get_display_field() ) {
            $actions = $this->get_actions($item);
            return sprintf('%s %s', $item['name'], $this->row_actions($actions));
        }

        return !empty($item[$col]) ? $item[$col] : '';
    }

    function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="id[]" value="%s" />',
            $item['id']
        );
    }

    function get_actions($item) {
        $updateUrl = $this->entity->update_url($item['id']);

        $actions = array(
            'edit' => sprintf('<a href="%s">%s</a>', $updateUrl, __('Edit', 'wp-magic-crud')),
            'delete' => sprintf('<a href="?page=%s&action=delete&id=%s" onclick="return confirm(\'%s\')">%s</a>', $_REQUEST['page'], $item['id'], __('Confirm delete?', 'wp-magic-crud'), __('Delete', 'wp-magic-crud')),
        );

        return apply_filters('wpmc_list_actions', $actions, $item);
    }

    function get_bulk_actions()
    {
        $actions = array(
            'delete' => __('Delete', 'wp-magic-crud'),
        );

        return apply_filters('wpmc_bulk_actions', $actions);
    }

    function process_actions_and_bulk()
    {
        $ids = wpmc_request_ids();
        $action = $this->current_action();

        if ( empty($ids) ) {
            wpmc_flash_message(__('Please select one or more items', 'wp-magic-crud'), 'error');
            $this->entity->back_to_home();
        }

        switch($action) {
            case 'delete':
                $this->entity->delete($ids);
                wpmc_flash_message( sprintf(__('Items removed: %d', 'wp-magic-crud'), count($ids)) );
                $this->entity->back_to_home();
            break;
            default:
                do_action('wpmc_run_action', $action, $ids);
                do_action("wpmc_run_action_{$action}", $ids);
            break;
        }
    }

    function get_per_page() {
        return apply_filters('wpmc_list_per_page', 10);
    }

    function execute_page_handler() {

        if ( !empty($this->current_action()) ) {
            $this->process_actions_and_bulk();
            return;
        }

        $this->prepare_items();

        $plural = $this->entity->get_plural();
        $canCreate = $this->entity->can_create();
        $createUrl = $this->entity->create_url();

        ?>
        <div class="wrap">
            <div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
            <h2>
                <?php echo $plural; ?>
                <?php if ( $canCreate ): ?>
                    <a class="add-new-h2" href="<?php echo $createUrl; ?>">
                        <?php _e('Create new', 'wp-magic-crud')?>
                    </a>
                <?php endif; ?>
            </h2>

            <?php wpmc_flash_render(); ?>

            <form class="" method="POST">
                <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>"/>
                <?php $this->search_box(__('Search'), 'search'); ?>
                <?php $this->display() ?>
            </form>
        </div>
        <?php
    }

    function prepare_items()
    {
        $columns = [];
        $columns['cb'] = '<input type="checkbox" />';
        $columns += $this->get_columns();

        $sortable = $this->get_sortable_columns();
        $hidden = array();
        
        $this->_column_headers = array($columns, $hidden, $sortable);

        $query = $this->build_listing_query();
        $items = $query->get();
        
        $this->items = apply_filters('wpmc_entity_list', $items, $this->entity);

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
        $perPage = $this->get_per_page();
        $sortCols = array_keys($this->get_sortable_columns());
        $tableName = $this->entity->get_table();
        $defaultOrder = $this->entity->get_default_order();

        $paged = isset($_REQUEST['paged']) ? max(0, intval($_REQUEST['paged']) - 1) : 0;
        $orderBy = (isset($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], $sortCols)) ? $_REQUEST['orderby'] : $defaultOrder;
        $order = (isset($_REQUEST['order']) && in_array($_REQUEST['order'], array('asc', 'desc'))) ? $_REQUEST['order'] : 'asc';
        $search = ( !empty($_REQUEST['s']) && $this->entity->is_listing() ) ? sanitize_text_field($_REQUEST['s']) : '';

        $db = new WPMC_Database();
        $qb = $db->buildMainQuery($this->entity);

        if ( !empty($search) ) {
            $qb->search($this->getSearchableColumns(), $search);
        }

        $qb->orderBy("{$tableName}.{$orderBy}", $order);
        $qb->limit($perPage);
        $qb->offset($paged);

        return apply_filters('wpmc_list_query', $qb, $this->entity);
    }

    function getSearchableColumns() {
        $tableName = $this->entity->get_table();
        $db = new WPMC_Database();
        $cols = [];

        foreach ( array_keys($this->entity->get_sortable_fields()) as $col ) {
            if ( $db->tableHasColumn($tableName, $col) ) {
                $column = $db->getTableColumn($tableName, $col);
                if ( preg_match('/(varchar|text)/', $column['DATA_TYPE']) ) {
                    $cols[] = "{$tableName}.{$col}";
                }
            }
        }

        return $cols;
    }
}