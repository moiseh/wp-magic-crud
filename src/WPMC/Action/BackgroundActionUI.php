<?php

namespace WPMC\Action;

use Exception;
use WPMC\UI\CommonHtml;

class BackgroundActionUI extends UIAction
{
    public function __construct(BackgroundAction $action)
    {
        parent::__construct($action);
    }

    public function renderOrExecute($ids = []) {
        /**
         * @var BackgroundAction
         */
        $action = $this->action;
        
        $entity = $action->getRootEntity();

        try {
            $result = $action->getRunner()->executeAction( $ids );
            $url = wpmc_entity_admin_url('action_logs');
            $link = CommonHtml::htmlLink($url, 'Click here');
            
            $this->logMessage('Action will be processed in background. ' . $link . ' to see action logs page');

            wpmc_redirect( wpmc_entity_admin_url($entity) );
        }
        catch (Exception $e ) {
            wpmc_flash_message($e->getMessage(), 'error');
        }

        return $this;
    }
}