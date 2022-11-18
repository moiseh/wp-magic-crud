<?php

namespace WPMC\Action;

use Exception;

class FieldableActionRunner extends ActionRunner
{
    private $inputParams = [];

    public function setInputParams($params = []) {
        $this->validateRequiredParams($params);
        $this->inputParams = $params;

        return $this;
    }

    public function getInputParams() {
        return $this->inputParams;
    }

    public function getInputParam($name, $default = null) {
        $params = $this->getInputParams();
        return isset($params[$name]) ? $params[$name] : $default;
    }

    public function hasParam($name) {
        return !empty($this->inputParams[$name]);
    }

    public function executeAction($ids = [], $params = []) {
        $this->setInputParams($params);
        return parent::executeAction($ids);
    }

    public function validateRequiredParams($params = []) {
        /** @var FieldableAction */
        $action = $this->getRootAction();

        foreach ( $action->getFieldParams() as $field ) {
            $name = $field->getName();
            $value = isset($params[$name]) ? $params[$name] : null;

            if ( $field->getRequired() && empty($value) ) {
                throw new Exception('Required field: ' . $field->getLabel() . ' (' . $name . ')');
            }
        }

        return $this;
    }
}