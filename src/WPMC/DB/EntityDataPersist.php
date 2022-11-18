<?php

namespace WPMC\DB;

use Exception;
use WPMC\Action;
use WPMC\Entity;

class EntityDataPersist
{
    public function __construct(private Entity $entity)
    {
    }

    /**
     * @return int
     */
    public function saveEntityData($item, $pkValue = null)
    {
        global $wpdb;

        try {
            $entity = $this->entity;
            $item = apply_filters('wpmc_process_save_data', $item, $entity);
            $itemToSave = $this->removeNonPrimitiveFields($item);
            $itemToSave = $this->callBeforeSave($itemToSave);

            // start transaction
            $wpdb->query('START TRANSACTION');
    
            $pkValue = $this->updateOrInsert($itemToSave, $pkValue);
            $this->runAfterSavedCallbacks($item, $pkValue);
            
            // commit transaction
            $wpdb->query('COMMIT');
        }
        catch (Exception $e) {
            // rollback transaction
            $wpdb->query('ROLLBACK');

            throw $e;
        }

        return $pkValue;
    }

    /**
     * @return int
     */
    private function updateOrInsert($item, $pkValue = null) {
        global $wpdb;

        $entity = $this->entity;
        $db = $entity->getDatabase();
        $tableName = $db->getTableName();
        $pkey = $db->getPrimaryKey();
        $trackChanges = $db->getTrackChanges();

        if ( empty($pkValue) && !empty($item[$pkey])) {
            $pkValue = $item[$pkey];
        }

        if (empty($pkValue)) {
            $result = $wpdb->insert($tableName, $item);
            psCheckDbError($result);

            $pkValue = $wpdb->insert_id;

            if ( !empty($pkValue) && $trackChanges ) {
                (new DataTrackSave($entity, $item))->saveForInsert($pkValue);
            }

            $this->runActionsOnCreate($pkValue);
            return $pkValue;
        }
        else {
            if ( $trackChanges ) {
                (new DataTrackSave($entity, $item))->saveForUpdate($pkValue);
            }
            
            $result = $wpdb->update($tableName, $item, array($pkey => $pkValue));
            psCheckDbError($result);

            $this->runActionsOnUpdate($pkValue);

            return $pkValue;
        }
    }

    private function runAfterSavedCallbacks($item, $pkValue)
    {
        $entity = $this->entity;
        $pkey = $entity->getDatabase()->getPrimaryKey();
  
        $item[$pkey] = $pkValue;

        if ( !empty($pkValue) ) {
            $fields = $entity->getFieldsObjects();

            foreach ( $fields as $field ) {
                $field->afterEntityDataSaved($item);
            }
        }

        do_action('wpmc_data_saved', $entity, $item);
    }

    private function runActionsOnCreate($id)
    {
        $entity = $this->entity;
        $actions = $entity->actionsCollection()->getAfterCreatedActions();

        foreach ( $actions as $action ) {
            $action->getRunner()->executeAction([$id]);
        }
    }

    private function runActionsOnUpdate($id)
    {
        $entity = $this->entity;
        $actions = $entity->actionsCollection()->getAfterUpdatedActions();

        foreach ( $actions as $action ) {
            $action->getRunner()->executeAction([$id]);
        }
    }

    /**
     * @return array
     */
    private function removeNonPrimitiveFields($item)
    {
        $entity = $this->entity;

        foreach ( $entity->getFieldsObjects() as $field ) {
            $name = $field->getName();

            if ( !$field->isPrimitiveType() && isset($item[$name]) ) {
                unset($item[$name]);
            }
        }

        return $item;
    }

    private function callBeforeSave($item)
    {
        $entity = $this->entity;

        foreach ( $entity->getFieldsObjects() as $field ) {
            $name = $field->getName();

            if ( isset($item[$name]) ) {
                $item[$name] = $field->alterBeforeSave($item[$name]);
            }
        }

        return $item;
    }
}