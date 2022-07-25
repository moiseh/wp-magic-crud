<?php
namespace WPMC\Action;

interface ITriggerableAction
{
    public function getRunAfterUpdate();
    public function getRunAfterCreate();
}