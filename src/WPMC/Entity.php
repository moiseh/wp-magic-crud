<?php
namespace WPMC;

use WPMC\DB\EntityDataPersist;
use WPMC\DB\EntityDataRemove;
use WPMC\DB\EntityOptionsList;
use WPMC\DB\EntityQuery;
use WPMC\Entity\ActionsCollection;
use WPMC\Entity\EntityDatabase;
use WPMC\Entity\EntityMenu;
use WPMC\Entity\EntityRest;
use WPMC\Entity\FieldsCollection;

class Entity
{
    /**
     * @var string
     */
    private $identifier;

    /**
     * @var string
     */
    private $json_file;

    /**
     * @var EntityDatabase
     * @required
     */
    private $database;

    /**
     * @var EntityMenu
     * @required
     */
    private $menu;

    /**
     * @var EntityRest
     */
    private $rest;

    /**
     * @var FieldsCollection
     */
    private $fieldsCol;

    /**
     * @var ActionsCollection
     */
    private $actionsCol;

    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
        return $this;
    }

    public function getIdentifier() {
        return $this->identifier;
    }
    
    public function setJsonFile($json_file)
    {
        $this->json_file = $json_file;
        return $this;
    }

    public function getDatabase()
    {
        return $this->database;
    }

    public function setDatabase(EntityDatabase $database)
    {
        $database->setRootEntity($this);

        $this->database = $database;
        return $this;
    }

    public function getMenu()
    {
        return $this->menu;
    }

    public function setMenu(EntityMenu $menu)
    {
        $this->menu = $menu;
        return $this;
    }

    public function getRest()
    {
        return $this->rest;
    }

    public function setRest(EntityRest $rest)
    {
        $this->rest = $rest;
        return $this;
    }

    public function hasActions()
    {
        return ( $this->actionsCollection()->count() > 0 );
    }

    public function setActions(array $actions)
    {
        $this->actionsCol = new ActionsCollection($actions, $this);
        return $this;
    }

    public function actionsCollection() {
        if ( empty($this->actionsCol) ) {
            $this->actionsCol = new ActionsCollection([], $this);
        }

        return $this->actionsCol;
    }

    /**
     * @return Action[]
     */
    public function getActionsObjects()
    {
        return $this->actionsCollection()->actionItems();
    }

    public function setFields(array $fields)
    {
        $this->fieldsCol = new FieldsCollection($fields, $this);
        return $this;
    }

    public function fieldsCollection() {
        return $this->fieldsCol;
    }

    /**
     * @return FieldBase[]
     */
    public function getFieldsObjects()
    {
        return $this->fieldsCollection()->fieldItems();
    }

    public function hasDbTableCleanup() {
        $cleanup = $this->getDatabase()->getCleanup();
        return !empty($cleanup);
    }

    public function getJsonFile() {
        return $this->json_file;
    }

    public function hasJsonFile() {
        return !empty($this->json_file);
    }

    public function saveToJson() {
        return (new EntityExport($this))->toJsonFile();
    }

    public function buildOptions($ids = []) {
        return (new EntityOptionsList($this))->buildOptionsList($ids);
    }

    public function delete($ids) {
        return (new EntityDataRemove($this))->delete($ids);
    }

    public function findById($id, $throwWhenNotExists = false) {
        return (new EntityQuery($this))->findByEntityId($id, $throwWhenNotExists);
    }

    public function displayTitleById($id, $throwWhenNotExists = false) {
        $row = $this->findById($id, $throwWhenNotExists);
        return $row[ $this->getDatabase()->getDisplayField() ];
    }

    public function saveDbData($item, $pkValue = null) {
        return (new EntityDataPersist($this))->saveEntityData($item, $pkValue);
    }

    public function validateDefinitions()
    {
        $this->getMenu()->validateDefinitions();
        $this->getDatabase()->validateDefinitions();
        $this->actionsCollection()->validateDefinitions();
        $this->fieldsCollection()->validateDefinitions();

        return 'Crud: ' . $this->getIdentifier() . 
            ' - Table: ' . $this->getDatabase()->getTableName() .
            ' - DBSync: ' . ( $this->getDatabase()->getAutoCreateTables() ? 'Yes' : 'No').
            ' - REST: ' . ( $this->getRest()->getExposeAsRest() ? 'Yes' : 'No').
            ' - Fields: ' . count($this->getFieldsObjects());
    }

    public function toArray() {
        $arr = [];
        $arr += $this->getDatabase()->toArray();
        $arr += $this->getMenu()->toArray();
        $arr['rest']['expose_as_rest'] = $this->getRest()->getExposeAsRest();
        $arr += $this->actionsCollection()->exportArray();
        $arr += $this->fieldsCollection()->exportArray();

        return $arr;
    }
}