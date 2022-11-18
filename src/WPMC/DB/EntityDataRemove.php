<?php

namespace WPMC\DB;

use Exception;
use WPMC\Entity;
use WPMC\Field\OneToManyField;

class EntityDataRemove
{
    public function __construct(private Entity $entity)
    {
    }

    public function delete($ids) {
        global $wpdb;

        if ( empty($ids) ) {
            return;
        }

        $entity = $this->entity;
        $ids = (array) apply_filters('wpmc_before_delete_ids', $ids, $this);
        $db = $entity->getDatabase();
        $table = $db->getTableName();
        $pkey = $db->getPrimaryKey();
        $rows = (new EntityQuery($entity))->findByIds($ids);

        try {
            $wpdb->query('START TRANSACTION');
            $trackChanges = $db->getTrackChanges();
    
            foreach ( $rows as $item ) {
                $pkValue = $item[$pkey];
    
                foreach ( $entity->getFieldsObjects() as $field ) {
                    $field->deleteRelatedData($pkValue, $item);
                }

                if ( $trackChanges ) {
                    (new DataTrackSave($entity, $item))->saveForDelete($pkValue);
                }
            }
    
            $in = implode(',', $ids);
            $result = $wpdb->query("DELETE FROM {$table} WHERE {$pkey} IN ({$in})");
            psCheckDbError($result);

            $wpdb->query('COMMIT');
        }
        catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            throw $e;
        }
    }

    /**
     * @deprecated
     */
    private function hasRelatedData(): bool
    {
        $entity = $this->entity;

        foreach ( $entity->getFieldsObjects() as $field ) {
            if ( $field instanceof OneToManyField ) {
                return true;
            }
        }

        return false;
    }
}