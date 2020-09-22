<?php
require_once __DIR__ . '/functions.php';

if ( !class_exists('WPMC_List_Table')) {
    require_once __DIR__ . '/WPMC_List_Table.php';
}
if ( !class_exists('WPMC_Entity')) {
    require_once __DIR__ . '/WPMC_Entity.php';
}
if ( !class_exists('WPMC_Form')) {
    require_once __DIR__ . '/WPMC_Form.php';
}
if ( !class_exists('WPMC_Field_OneToMany')) {
    require_once __DIR__ . '/WPMC_Field_OneToMany.php';
    $fieldEntity = new WPMC_Field_OneToMany();
    $fieldEntity->initHooks();
}
if ( !class_exists('WPMC_Field')) {
    require_once __DIR__ . '/WPMC_Field.php';
}
if ( !class_exists('WPMC_Database')) {
    require_once __DIR__ . '/WPMC_Database.php';
}
if ( !class_exists('WPMC_Query_Builder')) {
    require_once __DIR__ . '/WPMC_Query_Builder.php';
}

add_action('init', function(){
    $entities = wpmc_load_app_entities();

    // create entities database structure on-the-fly
    if ( apply_filters('wpmc_create_tables', true) ) {
        $db = new WPMC_Database();
        $db->migrateEntityTables($entities);
    }

    do_action('wpmc_loaded');
});


// admin styles
add_action('admin_enqueue_scripts', function(){
    wp_enqueue_style('wpmc-styles', plugins_url('/wpmc/styles.css', dirname(__FILE__) ));
});

