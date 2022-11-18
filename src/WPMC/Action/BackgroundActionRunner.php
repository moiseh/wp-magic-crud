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

    public function enqueueAsynchronous($ids = [], $params = [])
    {
        if ( empty($ids) ) {
            return;
        }

        /**
         * @var BackgroundAction
         */
        $action = $this->action;

        as_schedule_single_action( time(), $action->getJobHook(), [ $ids, $params ]);
    }
}