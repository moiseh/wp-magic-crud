<?php

namespace WPMC\Action;

use WPMC\Action;

class ActionRunner
{
    private $startTime;
    private $endTime;

    private $context;
    private $contextIds = [];

    public function __construct(protected Action $action)
    {
    }

    public function getRootAction()
    {
        return $this->action;
    }

    public function getContext()
    {
        return $this->context;
    }

    public function setContext($context)
    {
        $this->context = $context;
        return $this->action;
    }

    public function countIds() {
        return count($this->getContextIds());
    }

    public function getContextIds() {
        return $this->contextIds;
    }

    public function setContextIds($ids)
    {
        $this->contextIds = $ids;
        return $this;
    }

    public function executeAction($ids = [], $params = []) {
        return $this->runCallback( $ids );
    }

    public function runCallback($ids) {
        $action = $this->action;
        $this->setContextIds($ids);

        $this->startTime = microtime(true);
        $result = call_user_func($action->getCallback(), $this);
        $this->endTime = microtime(true);

        $this->logAction($result);

        return $result;
    }

    public function executeForRest($id, $params = [])
    {
        // $action = $this->getRootAction();
        // $entity = $action->getRootEntity();

        return $this->executeAction([$id], $params);
    }

    public function getRestMessage()
    {
        return __('Action executed');
    }

    private function logAction($result)
    {
        $action = $this->action;
        $alias = $action->getAlias();

        if ( $alias == 'rerun_action' ) {
            return;
        }
        
        $entity = $action->getRootEntity();
        $execTime = round($this->endTime - $this->startTime, 4);

        $log = new ActionLog();
        $log->setResult($result);
        $log->setEntity($entity);
        $log->setActionName($action->getAlias());
        $log->setExecTime($execTime);
        $log->setContext( $this->getContext() );
        $log->setActionIds( $this->getContextIds() );

        if ( $this instanceof FieldableActionRunner ) {
            $log->setParams( $this->getInputParams() );
        }

        $log->saveToDb();

        return $this;
    }
}