<?php
namespace WPMC\UI;

use WPMC\FieldBase;

class InlineFieldsTable
{
    private $currentItems = [];
    private $addTitle;

    /**
     * @param FieldBase[] $refFields
     */
    public function __construct(
        private array $refFields,
        private string $refColumn,
        private string $groupName
        )
    {
    }

    public function setCurrentItems($items)
    {
        $this->currentItems = $items;
        return $this;
    }

    public function setAddTitle($title)
    {
        $this->addTitle = $title;
        return $this;
    }

    public function renderFieldsTable()
    {
        $refItems = $this->currentItems;
        $templateHtml = $this->getEntityTemplate();
        $addTitle = $this->addTitle;

        ?>
        <div class="wpmc-onetomany-container-table">
            <table class="wpmc-onetomany-table widefat">
                <tbody>
                    <?php
                    foreach ($refItems as $index => $item) {
                        $item = (array) $item;
                        $tplHtml = $this->getEntityTemplate($item);
                        $replaced = str_replace("{index}", $index, $tplHtml);

                        echo $replaced;
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <?php if ( !empty($addTitle)): ?>
            <a class="button wpmc-line-add mg-top"><?php echo $addTitle; ?></a>
        <?php endif; ?>
        <textarea id='wpmc-first-line-tpl' style='display: none;'>
            <?php echo str_replace('</textarea>', '</tplTextArea>', $templateHtml); ?>
        </textarea>
        <script>
            jQuery(document).ready(function ($) {
                // see scripts.js OneToMany section
            });
        </script>
        <?php
    }

    private function getEntityTemplate($item = [])
    {
        $groupName = $this->groupName;
        $refFields = $this->prepareFields();

        ob_start();

        ?>
        <tr data-id="rule_{index}" class="entity-field-row">
            <?php if ( !empty($item['id'])): ?>
                <input type="hidden" name="<?php echo "{$groupName}[{index}][id]"; ?>" value="<?php echo $item['id']; ?>">
            <?php endif; ?>
            <?php
            foreach ( $refFields as $field ) {
                $name = $field->getName();
                $label = $field->getLabel();

                $field->setName("{$groupName}[{index}][{$name}]");
                $field->setValue( !empty($item[$name]) ? $item[$name] : null );

                ?>
                <td class="">
                    <label for="<?php echo $name; ?>"><?php echo esc_html__($label); ?>:</label>
                    <?php $field->render(); ?>
                </td>
                <?php
            }
            ?>
            <td class="remove">
                <a class="wpmc-line-remove"></a>
            </td>
        </tr>
        <?php

        $html = ob_get_contents();
        ob_end_clean();

        return $html;
    }

    /**
     * @return FieldBase[]
     */
    private function prepareFields()
    {
        $fields = $this->refFields;
        $refColumn = $this->refColumn;
        $prepared = [];

        foreach ( $fields as $field ) {
            $tmpField = clone($field);

            if ( $tmpField instanceof FieldBase ) {
                $name = $tmpField->getName();

                // do not render certain field types
                if ( ( $name != $refColumn ) && $tmpField->isPrimitiveType() && $tmpField->isCreatable() ) {
                    $prepared[] = $tmpField;
                }
            }
        }

        return $prepared;
    }

    private function buildEntityListingTable($field = [], $items = [])
    {
        $fieldRefEntity = $field['ref_entity'];
        $refEntity = wpmc_get_entity($fieldRefEntity);
        $listableFields = $refEntity->get_listable_fields();
        
        ob_start();
        ?>
        <table width="100%">
            <thead>
            <tr>
                <?php foreach ( $listableFields as $field ): ?>
                    <th><?php echo esc_html__($field['label']); ?></th>
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