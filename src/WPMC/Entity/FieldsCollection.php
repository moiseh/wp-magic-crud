<?php

namespace WPMC\Entity;

use Exception;
use Illuminate\Support\Collection;
use WPMC\Entity;
use WPMC\FieldBase;
use WPMC\FieldResolver;

class FieldsCollection extends Collection
{
    private $rootEntity;

    public function __construct($items = [], Entity $rootEntity)
    {
        $objects = [];

        foreach ( $items as $name => $fieldArr ) {
            $resolver = new FieldResolver($name, $fieldArr);

            $field = $resolver->getField();
            $field->setRootEntity($rootEntity);

            $objects[$name] = $field;
        }

        $this->rootEntity = $rootEntity;
        parent::__construct($objects);
    }

    /**
     * @return FieldBase[]
     */
    public function fieldItems()
    {
        return $this->items;
    }

    /**
     * @return FieldBase
     */
    public function getFieldObj($name)
    {
        if ( empty($this->items[$name])) {
            throw new Exception('Field not found: ' . $name);
        }

        return $this->items[$name];
    }

    public function canCreate() {
        $creatableFields = $this->getCreatableFields();
        $entity = $this->rootEntity;
        $hasPkey = $entity->getDatabase()->hasPrimaryKey();

        return ( count($creatableFields) > 0 ) && $hasPkey;
    }

    public function hasField($name) {
        return !empty($this->items[$name]);
    }

    public function getCreatableFields() {
        return array_filter($this->fieldItems(), function (FieldBase $field) {
            return $field->isCreatable();
        });
    }

    public function getUpdatableFields() {
        return array_filter($this->fieldItems(), function (FieldBase $field) {
            return $field->isEditable();
        });
    }

    public function getListableFields() {
        return array_filter($this->fieldItems(), function (FieldBase $field) {
            return $field->isListable();
        });
    }

    public function getSortableFields() {
        return array_filter($this->fieldItems(), function (FieldBase $field) {
            return $field->isListable() && $field->isSortable();
        });
    }

    public function validateDefinitions() {
        foreach ( $this->fieldItems() as $field ) {
            $field->validateDefinitions();
        }
    }

    public function exportArray() {
        $arr = [];

        foreach ( $this->fieldItems() as $field ) {
            $fieldArr = $field->toArray();
            $arr['fields'][$field->getName()] = $fieldArr;
        }

        return $arr;
    }
}