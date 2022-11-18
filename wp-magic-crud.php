<?php
/*
* Plugin Name: PluggableSoft - WP Magic Crud
* Description: Magic admin CRUDs for WordPress
* Version:     1.0
* Author:      Moises Heberle
* Author URI:  https://pluggablesoft.com/
* License:     GPLv2 or later
* License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

use WPMC\Action\AutoRunScheduler;
use WPMC\Action\BackgroundAction;
use WPMC\Entity\DatabaseCleanup;
use WPMC\Action\ActionLog;
use WPMC\DB\EntityDataTrack;
use WPMC\Entity;
use WPMC\Entity\EntityLoader;
use WPMC\UI\CommonAdmin;
use WPMC\UI\CommonHtml;
use WPMC\UI\FlashMessage;

defined( 'ABSPATH' ) or die( 'Not allowed' );
defined( 'WPMC_ROOT_DIR' ) || define('WPMC_ROOT_DIR', plugin_dir_path( __FILE__ ));

require_once __DIR__ . '/vendor/autoload.php';

register_activation_hook( __FILE__, function(){
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

    if ( function_exists('as_unschedule_all_actions') ) {
        as_unschedule_all_actions('wpmc_autorun_check');
    }
});

add_action('init', function () {
    // load ActionScheduler library
    require_once __DIR__ . '/vendor/woocommerce/action-scheduler/action-scheduler.php';
}, -1000);

add_action('init', function () {
    if ( psNeedScheduleCron() ) {
        if ( !as_has_scheduled_action( 'psoft_cruds_sync' ) ) {
            as_schedule_recurring_action( time(), ( MINUTE_IN_SECONDS * 15 ), 'psoft_cruds_sync' );
        }
    }
});

add_action('action_scheduler_before_execute', function(){
    static $hasRun = false;
    if ( $hasRun ) return;
    else $hasRun = true;

    //
    // add listeners for AutoRun actions pre-dispatch checker
    //
    $autoRunActions = (new AutoRunScheduler())->getAllAutoRunActions();

    foreach ( $autoRunActions as $action ) {
        $autoRun = $action->getAutoRun();

        add_action($autoRun->getCheckerJobHook(), function() use($action){
            $action->getAutoRun()->maybeDispatchAutoRunTasks();
        }, 10, 3);
    }

    //
    // add listeners for background actions final execution
    //
    $backgroundActions = BackgroundAction::getAllBackgroundActions();
    
    foreach ( $backgroundActions as $action )
    {
        add_action($action->getJobHook(), function($ids = [], $params = []) use($action) {
            $runner = $action->getRunner();
            $runner->executeNow($ids, $params);
        }, 10, 3);
    }

    //
    // add listeners for Database Cleanups tasks
    //
    $dbCleanups = DatabaseCleanup::getAllDbCleanups();

    foreach ( $dbCleanups as $cleanup ) {
        add_action($cleanup->getCleanupHook(), function() use($cleanup) {
            $cleanup->runEntityCleanup();
        }, 10, 2);
    }
});

// ActionScheduler
add_action('psoft_cruds_sync', function(){
    wpmc_sync_entities();
    do_action('psoft_after_cruds_sync');
});

add_action('admin_notices', function(){
    \WPMC\UI\FlashMessage::show_flash_messages();
});

// load admin menus for all entities
add_action('admin_menu', function(){
    add_menu_page( 'PSoft', 'PSoft', '', 'psoft-manager', null, 'dashicons-admin-multisite' );

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
    $entities['data_track'] = WPMC_ROOT_DIR . '/cruds/data_track.json';
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



if ( !function_exists('wpmc_load_app_entities')) {
    /**
     * @return \WPMC\Entity[]
     */
    function wpmc_load_app_entities() {
        $loader = EntityLoader::getELInstance();
        return $loader->loadEntityObjects();
    }
}

if ( !function_exists('wpmc_get_entity')) {
    /**
     * @return \WPMC\Entity
     */
    function wpmc_get_entity($name) {
        $loader = EntityLoader::getELInstance();
        return $loader->getEntityByAlias($name);
    }
}

if ( !function_exists('wpmc_sync_entities')) {
    function wpmc_sync_entities() {
        (new \WPMC\EntitySync())->syncEntities();
    }
}

if ( !function_exists('wpmc_resync_entities')) {
    function wpmc_resync_entities() {
        $loader = EntityLoader::getELInstance();
        $loader->clearEntitiesCache();

        wpmc_sync_entities();
    }
}

if ( !function_exists('wpmc_redirect')) {
    function wpmc_redirect($url) {

        if ( !empty($_GET['admin_back_to']) ) {
            $url = get_admin_url(get_current_blog_id(), 'admin.php?' . http_build_query(unserialize(base64_decode($_GET['admin_back_to']))));
        }

        ?>
        <script>
            window.location.href = "<?php echo $url; ?>";
        </script>
        <?php
        
        exit;
    }
}

if ( !function_exists('wpmc_flash_message') ) {
    function wpmc_flash_message($message, $class = null) {
        FlashMessage::queue_flash_message($message, $class);
    }
}

if ( !function_exists('wpmc_flash_render') ) {
    function wpmc_flash_render() {
        FlashMessage::show_flash_messages();
    }
}

if ( !function_exists('wpmc_entity_admin_url') ) {
    function wpmc_entity_admin_url($entity, $filters = []) {
        if ( is_string($entity) ) {
            $entity = wpmc_get_entity($entity);
        }
        
        if ( $entity instanceof Entity ) {
            $identifier = $entity->getIdentifier();
            return CommonAdmin::adminUrlWithFilters($identifier, $filters);
        }
    }
}

if ( !function_exists('wpmc_entity_identifier_link')) {
    function wpmc_entity_identifier_link($entity) {
        $obj = wpmc_get_entity($entity);

        if ( $obj->getMenu()->getDisplayMenu() ) {
            $url = wpmc_entity_admin_url($entity);
            return CommonHtml::htmlLink($url, $entity);
        }
        else {
            return $entity;
        }
    }
}

if (!function_exists('psTableExists')) {
    function psTableExists($table) {
        global $wpdb;
        return $wpdb->get_var("SHOW TABLES LIKE '{$table}'") == $table;
    }
}

if (!function_exists('psTableColumnExists')) {
    function psTableColumnExists($table, $column) {
        global $wpdb;
        
        $colName = $wpdb->get_var(  "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '{$table}' AND column_name = '{$column}'"  );
        return !empty($colName);
    }
}

if ( !function_exists('psNeedScheduleCron')) {
    function psNeedScheduleCron() {
        if ( !function_exists('as_enqueue_async_action') ) {
            return false;
        }

        return defined('DOING_CRON') || ( preg_match('/crontrol_admin_manage_page/', $_SERVER['REQUEST_URI']) && !defined('DOING_AJAX') );
    }
}

if ( !function_exists('convertStdToArray')) {
    function convertStdToArray($data) {
        return json_decode(json_encode($data), true);
    }
}

if ( !function_exists('wpmc_rerun_action')) {
    function wpmc_rerun_action(\WPMC\Action\ActionRunner $runner) {
        $actionIds = $runner->getContextIds();
        $result = [];
 
        foreach ( $actionIds as $id ) {
            $log = ActionLog::loadFromDb($id);
            $result[$id] = $log->rerunAction();
        }

        return $result;
    }
}

if ( !function_exists('wpmc_data_restore_action')) {
    function wpmc_data_restore_action(\WPMC\Action\ActionRunner $runner) {
        $trackIds = $runner->getContextIds();
        $result = [];
 
        foreach ( $trackIds as $id ) {
            $track = EntityDataTrack::loadFromDb($id);
            $result[$id] = $track->restore();
        }

        return $result;
    }
}

if ( !function_exists('psCheckDbError')) {
    function psCheckDbError($result) {
        global $wpdb;

        if ( is_wp_error( $result ) ) {
            throw new \Exception( 'DB Error:' . $result->get_error_message() );
        }
        elseif ( empty( $result ) && !empty($wpdb->last_error) ) {
            throw new \Exception( $wpdb->last_error );
        }
    }
}

if (!function_exists('psNiceNumber')) {
    function psNiceNumber($n) {
        $n = (0+str_replace(",","",$n));

        if(!is_numeric($n)) return false;
        if($n>1000000000000) return round(($n/1000000000000),1).'-T';
        else if($n>1000000000) return round(($n/1000000000),1).'B';
        else if($n>1000000) return round(($n/1000000),1).'M';
        else if($n>1000) return round(($n/1000),1).'K';

        return number_format($n);
    }
}