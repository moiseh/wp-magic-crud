<?php
class WPMC_Field_BelongsTo {
    function initHooks() {
        add_filter('wpmc_query_selects', array($this, 'entityQuerySelects'), 10, 3);
        add_filter('wpmc_entity_list', array($this, 'entityList'), 10, 2);
        add_action('wpmc_field_render', array($this, 'renderField'), 10, 2);
    }

    function entityQuerySelects($selects, WPMC_Query_Builder $qb, WPMC_Entity $entity) {
        foreach ( $entity->fields as $name => $field ) {
            if ($field['type'] == 'belongs_to') {
                $refEntity = wpmc_get_entity($field['ref_entity']);
                $selects[$name] = "{$refEntity->tableName}.{$refEntity->displayField} AS {$field['ref_entity']}";
                $qb->leftJoin($refEntity->tableName, $name, '=', "{$refEntity->tableName}.id");            
            }
        }

        return $selects;
    }

    function renderField($field = []) {
        if ( $field['type'] == 'belongs_to' ) {
            $refEntity = wpmc_get_entity($field['ref_entity']);
            $field['choices'] = $refEntity->build_options();

            $fieldHelper = new WPMC_Field();
            echo $fieldHelper->select($field);
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