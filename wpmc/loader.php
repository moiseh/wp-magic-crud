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
if ( !class_exists('WPMC_Field_Common')) {
    require_once __DIR__ . '/WPMC_Field_Common.php';
    $fieldCommon = new WPMC_Field_Common();
    $fieldCommon->initHooks();
}
if ( !class_exists('WPMC_Field_OneToMany')) {
    require_once __DIR__ . '/WPMC_Field_OneToMany.php';
    $fieldEntity = new WPMC_Field_OneToMany();
    $fieldEntity->initHooks();
}
if ( !class_exists('WPMC_Field_HasMany')) {
    require_once __DIR__ . '/WPMC_Field_HasMany.php';
    $fieldHasMany = new WPMC_Field_HasMany();
    $fieldHasMany->initHooks();
}
if ( !class_exists('WPMC_Field_BelongsTo')) {
    require_once __DIR__ . '/WPMC_Field_BelongsTo.php';
    $fieldBelongsTo = new WPMC_Field_BelongsTo();
    $fieldBelongsTo->initHooks();
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

