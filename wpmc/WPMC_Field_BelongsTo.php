<?php
class WPMC_Field_BelongsTo {
    function initHooks() {
        add_action('wpmc_db_creating_fields', array($this, 'defineDbFieldType'), 10, 2);
        add_filter('wpmc_query_selects', array($this, 'entityQuerySelects'), 10, 3);
        add_filter('wpmc_entity_list', array($this, 'entityList'), 10, 2);
        add_action('wpmc_field_render', array($this, 'renderField'), 10, 2);
    }

    function defineDbFieldType($fields = [], $table) {
        foreach ( $fields as $name => $field ) {
            switch($field['type']) {
                case 'belongs_to':
                    $entity = wpmc_get_entity( $field['ref_entity'] );
                    $refTable = $entity->get_table();

                    $fields[$name]['db_type'] = 'INTEGER';
                    // $fields[$name]['db_references'] = "REFERENCES {$refTable}(id)";
                break;
            }
        }

        return $fields;
    }

    function entityQuerySelects($selects, WPMC_Query_Builder $qb, WPMC_Entity $entity) {
        foreach ( $entity->get_fields() as $name => $field ) {

            if ( $field['type'] == 'belongs_to' ) {
                $refEntity = wpmc_get_entity($field['ref_entity']);
                $table = $refEntity->get_table();
                $displayField = $refEntity->get_display_field();
                $selects[$name] = "{$table}.{$displayField} AS {$field['ref_entity']}";

                $qb->leftJoin($table, $name, '=', "{$table}.id");            
            }
        }

        return $selects;
    }

    function renderField($field = []) {
        if ( $field['type'] == 'belongs_to' ) {
            $refEntity = wpmc_get_entity($field['ref_entity']);

            $field['type'] = 'select';
            $field['choices'] = $refEntity->build_options();
            wpmc_render_field($field);
        }
    }

    function entityList($rows, WPMC_Entity $entity) {
        foreach ( $entity->get_listable_fields() as $name => $field ) {
            if ( $field['type'] == 'belongs_to' ) {
                foreach ( $rows as $key => $row ) {
                    $refEntity = $field['ref_entity'];
                    if ( !empty($row[$refEntity]) ) {
                        $rows[$key][$name] = $row[$refEntity];
                    }
                }
            }
        }

        return $rows;
    }
}