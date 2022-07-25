<?php

namespace WPMC\Action;

use Exception;
use WPMC\Action;

class UIAction
{
    public function __construct(protected Action $action)
    {
    }

    protected function logMessage($result) {
        $action = $this->action;

        wpmc_flash_message($result);
        $action->logMessage($result);

        return $this;
    }

    protected function checkResult($result)
    {
        if ( is_array($result) ) {
            $this->logMessage('Action result: ' . json_encode($result));
        }
        else if ( is_bool($result) ) {
            if ( $result ) {
                $this->logMessage('Success to execute action');
            }
            else {
                throw new Exception('Failed to execute action');
            }
        }
        else {
            $this->logMessage($result);
        }
    }

    public function renderOrExecute($ids = []) {
    }
}