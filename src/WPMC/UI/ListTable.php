<?php
namespace WPMC\UI;

use Exception;
use WPMC\DB\PaginatedQuery;
use WPMC\Entity;

require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
require_once(ABSPATH . 'wp-admin/includes/template.php');

class ListTable extends \WP_List_Table {
    /**
     * @var Entity
     */
    private $entity;

    function __construct(Entity $entity) {
        $this->entity = $entity;

        parent::__construct(array(
            'singular' => $entity->getMenu()->getSingular(),
            'plural'   => $entity->getMenu()->getPlural(),
        ));
    }

    function get_columns() {
        $cols = [];
        $fields = $this->entity->fieldsCollection()->getListableFields();

        foreach ( $fields as $field ) {
            $cols[$field->getName()] = $field->getLabel();
        }

        return $cols;
    }

    function get_sortable_columns() {
        $cols = [];
        $fields = $this->entity->fieldsCollection()->getSortableFields();

        foreach ( $fields as $field ) {
            $name = $field->getName();
            $cols[$name] = [ $name, true ];
        }

        return $cols;
    }

    function column_default($item, $col) {
        if ( $col == $this->entity->getDatabase()->getDisplayField() ) {
            $actions = $this->get_actions($item);
            return sprintf('%s %s', $item[$col], $this->row_actions($actions));
        }

        return !empty($item[$col]) ? $item[$col] : '';
    }

    function column_cb($item)
    {
        $entity = $this->entity;

        if ( empty($item['id']) || !$entity->getDatabase()->hasPrimaryKey() ) {
            return null;
        }

        return sprintf(
            '<input type="checkbox" name="id[]" value="%s" />',
            $item['id']
        );
    }

    function get_actions($item) {
        return (new TableActionsProcessor($this->entity))->getActions($item);
    }

    function get_bulk_actions()
    {
        return (new TableActionsProcessor($this->entity))->getBulkActions();
    }

    function get_per_page() {
        return apply_filters('wpmc_list_per_page', 100);
    }

    function execute_page_handler() {
        $currAction = $this->current_action();

        if ( !empty($currAction) ) {
            (new TableActionsProcessor($this->entity))->processActionsAndBulk($currAction);
            return;
        }

        $this->prepare_items();

        $entity = $this->entity;
        $plural = esc_html__($entity->getMenu()->getPlural());
        $canCreate = $entity->fieldsCollection()->canCreate();
        $createUrl = $this->createFormUrl();
        $page = sanitize_text_field($_REQUEST['page']);

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
            <div class="bulk-status" style="display: inline;"></div>
            <?php $this->renderRelatedEntity(); ?>
            <?php wpmc_flash_render(); ?>
            <form class="" method="POST">
                <input type="hidden" name="page" value="<?php echo $page; ?>"/>
                <?php $this->search_box(__('Search'), 'search'); ?>
                <?php $this->display() ?>
            </form>
        </div>
        <script>
            (function($){
                jQuery(document).ready(function($){
                    var displaySelecteds = function(){
                        var checkeds = $('#the-list .check-column').find('input[type="checkbox"]:checked').length;

                        if ( checkeds > 0 ) {
                            $('.bulk-status').html('Total selecteds: ' + checkeds);
                        }
                        else {
                            $('.bulk-status').html('');
                        }
                    };

                    $('.check-column,.cb-select-all-1').on('click', function(){
                        setTimeout(displaySelecteds, 100);
                    });
                });
            })(jQuery);
        </script>
        <?php
    }

    private function renderRelatedEntity()
    {
        $relEntity = !empty($_REQUEST['related_entity']) ? wpmc_get_entity($_REQUEST['related_entity']) : '';
        $relKey = !empty($_REQUEST['related_key']) ? $_REQUEST['related_key'] : '';
        $relTitle = !empty($relEntity) && !empty($relKey) ? $relEntity->displayTitleById($relKey, true) : null;

        ?>
        <?php if ( !empty($relEntity) && !empty($relKey) && !empty($relTitle) ): ?>
            <h1>
                Related <?php echo $relEntity->getMenu()->getSingular(); ?>: <?php echo $relTitle; ?> (ID: <?php echo $relKey; ?>)
            </h1>
            <a href="<?php echo wpmc_entity_admin_url($relEntity); ?>">
                &larr; Back to <?php echo $relEntity->getMenu()->getPlural(); ?>
            </a>
        <?php endif; ?>
        <?php
    }

    function prepare_items() {
        $entity = $this->entity;
        $columns = [];
        
        if ( $entity->getDatabase()->hasPrimaryKey() ) {
            $columns['cb'] = '<input type="checkbox" />';
        }

        $columns += $this->get_columns();

        $sortable = $this->get_sortable_columns();
        $hidden = array();
        
        $this->_column_headers = array($columns, $hidden, $sortable);

        $pQuery = new PaginatedQuery($entity);
        $pQuery->setPerPage( $this->get_per_page() );

        $this->items = $pQuery->getFormattedPageItems();

        $this->set_pagination_args(array(
            'total_items' => $pQuery->getTotalItems(), 
            'per_page' => $pQuery->getPerPage(),
            'total_pages' => $pQuery->getTotalPages(),
        ));
    }

    function display() {
        $searching = !empty($_REQUEST['s']);

        if ( empty($this->items) && !$searching ) {
            $entity = $this->entity;
            $plural = esc_html__($entity->getMenu()->getPlural());
            $singular = esc_html__($entity->getMenu()->getSingular());
            $canCreate = $entity->fieldsCollection()->canCreate();
            $createUrl = $this->createFormUrl();

            ?>
            <h2 class="entity-list-noitems">
                <?php echo sprintf(__('There are no %s to display yet.'), $plural); ?>
            </h2>
            <?php if ( $canCreate ): ?>
                <a href="<?php echo $createUrl; ?>" class="entity-create-first">
                    <?php echo sprintf(__('Click here to create the first %s.'), $singular); ?>
                </a>
            <?php endif; ?>
            <?php
        }
        else {
            parent::display();
        }
    }

    private function createFormUrl() {
        $entity = $this->entity;
        $page = CommonAdmin::formPageIdentifier($entity);
        return get_admin_url(get_current_blog_id(), 'admin.php?page='.$page);
    }
}