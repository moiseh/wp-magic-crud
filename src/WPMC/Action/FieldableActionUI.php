<?php

namespace WPMC\Action;

use Exception;
use WPMC\UI\ActionForm;

class FieldableActionUI extends UIAction
{
    public function __construct(FieldableAction $action)
    {
        parent::__construct($action);
    }

    private function isFormPost() {
        return isset($_REQUEST['nonce']) && wp_verify_nonce($_REQUEST['nonce'], WPMC_ROOT_DIR);
    }

    public function getFormParams() {
        /** @var FieldableAction */
        $action = $this->action;

        $params = [];

        foreach ( $action->getFieldParams() as $field ) {
            $name = $field->getName();
            $params[$name] = $this->getInput($name);
        }

        return $params;
    }

    public function getInput($input) {
        return isset($_REQUEST[$input]) ? $_REQUEST[$input] : null;
    }

    public function renderOrExecute($ids = []) {
        /** @var FieldableAction */
        $action = $this->action;

        $entity = $action->getRootEntity();
        $runner = $action->getRunner();

        if ( $this->isFormPost() ) {
            try {
                $params = $this->getFormParams();
                
                $result = $runner->executeAction( $ids, $params );
                $this->checkResult($result);

                wpmc_redirect( wpmc_entity_admin_url($entity) );
            }
            catch (Exception $e ) {
                wpmc_flash_message($e->getMessage(), 'error');
                $this->renderDefaultForm();
            }
        }
        else {
            $this->renderDefaultForm();
        }

        return $this;
    }

    public function renderDefaultForm()
    {
        $action = $this->action;
        $title = $action->getLabel();

        echo (new ActionForm($action, $title))
            ->setContextIds( $action->getRunner()->getContextIds() )
            ->renderForm();
    }
}