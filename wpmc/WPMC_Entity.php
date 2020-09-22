<?php
class WPMC_Entity {
    public $fields = [];
    public $tableName;
    public $displayField;
    public $defaultOrder;
    public $identifier;
    public $singular;
    public $plural;
    public $restrictLogged;

    function __construct($options = array()) {
        if ( !empty($options['fields']) ) {
            $this->fields = $options['fields'];
        }

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

        if ( !empty($options['user_id']) ) {
            $this->restrictLogged = $options['user_id'];
        }
    }

    function init() {
        add_action('admin_menu', array($this, 'admin_menu'));
    }

    function identifier() {
        return strtolower($this->identifier);
    }

    function form_page_identifier() {
        return $this->identifier() . '_form';
    }

    function metabox_identifier() {
        return $this->identifier() . '_form_meta_box';
    }

    function listing_url() {
        return get_admin_url(get_current_blog_id(), 'admin.php?page='.$this->identifier());
    }

    function create_url() {
        return get_admin_url(get_current_blog_id(), 'admin.php?page='.$this->form_page_identifier());
    }

    function can_create() {
        foreach ( $this->fields as $field ) {
            if ( in_array('add', $field['flags']) ) {
                return true;
            }
        }

        return false;
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

    function get_creatable_fields() {
        $fields = [];
        foreach ( $this->fields as $name => $field ) {
            if ( in_array('add', $field['flags']) ) $fields[$name] = $field;
        }
        return $fields;
    }

    function get_updatable_fields() {
        $fields = [];
        foreach ( $this->fields as $name => $field ) {
            if ( in_array('edit', $field['flags']) ) $fields[$name] = $field;
        }
        return $fields;
    }

    function get_listable_fields() {
        $fields = [];
        foreach ( $this->fields as $name => $field ) {
            if ( in_array('list', $field['flags']) ) $fields[$name] = $field;
        }
        return $fields;
    }

    function get_sortable_fields() {
        $fields = [];
        foreach ( $this->fields as $name => $field ) {
            if ( in_array('sort', $field['flags']) && in_array('list', $field['flags']) ) $fields[$name] = $field;
        }
        return $fields;
    }

    function get_view_fields() {
        $fields = [];
        foreach ( $this->fields as $name => $field ) {
            if ( in_array('view', $field['flags']) ) $fields[$name] = $field;
        }
        return $fields;
    }

    function build_options() {
        global $wpdb;

        $rows = $wpdb->get_results(  "SELECT id,{$this->displayField} FROM {$this->tableName} ORDER BY {$this->defaultOrder}", ARRAY_A  );
        $opts = [];
        
        foreach ( $rows as $row ) {
            $opts[ $row['id'] ] = $row[$this->displayField];
        }

        return $opts;
    }

    function redirect($url) {
        // wp_safe_redirect($url);
        // echo "<script>window.location.href = '{$url}';</script>";
    }

    function delete($ids) {
        global $wpdb;

        foreach ( (array)$ids as $id ) {
            $wpdb->delete($this->tableName, array('id' => $id));
        }
    }

    function is_admin() {
        return current_user_can('activate_plugins');
    }

    function can_manage($ids) {
        global $wpdb;

        // when user is admin, allow manage all IDs
        // if ( $this->is_admin() ) {
        //     return true;
        // }

        // when user is NOT admin, and entity dont have restrict column
        // if ( empty($this->restrictLogged) ) {
        //     return false;
        // }

        if ( !is_array($ids) ) {
            $ids = [$ids];
        }

        $uid = get_current_user_id();
        $ids = implode(',', $ids);
        $ungranteds = $wpdb->get_var("SELECT COUNT(id) FROM {$this->tableName} WHERE id IN({$ids}) AND user_id <> {$uid}");

        return !( $ungranteds > 0 );
    }

    function check_can_manage($ids) {
        $canManage = $this->can_manage($ids);

        if ( !$canManage ) {
            throw new Exception('You cannot edit other users id');
        }
    }

    function find_by_id($id) {
        global $wpdb;
        $this->check_can_manage($id);
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->tableName} WHERE id = %d", $id), ARRAY_A);

        return apply_filters('wpmc_entity_find', $row, $this);
    }

    function add_alert($message, $type = 'message') {
        // $this->alert[$type] = $message;
        queue_flash_message($message, $type);
    }

    function render_messages() {
        WPFlashMessages::show_flash_messages();
    }

    function process_save_data($item) {
        if ( !empty($this->entity->restrictLogged) ) {
            $item[$this->entity->restrictLogged] = get_current_user_id();
        }

        return apply_filters('wpmc_process_save_data', $item, $this);
    }

    function save_db_data($item) {
        $item = $this->process_save_data($item);

        $db = new WPMC_Database();
        $id = $db->saveData($this->tableName, $item);

        $item['id'] = $id;
        do_action('wpmc_data_saved', $this, $item);

        return $id;
    }

    function admin_menu() {        
        $identifier = $this->identifier();

        if ( !apply_filters("wpmc_show_menu_{$identifier}", true) ) {
            return;
        }

        $capability = 'manage_saas';
        $addLabel = __('Adicionar novo', 'wpbc');
        $listingPage = array($this, 'listing_page_handler');
        $formPage = array($this, 'form_page_handler');

        add_menu_page($this->plural, $this->plural, $capability, $identifier, $listingPage);
        add_submenu_page($identifier, $this->plural, $this->plural, $capability, $identifier, $listingPage);
       
        if ( $this->can_create() ) {
            add_submenu_page($identifier, $addLabel, $addLabel, $capability, $this->form_page_identifier(), $formPage);
        }
    }

    function listing_page_handler() {
        global $wpdb;

        $table = new WPMC_List_Table($this);
        $table->prepare_items();

        $message = '';
        if ( 'delete' === $table->current_action() ) {
            $count = is_array($_REQUEST['id']) ? count($_REQUEST['id']) : 1;
            $message = '<div class="updated below-h2" id="message"><p>' . sprintf(__('Itens removidos: %d', 'wpbc'), $count) . '</p></div>';
        }

        ?>
        <div class="wrap">
            <div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
            <h2>
                <?php echo $this->plural ?>
                <?php if ( $this->can_create() ): ?>
                    <a class="add-new-h2" href="<?php echo $this->create_url(); ?>"><?php _e('Adicionar novo', 'wpbc')?></a>
                <?php endif; ?>
            </h2>
            <?php echo $message; ?>

            <form class="" method="POST">
                <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>"/>
                <?php $table->search_box(__('Buscar'), 'search'); ?>
                <?php $table->display() ?>
            </form>
        </div>
        <?php
    }

    function form_page_handler() {
        $form = new WPMC_Form($this);

        if ( isset($_REQUEST['nonce']) && wp_verify_nonce($_REQUEST['nonce'], basename(__FILE__)) ) {
            $form->process_form_post();
        }
        else {
            if (isset($_REQUEST['id'])) {
                $item = $form->get_editing_record();

                if (!$item) {
                    $this->add_alert( __('Registro não encontrado', 'wpbc'), 'error' );
                }
            }
            else {
                $item = $form->form_default_values();
            }
        }
        
        $identifier = $this->identifier();
        $title = ( isset($_REQUEST['id']) ? __('Gerenciar', 'wpbc') : __('Adicionar', 'wpbc') ) . ' ' . $this->singular;
        add_meta_box($this->metabox_identifier(), $title, array($form, 'render_form_content'), $this->identifier(), 'normal', 'default');

        ?>
        <div class="wrap">
            <div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
            <h2><?php echo $this->singular ?> <a class="add-new-h2" href="<?php echo $this->listing_url(); ?>"><?php _e('voltar para a lista', 'wpbc')?></a>
            </h2>

            <?php $this->render_messages(); ?>

            <form id="form_<?php echo $identifier; ?>" class="form-meta-box" method="POST">
                <input type="hidden" name="nonce" value="<?php echo wp_create_nonce(basename(__FILE__))?>"/>
                
                <input type="hidden" name="id" value="<?php echo $item['id'] ?>"/>

                <div class="metabox-holder" id="poststuff">
                    <div id="post-body">
                        <div id="post-body-content">
                            <?php do_meta_boxes($this->identifier(), 'normal', []); ?>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <?php
    }
}