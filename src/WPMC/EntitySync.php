<?php
namespace WPMC;

use Exception;
use WPMC\Action\AutoRunScheduler;
use WPMC\Entity\DbCleanupScheduler;
use WPMC\DB\EntitySchema;

class EntitySync {
    private $forceAutoRun = false;
    private $runDbCleanups = false;

    public function syncEntities()
    {
        $entities = wpmc_load_app_entities();
        
        $log = [];
        $log['actions'] = 0;
        $log['fields'] = 0;

        do_action('admin_menu');

        // synchronize entities database
        // its important to sync before check to avoid validation table/columns errors
        foreach ( $entities as $ent ) {
            if ( $ent->getDatabase()->getAutoCreateTables() ) {
                $schema = new EntitySchema($ent);
                $schema->doCreateTable($ent);
            }

            $log['fields'] += count($ent->getFieldsObjects());
            $log['actions'] += count($ent->getActionsObjects());
        }

        // check entities
        foreach ( $entities as $ent ) {
            $log['cruds'][] = $ent->validateDefinitions();
        }

        $this->checkConflictingActionAliases($entities);

        // run entity actions auto_run job schedule checker
        (new AutoRunScheduler())->checkScheduledJobs();

        // run entity cleanup job schedulers
        (new DbCleanupScheduler())->checkScheduledJobs();

        do_action('wpmc_after_sync_entities');

        return $log;
    }

    /**
     * @param Entity[] $entities
     */
    private function checkConflictingActionAliases($entities)
    {
        $actionAlias = [];

        foreach ( $entities as $ent ) {
            foreach ( $ent->getActionsObjects() as $action ) {
                $alias = $action->getAlias();
    
                if ( in_array($alias, $actionAlias) ) {
                    throw new Exception('Conflicting action alias: ' . $alias . ' - Entity: ' . $ent->getIdentifier());
                }
    
                $actionAlias[] = $alias;
            }
        }
    }

    public function setForceAutoRun($forceAutoRun)
    {
        $this->forceAutoRun = $forceAutoRun;
        return $this;
    }

    public function setRunDbCleanups($runDbCleanups)
    {
        $this->runDbCleanups = $runDbCleanups;

        return $this;
    }
}