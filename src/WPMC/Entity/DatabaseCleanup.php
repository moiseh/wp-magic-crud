<?php

namespace WPMC\Entity;

use Exception;
use Illuminate\Database\Query\Builder;

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
    private $where_raw;

    private $deletedCount = 0;
    private $totalChunks = 0;

    public function toArray(): array
    {
        $arr = [];
        $arr['run_interval'] = $this->getRunInterval();
        $arr['where_raw'] = $this->getWhereRaw();

        return $arr;
    }

    public function validateDefinitions()
    {
        $cleanInterval = $this->getRunInterval();

        if ( strtotime($cleanInterval) === false ) {
            throw new Exception('Invalid database.cleanup.run_interval => ' . $cleanInterval);
        }

        // check for query errors
        $query = $this->buildCleanupQuery();
        $query->limit(1);
        $query->first();
    }

    public function runEntityCleanup()
    {
        $db = $this->getRootEntityDatabase();
        $entity = $db->getRootEntity();
        $table = $db->getTableName();
        $primaryKey = $db->getPrimaryKey();

        // run deletions
        $query = $this->buildCleanupQuery();
        $query->chunkById(1000, function(\Illuminate\Support\Collection $items) use($entity, $primaryKey){
            $ids = $items->pluck($primaryKey)->toArray();
            $entity->delete($ids);

            $this->deletedCount += count($ids);
            $this->totalChunks ++;

            return true;
        }, $primaryKey);

        //
        // admin alerts
        //
        $deleteds = $this->deletedCount;

        if ( function_exists('psInfoLog') && ( $deleteds > 0 ) ) {
            $deleteds = psNiceNumber( $deleteds );
            $chunks = $this->totalChunks;

            psInfoLog("Cleanup {$deleteds} records for table {$table} - Chunks: {$chunks}");
        }
    }

    private function buildCleanupQuery(): Builder
    {
        $db = $this->getRootEntityDatabase();
        $entity = $db->getRootEntity();
        $whereRaw = $this->getWhereRaw();

        if ( !preg_match('/INTERVAL (.*)/i', $whereRaw) ) {
            throw new Exception('Where raw seems to be invalid: ' . $whereRaw);
        }

        $query = $entity->newEloquentQuery();
        $query->select( $db->getPrimaryKey() );
        $query->whereRaw( $whereRaw );

        return $query;
    }

    public function getCleanupHook()
    {
        $db = $this->getRootEntityDatabase();
        $entity = $db->getRootEntity();
        $identifier = $entity->getIdentifier();
        $hook = 'dbcleanup_' . $identifier;
        
        return $hook;
    }

    public function scheduleCleanupJob()
    {
        $hook = $this->getCleanupHook();

        if ( !as_has_scheduled_action($hook, null, 'wpmc_database_cleanups') ) {
            $interval = $this->getRunInterval(true);

            as_schedule_recurring_action( time(), $interval, $hook, [], 'wpmc_database_cleanups' );
            psInfoLog("Scheduled {$hook}, every {$interval} secs");
        }
    }

    /**
     * @return DatabaseCleanup[]
     */
    public static function getAllDbCleanups(): ?array
    {
        $entities = wpmc_load_app_entities();
        $cleanups = [];

        foreach ( $entities as $ent ) {
            if ( $ent->hasDbTableCleanup() ) {
                $db = $ent->getDatabase();
                $cleanups[] = $db->getCleanup();
            }
        }

        return $cleanups;
    }

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

    public function setWhereRaw(string $where_raw)
    {
        $this->where_raw = $where_raw;
        return $this;
    }

    public function getWhereRaw()
    {
        return $this->where_raw;
    }
}