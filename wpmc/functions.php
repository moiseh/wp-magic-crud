<?php

/**
 * @return \WPMC_Query_Builder
 */
function qbuilder() {
    global $wpdb;
    $qb = new WPMC_Query_Builder($wpdb);
    return $qb;
}

function load_app_entities() {
    global $wpsc_entities;

    $wpsc_entities = [];
    $entities = apply_filters('wpmc_entities', array());
    
    foreach ( $entities as $entity => $options ) {
        $options['identifier'] = $entity;

        $obj = new WPMC_Entity($options);
        $obj->init();
    
        $wpsc_entities[$entity] = $obj;
    }
}

function migrate_entities_db() {
    global $wpsc_entities;

    $db = new WPMC_Database();
    $db->migrateEntityTables($wpsc_entities);
}


/*
Description: Easily Show Flash Messages in WP Admin
Version: 1
Author: Daniel Grundel, Web Presence Partners
Author URI: http://webpresencepartners.com
*/
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
if( class_exists('WPFlashMessages') && !function_exists('queue_flash_message') ) {
    function queue_flash_message($message, $class = null) {
        WPFlashMessages::queue_flash_message($message, $class);
    }
}
// End of WPFlashMessages