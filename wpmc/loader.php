<?php
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/WPMC_List_Table.php';
require_once __DIR__ . '/WPMC_Entity.php';
require_once __DIR__ . '/WPMC_Field_Entity.php';
require_once __DIR__ . '/WPMC_Field.php';
require_once __DIR__ . '/WPMC_Database.php';
require_once __DIR__ . '/WPMC_Query_Builder.php';


add_action('init', function(){

    // load entity field type hooks
    $entityField = new WPMC_Field_Entity();
    $entityField->initHooks();

    load_app_entities();
    migrate_entities_db();
});


// admin styles
add_action('admin_enqueue_scripts', function(){
    wp_enqueue_style('wpmc-styles', plugins_url('/wpmc/styles.css', dirname(__FILE__) ));
});

