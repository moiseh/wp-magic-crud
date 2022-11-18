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

    public function getJobHook()
    {
        $entity = $this->getRootEntity();
        $identifier = $entity->getIdentifier();
        $alias = $this->getAlias();
        $hook = 'action_' . $alias;
        
        return $hook;
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

    /**
     * @return \WPMC\Action\BackgroundAction[]
     */
    public static function getAllBackgroundActions(): array
    {
        $entities = wpmc_load_app_entities();
        $arActions = [];

        foreach( $entities as $entity ) {
            $entityActions = $entity->actionsCollection()->getBackgroundJobActions();
            $arActions = array_merge($arActions, $entityActions);
        }

        return $arActions;
    }
}