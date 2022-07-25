<?php

namespace WPMC\Action;

class BackgroundActionRunner extends ActionRunner
{
    public function executeNow($ids = [], $params = []) {
        return parent::executeAction($ids, $params);
    }

    public function executeAction($ids = [], $params = []) {
        if ( empty($ids) ) {
            return true;
        }

        $this->enqueueAsynchronous($ids, $params);
        return $this;
    }

    public function executeForRest($id, $params = [])
    {
        $result = parent::executeForRest($id);
        return null;
    }

    public function getRestMessage()
    {
        return __('Action sent to background');
    }

    private function enqueueAsynchronous($ids, $params = [])
    {
        $action = $this->action;
        $rootEntity = $action->getRootEntity();
        $entityAlias = $rootEntity->getIdentifier();
        $actionAlias = $action->getAlias();

        as_schedule_single_action( time(), 'wpmc_background_action', [ $entityAlias, $actionAlias, $ids, $params ]);
    }
}