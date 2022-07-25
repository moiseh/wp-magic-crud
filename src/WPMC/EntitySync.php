<?php
namespace WPMC;

use WPMC\DB\EntitySchema;

class EntitySync {
    private $forceAutoRun = false;

    public function syncEntities()
    {
        $entities = wpmc_load_app_entities();
        $log = array();
    
        do_action('admin_menu');

        // synchronize entities database
        // its important to sync before check to avoid validation table/columns errors
        foreach ( $entities as $ent ) {
            if ( $ent->getDatabase()->getAutoCreateTables() ) {
                $schema = new EntitySchema($ent);
                $schema->doCreateTable($ent);
            }
        }

        // check entities
        foreach ( $entities as $ent ) {
            $log[] = $ent->validateDefinitions();
        }

        // run database tables cleanup
        foreach ( $entities as $ent ) {
            if ( $ent->hasDbTableCleanup() ) {
                $ent->getDatabase()->getCleanup()->runEntityCleanup();
            }
        }

        // run entity actions auto_run
        foreach ( $entities as $ent ) {
            if ( $ent->hasActions() ) {
                $ent->actionsCollection()->checkRunEntityActions( $this->forceAutoRun );
            }
        }

        return $log;
    }

    public function setForceAutoRun($forceAutoRun)
    {
        $this->forceAutoRun = $forceAutoRun;
        return $this;
    }
}