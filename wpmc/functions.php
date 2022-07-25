<?php

use WPMC\Action\RerunAction;
use WPMC\Entity;
use WPMC\Entity\EntityLoader;
use WPMC\UI\CommonAdmin;
use WPMC\UI\CommonHtml;
use WPMC\UI\FlashMessage;

if ( !function_exists('wpmc_load_app_entities')) {
    /**
     * @return \WPMC\Entity[]
     */
    function wpmc_load_app_entities() {
        static $entityObjects = [];

        if ( empty($entityObjects) ) {
            $loader = new EntityLoader();
            $entityObjects = $loader->loadEntityObjects();
        }

        return $entityObjects;
    }
}

if ( !function_exists('wpmc_get_entity')) {
    /**
     * @return \WPMC\Entity
     */
    function wpmc_get_entity($name) {
        $entities = wpmc_load_app_entities();
        
        if( empty($entities[$name])) {
            throw new Exception('Entity not found: ' . $name);
        }

        return $entities[$name];
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
        return (new RerunAction($actionIds))->execute();
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

