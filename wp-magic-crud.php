<?php
/*
* Plugin Name: PluggableSoft - WP Magic Crud
* Description: Magic admin CRUDs for WordPress
* Version:     1.0
* Author:      Moises Heberle
* License:     GPLv2 or later
* License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

defined( 'ABSPATH' ) or die( 'Not allowed' );
defined( 'WPMC_ROOT_DIR' ) || define('WPMC_ROOT_DIR', plugin_dir_path( __FILE__ ));

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/wpmc/functions.php';

register_activation_hook( __FILE__, function(){
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

    if ( function_exists('as_unschedule_action') ) {
        as_unschedule_action('psoft_cruds_sync');
    }
});

add_action('init', function () {
    if ( psNeedScheduleCron() ) {
        if ( !as_has_scheduled_action( 'psoft_cruds_sync_1' ) ) {
            as_schedule_recurring_action( time(), ( MINUTE_IN_SECONDS * 20 ), 'psoft_cruds_sync_1' );
        }
    }
});

// ActionScheduler
add_action('psoft_cruds_sync_1', function(){
    (new \WPMC\EntitySync())->syncEntities();
});

// ActionScheduler
add_action('wpmc_background_action', function($entity, $action, $ids, $params = []){
    $entity = wpmc_get_entity($entity);

    $action = $entity->actionsCollection()->getBackgroundJobAction($action);
    $action->getRunner()->executeNow($ids, $params);
}, 10, 5);

add_action('admin_notices', function(){
    \WPMC\UI\FlashMessage::show_flash_messages();
});

// load admin menus for all entities
add_action('admin_menu', function(){
    $entities = wpmc_load_app_entities();

    // trigger entity menus
    foreach ( $entities as $entity ) {
        $menu = new \WPMC\UI\AdminMenu($entity);
        $menu->loadAdminMenu();
    }

    do_action('wpmc_loaded');
}, 500);

// admin styles
add_action('admin_enqueue_scripts', function(){
    wp_enqueue_style('wpmc-styles', plugins_url('/wpmc/styles.css', __FILE__));
    wp_enqueue_script('wpmc-scripts', plugins_url('/wpmc/scripts.js', __FILE__));
});

// add this plugin to automatic schema updater
add_filter('psoft_schema_update', function($plugins){
    $plugins[] = __FILE__;
    return $plugins;
});

add_action('rest_api_init', function (){
    foreach ( wpmc_load_app_entities() as $entity )  {
        
        if ( !$entity->getRest()->getExposeAsRest() ) {
            continue;
        }

        $rest = new \WPMC\Rest\EntityRest($entity);
        $rest->registerPaginationRest();
        $rest->registerGetRest();
        $rest->registerPostRest();
        $rest->registerPutRest();
        $rest->registerActionsRest();
    }
});

add_filter('wpmc_load_entities', function($entities){
    $entities['action_logs'] = WPMC_ROOT_DIR . '/cruds/action_logs.json';
    return $entities;
});

add_filter('jwt_auth_whitelist', function ( $endpoints ) {
    $endpoints = [
        '/api/premium/request_update',
        '/api/premium/send_message',
        '/api/system/test',
    ];

    return array_unique( array_merge( $endpoints, $endpoints ) );
});