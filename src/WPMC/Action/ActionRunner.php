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
        global $wpdb;

        $action = $this->action;
        $alias = $action->getAlias();

        if ( $alias == 'rerun_action' ) {
            return;
        }
        
        $data = $this->getLogData();
        $data['result'] = is_array($result) ? json_encode($result) : $result;

        $saved = $wpdb->insert('wp_wpmc_action_logs', $data);
        psCheckDbError($saved);

        return $this;
    }

    protected function getLogData()
    {
        $action = $this->action;
        $entity = $action->getRootEntity();
        $alias = $action->getAlias();
        $execTime = round($this->endTime - $this->startTime, 4);

        $data = [];
        $data['date'] = gmdate('Y-m-d H:i:s');
        $data['entity'] = $entity->getIdentifier();
        $data['action_name'] = $alias;
        $data['exec_time'] = $execTime;
        $data['context'] = $this->getContext();
        $data['action_ids'] = json_encode($this->getContextIds());
        $data['user_id'] = get_current_user_id();

        return $data;
    }
}