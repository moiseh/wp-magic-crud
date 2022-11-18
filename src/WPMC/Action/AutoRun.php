<?php

namespace WPMC\Action;

use Exception;
use WPMC\DB\EntityQuery;

class AutoRun
{
    /**
     * @var BackgroundAction
     */
    private $rootAction;

    /**
     * @required
     * @var string
     */
    private $interval;

    /**
     * @required
     * @var int
     */
    private $batch_size;

    /**
     * @required
     * @var int
     */
    private $limit_per_run;

    /**
     * @var string
     */
    private $query_callback;

    /**
     * @var string
     */
    private $where_raw;

    public function getRootAction()
    {
        return $this->rootAction;
    }

    public function setRootAction(BackgroundAction $rootAction)
    {
        $this->rootAction = $rootAction;
        return $this;
    }

    public function getInterval($convertToSecs = false)
    {
        $interval = $this->interval;

        if ( $convertToSecs && !empty($interval) ) {
            $interval = ( strtotime($interval) - time() );
        }

        return $interval;
    }

    public function setInterval(string $interval)
    {
        $this->interval = $interval;

        return $this;
    }

    public function getBatchSize()
    {
        return $this->batch_size;
    }

    public function setBatchSize(string $batch_size)
    {
        $this->batch_size = $batch_size;

        return $this;
    }

    public function hasQueryCallback() {
        return !empty($this->query_callback);
    }

    public function getQueryCallback()
    {
        return $this->query_callback;
    }

    public function setQueryCallback(string $query_callback)
    {
        $this->query_callback = $query_callback;

        return $this;
    }

    public function getWhereRaw()
    {
        return $this->where_raw;
    }

    public function setWhereRaw(string $where_raw)
    {
        $this->where_raw = $where_raw;
        return $this;
    }

    public function getLimitPerRun()
    {
        return $this->limit_per_run;
    }

    public function setLimitPerRun(int $limit)
    {
        $this->limit_per_run = $limit;
        return $this;
    }

    public function getCheckerJobHook()
    {
        $action = $this->getRootAction();
        $entity = $action->getRootEntity();
        $alias = $action->getAlias();
        $identifier = $entity->getIdentifier();

        return 'autorun_' . $alias;
    }

    public function scheduleAutoJob()
    {
        $hook = $this->getCheckerJobHook();

        if ( !as_has_scheduled_action($hook) ) {
            $interval = $this->getInterval(true);

            as_schedule_recurring_action( time(), $interval, $hook, [], 'wpmc_autorun_checker' );
            psInfoLog("Scheduled {$hook}, every {$interval} secs");
        }
    }

    public function maybeDispatchAutoRunTasks()
    {
        $action = $this->getRootAction();
        $entity = $action->getRootEntity();
        $batchSize = $this->getBatchSize();
        $pkey = $entity->getDatabase()->getPrimaryKey();

        $query = $this->buildARQuery();
        $query->chunk($batchSize, function(\Illuminate\Support\Collection $items) use($pkey, $action){
            static $count = 0;

            $ids = $items->pluck($pkey);
            $action->getRunner()->enqueueAsynchronous($ids);

            $count += count($ids);
            $maxPerRun = $this->getLimitPerRun();

            // limit_per_run check
            if ( ( $maxPerRun > 0 ) && ( $count >= $maxPerRun ) ) {
                // psInfoLog('Breaking chunk to limit ' . $queryLimit . ' items');
                return false;
            }

            return true;
        });
    }

    /**
     * @return \Illuminate\Database\Query\Builder
     */
    private function buildARQuery()
    {
        $rootAction = $this->getRootAction();
        $entity = $rootAction->getRootEntity();
        $queryCallback = $this->getQueryCallback();
        
        $entityQuery = new EntityQuery($entity);
        $query = $entityQuery->buildEloquentQuery(false);
        $query->orderByRaw( $entityQuery->getDefaultOrderCol() );

        $whereRaw = $this->getWhereRaw();
        
        $query->limit( $this->getLimitPerRun() );

        if ( !empty($whereRaw) ) {
            $query->whereRaw($whereRaw);
        }

        if ( !empty($queryCallback) ) {
            $query = call_user_func($queryCallback, $query);
        }

        return $query;
    }

    public function validateDefinitions()
    {
        $arInterval = $this->getInterval();
        $action = $this->getRootAction();
        $alias = $action->getAlias();
        $callback = $this->getQueryCallback();

        if ( strtotime($arInterval) === false ) {
            throw new Exception("Invalid action.auto_run.interval => {$arInterval}");
        }

        // if ( !is_callable($callback) ) {
        //     throw new Exception("Query callback is not callable for {$alias} action");
        // }

        // execute AutoRun query to check if have any syntax errors
        $query = $this->buildARQuery();
        $query->first();
    }

    public function toArray()
    {
        $arr = [];
        $arr['auto_run']['interval'] = $this->getInterval();
        $arr['auto_run']['batch_size'] = (int) $this->getBatchSize();
        $arr['auto_run']['limit_per_run'] = (int) $this->getLimitPerRun();

        if ( $this->hasQueryCallback() ) {
            $arr['auto_run']['query_callback'] = $this->getQueryCallback();
        }

        if ( !empty($this->where_raw) ) {
            $arr['auto_run']['where_raw'] = $this->getWhereRaw();
        }

        return $arr;
    }
}