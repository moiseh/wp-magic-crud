<?php

namespace WPMC\Action;

use WPMC\Action;

class RerunAction
{
    public function __construct(private array $actionIds)
    {
        
    }

    public function execute()
    {
        $actionIds = $this->actionIds;
        $actionLogs = wpmc_get_entity('action_logs');

        foreach ( $actionIds as $id ) {
            $row = $actionLogs->findById($id);

            $rootEntity = wpmc_get_entity( $row['entity'] );
            $params = !empty($row['params']) ? (array) json_decode($row['params']) : [];
            $contextIds = (array) json_decode($row['action_ids']);

            $newAction = $rootEntity->actionsCollection()->getActionByAlias( $row['action_name'] );
            $newAction->setRootEntity( $rootEntity );
            $newAction->setAlias( $row['action_name'] );

            $runner = $newAction->getRunner();
            $runner->setContext(Action::CONTEXT_ACTION_RERUN);

            if ( $newAction instanceof BackgroundAction ) {
                $result = $newAction->getRunner()->executeNow( $contextIds, $params );
            }
            else {
                $result = $runner->executeAction( $contextIds, $params );
            }
        }

        return __('Action executed successfully');
    }
}