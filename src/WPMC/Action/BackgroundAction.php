<?php
namespace WPMC\Action;

use WPMC\Action;
use WPMC\Action\AutoRun;
use WPMC\Action\BackgroundActionRunner;

class BackgroundAction extends Action implements ITriggerableAction
{
    use TriggerableActionTrait;

    /**
     * @var AutoRun
     */
    private $auto_run;

    /**
     * @return BackgroundActionRunner
     */
    public function getRunner()
    {
        if ( empty($this->runner) ) {
            $this->runner = new BackgroundActionRunner($this);
        }

        return $this->runner;
    }

    public function getActionUI()
    {
        $action = clone( $this )->getRunner()->setContext(Action::CONTEXT_UI_FORM);        
        return new BackgroundActionUI($action);
    }

    public function hasAutoRun() {
        return !empty($this->auto_run);
    }

    public function getAutoRun()
    {
        return $this->auto_run;
    }

    public function setAutoRun(AutoRun $autoRun)
    {
        $autoRun->setRootAction($this);

        $this->auto_run = $autoRun;
        return $this;
    }

    public function validateDefinitions()
    {
        $this->getAutoRun()->validateDefinitions();

        parent::validateDefinitions();
    }

    public function getType()
    {
        return 'background';
    }

    public function toArray()
    {
        $arr = parent::toArray();
        $arr += $this->triggerableArray();

        if ( $this->hasAutoRun() ) {
            $arr = array_merge($arr, $this->getAutoRun()->toArray());
        }

        return $arr;
    }
}