<?php
namespace WPMC\Action;

trait TriggerableActionTrait
{
    private $run_after_update;
    private $run_after_create;

    public function getRunAfterUpdate()
    {
        return $this->run_after_update;
    }

    public function setRunAfterUpdate($runAfterUpdate)
    {
        $this->run_after_update = $runAfterUpdate;
        return $this;
    }

    public function getRunAfterCreate()
    {
        return $this->run_after_create;
    }

    public function setRunAfterCreate($runAfterCreate)
    {
        $this->run_after_create = $runAfterCreate;
        return $this;
    }

    protected function triggerableArray()
    {
        $arr = [];

        if ( isset($this->run_after_create) ) {
            $arr['run_after_create'] = $this->getRunAfterCreate();
        }

        if ( isset($this->run_after_create) ) {
            $arr['run_after_update'] = $this->getRunAfterUpdate();
        }
        
        return $arr;
    }
}