<?php

namespace WPMC\Action;

use Exception;

class SimpleActionUI extends UIAction
{
    public function __construct(SimpleAction $action)
    {
        parent::__construct($action);
    }

    public function renderOrExecute($ids = []) {
        /**
         * @var SimpleAction
         */
        $action = $this->action;
        
        $entity = $action->getRootEntity();

        try {
            $result = $action->getRunner()->runCallback( $ids );
            $this->checkResult($result);

            wpmc_redirect( wpmc_entity_admin_url($entity) );
        }
        catch (Exception $e ) {
            wpmc_flash_message($e->getMessage(), 'error');
        }

        return $this;
    }
}