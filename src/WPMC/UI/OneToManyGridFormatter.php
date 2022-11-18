<?php
namespace WPMC\UI;

use WPMC\Field\OneToManyField;

class OneToManyGridFormatter
{
    public function __construct(
        private OneToManyField $field
    )
    {
        
    }

    public function formatRows($rows = [])
    {
        $field = $this->field;
        $fieldRefCol = $field->getRefColumn();
        $refEntity = $field->getRefEntity();
        $rootEntity = $field->getRootEntity();
        $name = $field->getName();
        $pkey = $refEntity->getDatabase()->getPrimaryKey();

        foreach ( $rows as $key => $parentRow ) {
            $relationId = $parentRow[$pkey];
            $queryBuilder = $field->buildRelatedRowsQuery($relationId);
            $countItems = $queryBuilder->count();
            $suffix = ( $countItems > 1 ) ? $refEntity->getMenu()->getPlural() : $refEntity->getMenu()->getSingular();

            if ( $refEntity->getMenu()->getDisplayMenu() && $countItems > 0 ) {
                // create filter to display only referenced items in target entity list
                $filters = [ $fieldRefCol => $relationId ];
                $filters['related_entity'] = $rootEntity->getIdentifier();
                $filters['related_key'] = $relationId;

                $listUrl = wpmc_entity_admin_url($refEntity, $filters);

                $rows[$key][$name] = sprintf('<a href="%s">%s %s</a>', $listUrl, $countItems, $suffix);
            }
            else {
                $rows[$key][$name] = sprintf('%s %s', $countItems, $suffix);
            }

            $rows[$key][$name] = $field->maybeApplyCustomDisplay($rows[$key][$name], $parentRow);
        }

        return $rows;
    }
}