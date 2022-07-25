<?php
namespace WPMC;

use Exception;
use WPMC\Action\ActionRunner;
use WPMC\Action\IResttableAction;
use WPMC\Action\RESTTableActionTrait;
use WPMC\Action\UIAction;

class Action implements IResttableAction
{
    use RESTTableActionTrait;

    const CONTEXT_UI_FORM = 'UI_FORM';
    const CONTEXT_BACKGROUND_JOB = 'BACKGROUND_JOB';
    const CONTEXT_AUTORUN_JOB = 'AUTORUN_JOB';
    const CONTEXT_API_REST = 'API_REST';
    const CONTEXT_CLI_COMMAND = 'CLI_CMD';
    const CONTEXT_RUN_ON_CREATE = 'RUN_ON_CREATE';
    const CONTEXT_RUN_ON_UPDATE = 'RUN_ON_UPDATE';
    const CONTEXT_ACTION_RERUN = 'ACTION_RERUN';

    /**
     * @var Entity
     */
    private $rootEntity;

    /**
     * @var ActionRunner
     */
    protected $runner;

    private $label;
    private $alias;
    private $callback;
    private $is_bulkable = false;

    public function setLabel($label)
    {
        $this->label = $label;
        return $this;
    }

    public function setAlias($alias)
    {
        $this->alias = $alias;
        return $this;
    }

    public function setCallback($callback)
    {
        $this->callback = $callback;
        return $this;
    }

    public function setIsBulkable($isBulkable)
    {
        $this->is_bulkable = $isBulkable;
        return $this;
    }

    public function hasUIForm() {
        return true;
    }

    public function setRootEntity(Entity $entity) {
        $this->rootEntity = $entity;
        return $this;
    }

    public function getRootEntity() {
        return $this->rootEntity;
    }

    public function getLabel() {
        return $this->label;
    }

    public function getAlias() {
        return $this->alias;
    }

    public function getCallback() {
        return $this->callback;
    }

    public function isBulkable() {
        return $this->is_bulkable;
    }

    /**
     * @return UIAction
     */
    public function getActionUI()
    {
    }

    /**
     * @return ActionRunner
     */
    public function getRunner() {
        if ( empty($this->runner) ) {
            $this->runner = new ActionRunner($this);
        }

        return $this->runner;
    }

    public function logMessage($result)
    {
        // psInfoLog("Action: {$action} => Context: {$context} => {$message}");
        
        return $this;
    }

    public function validateDefinitions()
    {
        $callback = $this->getCallback();
        $alias = $this->getAlias();

        if ( !is_callable($callback) ) {
            throw new Exception('Invalid callback defined for action: ' . $alias);
        }
    }

    public function getType() {
        return '';
    }

    public function toArray()
    {
        $arr = [];
        $arr['type'] = $this->getType();
        $arr['label'] = $this->getLabel();
        $arr['callback'] = $this->getCallback();
        $arr['is_bulkable'] = $this->isBulkable();
        $arr += $this->resttableToArray();

        return $arr;
    }
}