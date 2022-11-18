<?php

namespace WPMC\Action;

use WPMC\Action;
use WPMC\Entity;

class ActionLog
{
    private $actionLogId;
    private $date;
    private $entity;
    private $actionName;
    private $actionIds;
    private $params;
    private $execTime;
    private $context;
    private $userId;
    private $result;

    public static function loadFromDb($id): ActionLog
    {
        $entity = wpmc_get_entity('action_logs');
        $row = $entity->findById($id);

        $entry = new self();
        $entry->setActionLogId( $row['id'] );
        $entry->setDate( $row['date'] );
        $entry->setEntity( $row['entity'] );
        $entry->setActionName( $row['action_name'] );
        $entry->setActionIds( $row['action_ids'] );
        $entry->setParams( $row['params'] );
        $entry->setResult( $row['result'] );
        $entry->setExecTime( $row['exec_time'] );
        $entry->setContext( $row['context'] );
        $entry->setUserId( $row['user_id'] );

        return $entry;
    }

    public function saveToDb()
    {
        $data = [];
        $data['date'] = $this->getDate() ?: gmdate('Y-m-d H:i:s');
        $data['entity'] = $this->getEntity();
        $data['action_name'] = $this->getActionName();
        $data['action_ids'] = json_encode($this->getActionIds());
        $data['params'] = $this->getParams() ? json_encode($this->getParams()) : null;
        $data['result'] = $this->getResult() ? json_encode($this->getResult()) : null;
        $data['exec_time'] = $this->getExecTime();
        $data['context'] = $this->getContext();
        $data['user_id'] = $this->getUserId() ?: get_current_user_id();

        $entity = wpmc_get_entity('action_logs');

        $save = $entity->saveDbData($data, $this->getActionLogId());
        $this->setActionLogId( $save );

        return $this->getActionLogId();
    }

    public function rerunAction()
    {
        $entity = $this->getEntityObject();
        $params = $this->getParams();
        $contextIds = $this->getActionIds();

        $newAction = $entity->actionsCollection()->getActionByAlias( $this->getActionName() );
        $newAction->setRootEntity( $entity );
        $newAction->setAlias( $this->getActionName() );

        $runner = $newAction->getRunner();
        $runner->setContext(Action::CONTEXT_ACTION_RERUN);

        if ( $newAction instanceof BackgroundAction ) {
            return $newAction->getRunner()->executeNow( $contextIds, $params );
        }
        else {
            return $runner->executeAction( $contextIds, $params );
        }
    }

    /**
     * Get the value of actionLogId
     */ 
    public function getActionLogId()
    {
        return $this->actionLogId;
    }

    /**
     * Set the value of actionLogId
     *
     * @return  self
     */ 
    public function setActionLogId($actionLogId)
    {
        $this->actionLogId = $actionLogId;

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

    public function getEntityObject()
    {
        return wpmc_get_entity( $this->getEntity() );
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
     * Get the value of actionName
     */ 
    public function getActionName()
    {
        return $this->actionName;
    }

    /**
     * Set the value of actionName
     *
     * @return  self
     */ 
    public function setActionName($actionName)
    {
        $this->actionName = $actionName;

        return $this;
    }

    /**
     * Get the value of actionIds
     */ 
    public function getActionIds()
    {
        return $this->actionIds ?: [];
    }

    /**
     * Set the value of actionIds
     *
     * @return  self
     */ 
    public function setActionIds($actionIds)
    {
        if( !is_array($actionIds) && !empty($actionIds) ) {
            $actionIds = json_decode($actionIds);
        }
        
        $this->actionIds = $actionIds;

        return $this;
    }

    /**
     * Get the value of execTime
     */ 
    public function getExecTime()
    {
        return $this->execTime;
    }

    /**
     * Set the value of execTime
     *
     * @return  self
     */ 
    public function setExecTime($execTime)
    {
        $this->execTime = $execTime;

        return $this;
    }

    /**
     * Get the value of context
     */ 
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Set the value of context
     *
     * @return  self
     */ 
    public function setContext($context)
    {
        $this->context = $context;

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
     * Get the value of params
     */ 
    public function getParams()
    {
        return convertStdToArray( $this->params );
    }

    /**
     * Set the value of params
     *
     * @return  self
     */ 
    public function setParams($params)
    {
        if( !is_array($params) && !empty($params) ) {
            $params = json_decode($params);
        }

        $this->params = $params;

        return $this;
    }

    /**
     * Get the value of result
     */ 
    public function getResult()
    {
        return $this->result;
    }

    /**
     * Set the value of result
     *
     * @return  self
     */ 
    public function setResult($result)
    {
        if( !is_array($result) && !empty($result) ) {
            $result = json_decode($result);
        }

        $this->result = $result;

        return $this;
    }
}