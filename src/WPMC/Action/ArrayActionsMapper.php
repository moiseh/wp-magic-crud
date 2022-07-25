<?php

namespace WPMC\Action;

use Exception;
use JsonMapper;
use WPMC\Entity;

class ArrayActionsMapper
{
    public function __construct(private Entity $entity, private array $actions)
    {
        
    }
    
    public function resolveActions()
    {
        $actions = $this->actions;
        $entity = $this->entity;

        $jm = new JsonMapper();
        $jm->bExceptionOnMissingData = true;
        $jm->bExceptionOnUndefinedProperty = true;
        $jm->bEnforceMapType = false;
        
        $objects = [];
        
        foreach ( $actions as $alias => $action ) {
            if ( empty($action['type'])) {
                throw new Exception('Missing action type');
            }

            switch($action['type']) {
                case 'fieldable': $obj = new FieldableAction(); break;
                case 'simple': $obj = new SimpleAction(); break;
                case 'background': $obj = new BackgroundAction(); break;

                default:
                    throw new Exception('Invalid action type: ' .$action['type']);
                    break;
            }
            
            unset($action['type']);

            $obj->setRootEntity($entity);
            $obj->setAlias($alias);
            $jm->map($action, $obj);

            $objects[$alias] = $obj;
        }

        return $objects;
    }
}