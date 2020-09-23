<?php
class WPMC_Field_HasMany {
    function initHooks() {
        add_filter('wpmc_entity_find', array($this, 'entityFind'), 10, 2);
        add_filter('wpmc_entity_list', array($this, 'entityList'), 10, 2);
        add_action('wpmc_db_table_created', array($this, 'dbCreatingFields'), 10, 2);
        add_action('wpmc_data_saved', array($this, 'saveFormData'), 10, 2);
        add_action('wpmc_field_render', array($this, 'renderField'), 10, 2);
    }

    function dbCreatingFields($tableName, $fields = []) {
        foreach ( $fields as $col => $field ) {
            if ( !empty($field['pivot_table']) && !empty($field['pivot_left']) && !empty($field['pivot_right']) ) {
                $sql = "CREATE TABLE {$field['pivot_table']} (
                    id int(11) NOT NULL AUTO_INCREMENT,
                    `{$field['pivot_left']}` INTEGER,
                    `{$field['pivot_right']}` INTEGER,
                    PRIMARY KEY  (id)
                );";

                dbDelta($sql);
            }
        }
    }

    function saveFormData(WPMC_Entity $entity, $item = []) {
        global $wpdb;

        if ( empty($item['id'])) {
            return;
        }

        foreach ( $entity->fields as $name => $field ) {
            if ( $field['type'] == 'has_many' ) {
                $db = new WPMC_Database();
                $wpdb->delete($field['pivot_table'], array($field['pivot_left'] => $item['id']));

                foreach ( (array) $item[$name] as $referenceId ) {
                    $db->saveData( $field['pivot_table'], [
                        $field['pivot_left'] => $item['id'],
                        $field['pivot_right'] => $referenceId,
                    ] );
                }
            }
        }
    }

    function renderField($field = []) {
        if ( $field['type'] == 'has_many' ) {
            $refEntity = wpmc_get_entity($field['ref_entity']);
            $field['choices'] = $refEntity->build_options();

            $fieldHelper = new WPMC_Field();
            echo $fieldHelper->checkbox_multi($field);
        }
    }

    function entityFind($item, WPMC_Entity $entity) {
        if ( empty($item['id']) ) {
            return $item;
        }

        foreach ( $entity->fields as $name => $field ) {
            switch($field['type']) {
                case 'has_many':
                    // find the related IDs and merge with find data row
                    $item[$name] = $this->listRelationIds($field, $item['id']);
                break;
            }
        }

        return $item;
    }

    function entityList($rows, WPMC_Entity $entity) {
        foreach ( $entity->get_listable_fields() as $name => $field ) {
            if ( $field['type'] == 'has_many' ) {
                foreach ( $rows as $key => $row ) {
                    $refEntity = wpmc_get_entity($field['ref_entity']);
                    $ids = $this->listRelationIds($field, $row['id']);
                    $list = $refEntity->build_options($ids);

                    $html = '<ul>';
                    foreach ( $list as $label ) {
                        $html .= "<li>- {$label}</li>";
                    }
                    $html .= '</ul>';

                    $rows[$key][$name] = $html;
                }
            }
        }

        return $rows;
    }

    function listRelationIds($field, $leftColumnId) {
        global $wpdb;
        $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$field['pivot_table']} WHERE {$field['pivot_left']} = %d", $leftColumnId), ARRAY_A);
        $list = [];

        foreach ( $rows as $_row ) {
            $list[] = $_row[ $field['pivot_right'] ];
        }

        return $list;
    }
}