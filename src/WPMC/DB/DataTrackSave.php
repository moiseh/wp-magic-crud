<?php

namespace WPMC\DB;

use WPMC\Entity;

class DataTrackSave
{
    public function __construct(
        private Entity $entity,
        private array $item
    )
    {
        $this->item = $this->filterItemData($item);
    }

    public function saveForInsert($pkValue)
    {
        $entity = $this->entity;
        $item = $this->item;

        foreach ( $item as $field => $newValue ) {

            // if ( strlen($newValue) <= 0 ) {
            //     continue;
            // }

            $track = new EntityDataTrack();
            $track->setEntity($entity);
            $track->setOperation('INSERT');
            $track->setPkey($pkValue);
            $track->setFieldName($field);
            $track->setNewValue($newValue);
            $track->saveToDb();
        }
    }

    public function saveForUpdate($pkValue)
    {
        $entity = $this->entity;
        $oldItem = $entity->findById($pkValue);
        $item = $this->item;

        foreach ( $item as $field => $newValue ) {
            $oldValue = isset($oldItem[$field]) ? $oldItem[$field] : null;

            if ( $this->checkEquals($oldValue, $newValue) ) {
                continue;
            }

            $track = new EntityDataTrack();
            $track->setEntity($entity);
            $track->setOperation('UPDATE');
            $track->setPkey($pkValue);
            $track->setFieldName($field);
            $track->setOldValue($oldValue);
            $track->setNewValue($newValue);
            $track->saveToDb();
        }
    }

    public function saveForDelete($pkValue)
    {
        // deactivated at the moment
        return;

        $entity = $this->entity;
        $item = $this->item;

        foreach ( $item as $field => $oldValue ) {

            if ( strlen($oldValue) <= 0 ) {
                continue;
            }

            $track = new EntityDataTrack();
            $track->setEntity($entity);
            $track->setOperation('DELETE');
            $track->setPkey($pkValue);
            $track->setFieldName($field);
            $track->setOldValue($oldValue);
            $track->saveToDb();
        }
    }

    private function checkEquals($oldValue, $newValue)
    {
        if ( is_numeric($oldValue) && is_numeric($newValue) ) {
            return ( $oldValue == $newValue );
        }

        return ( $oldValue === $newValue );
    }

    private function filterItemData($item)
    {
        foreach ( $item as $key => $val ) {
            if ( is_array($val) ) {
                unset($item[$key]);
            }
        }

        return $item;
    }
}