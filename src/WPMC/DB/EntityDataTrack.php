<?php

namespace WPMC\DB;

use WPMC\Entity;

class EntityDataTrack
{
    private $trackId;
    private $date;
    private $entity;
    private $operation;
    private $pkey;
    private $fieldName;
    private $oldValue;
    private $newValue;
    private $userId;

    public static function loadFromDb($id): EntityDataTrack
    {
        $entity = wpmc_get_entity('data_track');
        $row = $entity->findById($id);

        $entry = new self();
        $entry->setTrackId($row['id']);
        $entry->setEntity($row['entity']);
        $entry->setOperation($row['operation']);
        $entry->setPkey($row['pkey']);
        $entry->setFieldName($row['field_name']);
        $entry->setOldValue($row['old_value']);
        $entry->setNewValue($row['new_value']);
        $entry->setUserId($row['user_id']);

        return $entry;
    }

    public function saveToDb()
    {
        $data = [];
        $data['date'] = $this->getDate() ?: gmdate('Y-m-d H:i:s');
        $data['entity'] = $this->getEntity();
        $data['operation'] = $this->getOperation();
        $data['pkey'] = $this->getPkey();
        $data['field_name'] = $this->getFieldName();
        $data['old_value'] = $this->getOldValue();
        $data['new_value'] = $this->getNewValue();
        $data['user_id'] = $this->getUserId() ?: get_current_user_id();

        $entity = wpmc_get_entity('data_track');

        $save = $entity->saveDbData($data, $this->getTrackId());
        $this->setTrackId( $save );

        return $this->getTrackId();
    }

    public function restore()
    {
        return 'restore_not_yet_supported';
    }

    /**
     * Get the value of trackId
     */ 
    public function getTrackId()
    {
        return $this->trackId;
    }

    /**
     * Set the value of trackId
     *
     * @return  self
     */ 
    public function setTrackId($trackId)
    {
        $this->trackId = $trackId;

        return $this;
    }

    /**
     * Get the value of date
     */ 
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set the value of date
     *
     * @return  self
     */ 
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get the value of entity
     */ 
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * Set the value of entity
     *
     * @return  self
     */ 
    public function setEntity($entity)
    {
        if ( $entity instanceof Entity ) {
            $entity = $entity->getIdentifier();
        }

        $this->entity = $entity;

        return $this;
    }

    /**
     * Get the value of operation
     */ 
    public function getOperation()
    {
        return $this->operation;
    }

    /**
     * Set the value of operation
     *
     * @return  self
     */ 
    public function setOperation($operation)
    {
        $this->operation = $operation;

        return $this;
    }

    /**
     * Get the value of pkey
     */ 
    public function getPkey()
    {
        return $this->pkey;
    }

    /**
     * Set the value of pkey
     *
     * @return  self
     */ 
    public function setPkey($pkey)
    {
        $this->pkey = $pkey;

        return $this;
    }

    /**
     * Get the value of oldValue
     */ 
    public function getOldValue()
    {
        return $this->oldValue;
    }

    /**
     * Set the value of oldValue
     *
     * @return  self
     */ 
    public function setOldValue($oldValue)
    {
        $this->oldValue = $oldValue;

        return $this;
    }

    /**
     * Get the value of newValue
     */ 
    public function getNewValue()
    {
        return $this->newValue;
    }

    /**
     * Set the value of newValue
     *
     * @return  self
     */ 
    public function setNewValue($newValue)
    {
        $this->newValue = $newValue;

        return $this;
    }

    /**
     * Get the value of userId
     */ 
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set the value of userId
     *
     * @return  self
     */ 
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get the value of fieldName
     */ 
    public function getFieldName()
    {
        return $this->fieldName;
    }

    /**
     * Set the value of fieldName
     *
     * @return  self
     */ 
    public function setFieldName($fieldName)
    {
        $this->fieldName = $fieldName;

        return $this;
    }
}