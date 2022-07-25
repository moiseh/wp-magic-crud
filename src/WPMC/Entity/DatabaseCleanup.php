<?php

namespace WPMC\Entity;

use Exception;

class DatabaseCleanup
{
    /**
     * @var EntityDatabase
     */
    private $rootEntityDatabase;

    /**
     * @var string
     * @required
     */
    private $run_interval;

    /**
     * @var string
     * @required
     */
    private $sql;

    public function getRootEntityDatabase()
    {
        return $this->rootEntityDatabase;
    }

    public function setRootEntityDatabase(EntityDatabase $redb)
    {
        $this->rootEntityDatabase = $redb;

        return $this;
    }

    public function getRunInterval($convertToSecs = false) {
        $interval = $this->run_interval;

        if ( $convertToSecs && !empty($interval) ) {
            $interval = ( strtotime($interval) - time() );
        }

        return $interval;
    }

    public function setRunInterval(string $run_interval)
    {
        $this->run_interval = $run_interval;

        return $this;
    }

    public function getSql()
    {
        return $this->sql;
    }

    public function setSql(string $sql)
    {
        $this->sql = $sql;
        return $this;
    }

    public function validateDefinitions()
    {
        $cleanInterval = $this->getRunInterval();

        if ( strtotime($cleanInterval) === false ) {
            throw new Exception('Invalid database.cleanup.run_interval => ' . $cleanInterval);
        }
    }

    public function runEntityCleanup()
    {
        global $wpdb;

        $entityDb = $this->getRootEntityDatabase();
        $entity = $entityDb->getRootEntity();
        $table = $entityDb->getTableName();
        $sql = $this->getSql();

        if ( !preg_match('/DELETE FROM ' . $table . ' WHERE (.*) INTERVAL /', $sql) ) {
            throw new Exception('Cleanup SQL seems to be invalid: ' . $sql);
        }

        $interval = $this->getRunInterval(true);
        $identifier = $entity->getIdentifier();
        $transient = 'wpmc_entity_dbcleanup_' . $identifier;

        if ( !get_transient($transient) ) {

            set_transient($transient, 1, $interval);

            $deleted = $wpdb->query($sql);
        
            if ( $deleted > 0 && function_exists('psInfoLog') ) {
                psInfoLog("Total DB cleanup records for entity {$identifier}: {$deleted}");
            }
        }
    }
}