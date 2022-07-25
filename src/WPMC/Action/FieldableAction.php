<?php
namespace WPMC\Action;

use Exception;
use WPMC\Action;
use WPMC\FieldBase;
use WPMC\Action\FieldableActionRunner;
use WPMC\FieldResolver;

class FieldableAction extends Action
{
    /**
     * @var array
     * @required
     */
    private $field_parameters = [];

    /**
     * @var FieldBase[]
     */
    private $fieldParams = [];

    public function __construct(array $fieldParams = null)
    {
        $this->field_parameters = $fieldParams;
    }

    public function setFieldParameters($params)
    {
        $entity = $this->getRootEntity();
        $fields = [];

        foreach ( $params as $name => $fieldArr ) {
            $resolver = new FieldResolver($name, $fieldArr);

            $field = $resolver->getField();
            $field->setRootEntity($entity);

            $fields[$name] = $field;
        }

        $this->fieldParams = $fields;
        return $this;
    }

    public function getFieldParams() {
        return $this->fieldParams;
    }

    /**
     * @return FieldableActionRunner
     */
    public function getRunner() {
        if ( empty($this->runner) ) {
            $this->runner = new FieldableActionRunner($this);
        }

        return $this->runner;
    }

    public function getActionUI()
    {
        $action = clone( $this )->getRunner()->setContext(Action::CONTEXT_UI_FORM);        
        return new FieldableActionUI($action);
    }

    public function getRestMethod()
    {
        return 'POST';
    }
    
    public function getType()
    {
        return 'fieldable';
    }

    public function toArray()
    {
        $arr = parent::toArray();
        $arr['field_parameters'] = [];

        foreach ( $this->getFieldParams() as $name => $field ) {
            $arr['field_parameters'][$name] = $field->toArray();
        }

        return $arr;
    }
}