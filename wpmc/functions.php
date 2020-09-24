<?php

if ( !function_exists('wpmc_query')) {
    /**
     * @return \WPMC_Query_Builder
     */
    function wpmc_query() {
        global $wpdb;
        $qb = new WPMC_Query_Builder($wpdb);
        return $qb;
    }
}

if ( !function_exists('wpmc_load_app_entities')) {
    /**
     * @return WPMC_Entity[]
     */
    function wpmc_load_app_entities() {
        global $wpmc_entities;

        $wpmc_entities = [];
        $entities = apply_filters('wpmc_entities', array());
        
        foreach ( $entities as $entity => $options ) {
            $options['identifier'] = $entity;

            $obj = new WPMC_Entity($options);
            $obj->init();
        
            $wpmc_entities[$entity] = $obj;
        }

        return $wpmc_entities;
    }
}

if ( !function_exists('wpmc_get_entities')) {
    /**
     * @return WPMC_Entity[]
     */
    function wpmc_get_entities() {
        global $wpmc_entities;
        return $wpmc_entities;
    }
}

if ( !function_exists('wpmc_get_entity')) {
    /**
     * @return WPMC_Entity
     */
    function wpmc_get_entity($name) {
        $entities = wpmc_get_entities();
        
        if( empty($entities[$name])) {
            throw new Exception('Invalid entity');
        }

        return $entities[$name];
    }
}

if ( !function_exists('wpmc_current_entity')) {
    function wpmc_current_entity() {
        return !empty($_REQUEST['page']) ? str_replace('_form', '', $_REQUEST['page']) : null;
    }
}

if ( !function_exists('wpmc_get_current_entity')) {
    function wpmc_get_current_entity() {
        return wpmc_get_entity( wpmc_current_entity() );
    }
}

if ( !function_exists('wpmc_request_ids')) {
    function wpmc_request_ids() {
        $ids = [];

        if ( !empty($_REQUEST['id']) ) {
            $ids = is_array($_REQUEST['id']) ? $_REQUEST['id'] : explode(',', $_REQUEST['id']);
        }

        return $ids;
    }
}

if ( !function_exists('wpmc_render_field')) {
    function wpmc_render_field($field = [], $entity = null) {
        do_action('wpmc_field_render', $field, $entity);
    }
}

if ( !function_exists('wpmc_redirect')) {
    function wpmc_redirect($url) {
        ?>
        <script>
            window.location.href = "<?php echo $url; ?>";
        </script>
        <?php
        
        exit;
    }
}

if ( ! function_exists( 'get_current_page_url' ) ) {
    function get_current_page_url() {
      global $wp;
      return add_query_arg( $_SERVER['QUERY_STRING'], '', $wp->request );
    }
}

/*
Description: Easily Show Flash Messages in WP Admin
Version: 1
Author: Daniel Grundel, Web Presence Partners
Author URI: http://webpresencepartners.com
*/
if (!class_exists('WPFlashMessages')) {
    class WPFlashMessages {
        public function __construct() {
            add_action('admin_notices', array(&$this, 'show_flash_messages'));
        }
    
        public static function queue_flash_message($message, $class = '') {
    
            $default_allowed_classes = array('error', 'updated');
            $allowed_classes = apply_filters('flash_messages_allowed_classes', $default_allowed_classes);
            $default_class = apply_filters('flash_messages_default_class', 'updated');
    
            if(!in_array($class, $allowed_classes)) $class = $default_class;
    
            $flash_messages = maybe_unserialize(get_option('wp_flash_messages', array()));
            $flash_messages[$class][] = $message;
    
            update_option('wp_flash_messages', $flash_messages);
        }
        
        public static function show_flash_messages() {
            $flash_messages = maybe_unserialize(get_option('wp_flash_messages', ''));
    
            if(is_array($flash_messages)) {
                foreach($flash_messages as $class => $messages) {
                    foreach($messages as $message) {
                        ?><div class="<?php echo $class; ?>"><p><?php echo $message; ?></p></div><?php
                    }
                }
            }
    
            //clear flash messages
            delete_option('wp_flash_messages');
        }
    }

    new WPFlashMessages();
}
if ( !function_exists('wpmc_flash_message') ) {
    function wpmc_flash_message($message, $class = null) {
        WPFlashMessages::queue_flash_message($message, $class);
    }
}
if ( !function_exists('wpmc_flash_render') ) {
    function wpmc_flash_render() {
        WPFlashMessages::show_flash_messages();
    }
}
// End of WPFlashMessages

if ( !function_exists('wpmc_field_with_label')) {
    function wpmc_field_with_label($field = [], $label = null, $entity = null) {
        ?>
        <p>
            <label for="<?php echo $field['name']; ?>"><?php echo $field['label']; ?>:</label>
            <br>
            <?php wpmc_render_field($field); ?>
        </p>
        <?php
    }
}

if ( !function_exists('wpmc_default_action_form') ) {
    function wpmc_default_action_form($title, $postCallback, $formCallback) {
        if ( isset($_REQUEST['nonce']) && wp_verify_nonce($_REQUEST['nonce'], basename(__FILE__)) ) {
            $postCallback();
        }
    
        $entity = wpmc_get_current_entity();
        $ids = wpmc_request_ids();
        $listingUrl = $entity->listing_url();
        $action = $_REQUEST['action'];
        $backLabel = __('Back to', 'wp-magic-crud') . ' ' . $entity->plural;
        // $actions = apply_filters('wpmc_list_actions', [], ['id'=>0]);
        // $labelAction = strip_tags($actions[$action]);
    
        ?>
        <div class="wrap">
            <div class="icon32 icon32-posts-post" id="icon-edit">
            <br></div>
            <h2>
                <?php echo $title; ?>
                <a class="add-new-h2" href="<?php echo $listingUrl; ?>">
                    <?php echo $backLabel; ?>
                </a>
            </h2>
            <?php wpmc_flash_render(); ?>
            <form action="<?php echo get_current_page_url(); ?>" method="POST">
                <input type="hidden" name="nonce" value="<?php echo wp_create_nonce(basename(__FILE__))?>"/>
                <input type="hidden" name="action" value="<?php echo $action; ?>"/>
                <input type="hidden" name="id" value="<?php echo implode(',', $ids); ?>"/>
                <?php $formCallback(); ?>
            </form>
        </div>
        <?php
    }
}