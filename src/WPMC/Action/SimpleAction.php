<?php
namespace WPMC\Action;

use WPMC\Action;

class SimpleAction extends Action implements ITriggerableAction
{
    use TriggerableActionTrait;

    public function getActionUI()
    {
        $action = clone( $this )->getRunner()->setContext(Action::CONTEXT_UI_FORM);        
        return new SimpleActionUI($action);
    }

    public function getType()
    {
        return 'simple';
    }

    public function toArray()
    {
        $arr = parent::toArray();
        $arr += $this->triggerableArray();

        return $arr;
    }
}