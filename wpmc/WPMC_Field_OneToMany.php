<?php
class WPMC_Field_OneToMany {
    function initHooks() {
        add_action('wpmc_data_saved', array($this, 'saveEntityData'), 10, 2);
        add_filter('wpmc_form_validate', array($this, 'formValidate'), 10, 3);
        add_action('wpmc_field_render', array($this, 'renderEntityFieldType'), 10, 2);
        add_filter('wpmc_entity_find', array($this, 'mergeEntityFind'), 10, 2);
        add_filter('wpmc_entity_list', array($this, 'mergeEntityList'), 10, 2);
    }

    private function getRelatedRows($field = [], $relationId) {
        global $wpdb;

        $fieldRefEntity = $field['ref_entity'];
        $fieldRefColumn = $field['ref_column'];
        $refEntity = wpmc_get_entity($fieldRefEntity);
        
        $db = new WPMC_Database();
        $query = $db->buildMainQuery($refEntity);
        $query->where("{$refEntity->tableName}.{$fieldRefColumn}", "=", $relationId);

        $rows = $query->get();

        return $rows;
    }

    function mergeEntityFind($item, WPMC_Entity $entity) {

        // find parent ID and merge data with the row
        if ( empty($item['id']) ) {
            return $item;
        }

        foreach ( $entity->fields as $name => $field ) {
            if ( $field['type'] == 'one_to_many' ) {
                $relationId = $item['id'];
                $item[$name] = $this->getRelatedRows($field, $relationId);
            }
        }

        return $item;
    }

    function mergeEntityList($parentRows, WPMC_Entity $entity) {
        foreach ( $entity->get_listable_fields() as $name => $field ) {
            if ( $field['type'] == 'one_to_many' ) {
                $fieldRefEntity = $field['ref_entity'];
                $fieldRefColumn = $field['ref_column'];
                $refEntity = wpmc_get_entity($fieldRefEntity);

                foreach ( $parentRows as $key => $parentRow ) {
                    $relationId = $parentRow['id'];
                    $items = $this->getRelatedRows($field, $relationId);
                    if ( count($items) > 1 ) {
                        $parentRows[$key][$name] = count($items) . ' ' . $refEntity->plural;
                    }
                    else {
                        $parentRows[$key][$name] = count($items) . ' ' . $refEntity->singular;
                    }
                    
                    // $parentRows[$key][$name] = $this->buildEntityListingTable($field, $items);
                }
            }
        }

        return $parentRows;
    }

    function formValidate($errors = [], WPMC_Entity $entity, $item) {
        // foreach ( $entity->fields as $name => $field ) {
        //     if ( $field['type'] == 'entity' ) {
        //         $fieldRefEntity = $field['ref_entity'];

        //         foreach ( $item[$name] as $lineItem ) {
        //             $refEntity = wpmc_get_entity($fieldRefEntity);
        //             $errors += $refEntity->validate_form($lineItem);
        //         }
        //     }
        // }

        return $errors;
    }

    function saveEntityData(WPMC_Entity $entity, $item) {
        foreach ( $entity->fields as $name => $field ) {
            if ( $field['type'] == 'one_to_many' ) {
                $fieldRefEntity = $field['ref_entity'];
                $fieldRefColumn = $field['ref_column'];
                $relationId = $item['id'];

                if ( !empty($_REQUEST[$fieldRefEntity]) && ( $relationId > 0 ) ) {
                    $refItems = $_REQUEST[$fieldRefEntity];
                    $refEntity = wpmc_get_entity($fieldRefEntity);
                    $savedIds = [];

                    foreach ( $refItems as $index => $refItem ) {
                        $refItem[$fieldRefColumn] = $relationId;
                        $savedIds[] = $refEntity->save_db_data($refItem);
                    }
                    
                    // var_dump($savedIds); exit;

                    // delete removed data from form
                    $relatedRows = $this->getRelatedRows($field, $relationId);
                    foreach ( $relatedRows as $row ) {
                        if ( !in_array($row['id'], $savedIds) ) {
                            $refEntity->delete($row['id']);
                        }
                    }
                }
            }
        }
    }

    function renderEntityFieldType($field = []) {
        if ( $field['type'] != 'one_to_many' ) {
            return;
        }

        $templateHtml = $this->getEntityTemplate($field);
        $fieldRefEntity = $field['ref_entity'];
        $refEntity = wpmc_get_entity($fieldRefEntity);
        $addTitle = __("Adicionar {$refEntity->singular}");
        $refItems = !empty($field['value']) ? $field['value'] : [];

        // use this way to change default html template
        ob_start();
        $html = ob_get_clean();
        if ( !empty($html) ) {
            echo $html;
            return;
        }

        ?>
        <div class="wad-rules-table-container">
            <table class="wad-rules-table widefat">
                <tbody>
                    <?php
                    foreach ($refItems as $index => $item) {
                        $tplHtml = $this->getEntityTemplate($field, $item);
                        $replaced = str_replace("{index}", $index, $tplHtml);
                        echo $replaced;
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <a class="button wad-add-group mg-top"><?php echo $addTitle; ?></a>
        <textarea id='wad-first-rule-tpl' style='display: none;'>
            <?php echo $templateHtml; ?>
        </textarea>
        <script>
            jQuery(document).ready(function ($) {
                $(document).on("click", ".wad-add-group", function (e){
                    var new_rule_index = 0;
                    var group_index = $(".entity-field-row").length;
                    var raw_tpl = $("#wad-first-rule-tpl").val();
                    var replaced = raw_tpl.replace(/{index}/g, group_index);
                    // var html = '<table class="wad-rules-table widefat"><tbody>' + replaced + '</tbody></table>';
                    
                    $(".wad-rules-table-container tbody").append(replaced);
                });

                $(document).on("click", ".wad-remove-rule", function (e){
                    //If this is the last rule in the group, we remove the entire group
                    if ($(this).parent().parent().parent().find("tr").length == 1) {
                        $(this).parent().parent().parent().parent().remove();
                    }
                    else {
                        $(this).parent().parent().remove();
                    }
                });
            });
        </script>
        <?php
    }

    function getEntityTemplate($field = [], $item = []) {
        $entityName = $field['ref_entity'];
        $refColumn = $field['ref_column'];
        $refEntity = wpmc_get_entity($entityName);
        $refFields = $refEntity->fields;

        ob_start();
        ?>
        <tr data-id="rule_{index}" class="entity-field-row">
            <?php if ( !empty($item['id'])): ?>
                <input type="hidden" name="<?php echo "{$entityName}[{index}][id]"; ?>" value="<?php echo $item['id']; ?>">
            <?php endif; ?>
            <?php
            foreach ( $refFields as $name => $field ) {
                // do not render the relationship column
                if ( $name == $refColumn || in_array($field['type'], ['has_many', 'one_to_many']) ) {
                    continue;
                }

                $field['name'] = "{$entityName}[{index}][{$name}]";
                $field['value'] = !empty($item[$name]) ? $item[$name] : null;

                ?>
                <td class="">
                    <label for="<?php echo $name; ?>"><?php echo $field['label']; ?>:</label>
                    <?php wpmc_render_field($field); ?>
                </td>
                <?php
            }
            ?>
            <td class="remove">
                <a class="wad-remove-rule acf-button-remove"></a>
            </td>
        </tr>
        <?php

        $rule_tpl = ob_get_contents();
        ob_end_clean();

        return $rule_tpl;
    }

    private function buildEntityListingTable($field = [], $items = []) {

        $fieldRefEntity = $field['ref_entity'];
        $fieldRefColumn = $field['ref_column'];
        $refEntity = wpmc_get_entity($fieldRefEntity);
        $listableFields = $refEntity->get_listable_fields();
        
        ob_start();
        ?>
        <table width="100%">
            <thead>
            <tr>
                <?php foreach ( $listableFields as $field ): ?>
                    <th><?php echo $field['label']; ?></th>
                <?php endforeach; ?>
            </tr>
            </thead>
            <tbody>
                <?php foreach ( $items as $item ): ?>
                    <?php foreach ( $listableFields as $name => $field ): ?>
                        <th><?php echo $item[$name]; ?></th>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php

        return ob_get_clean();
    }
}