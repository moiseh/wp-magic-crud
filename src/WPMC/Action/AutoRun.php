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
     * @var string
     */
    private $batch_size;

    /**
     * @var string
     */
    private $query_callback;

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

    public function executeAutoRunQueue($force = false)
    {
        $action = $this->getRootAction();
        $entity = $action->getRootEntity();
        $alias = $action->getAlias();
        $transient = 'wpmc_background_action_' . $alias;
        $interval = $this->getInterval(true);

        if ( !get_transient($transient) || $force ) {
            set_transient($transient, 1, $interval);

            $batchSize = $this->getBatchSize();
            $pkey = $entity->getDatabase()->getPrimaryKey();

            $query = $this->buildARQuery();

            $query->chunkById($batchSize, function(\Illuminate\Support\Collection $items) use($pkey, $action){
                $ids = $items->pluck($pkey);
                $action->getRunner()->executeAction($ids);
            }, $pkey);
        }
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

        if ( !empty($queryCallback) ) {
            $query = call_user_func($queryCallback, $query);
        }

        return $query;
    }

    public function validateDefinitions()
    {
        $arInterval = $this->getInterval();

        if ( strtotime($arInterval) === false ) {
            throw new Exception('Invalid action.auto_run.interval => ' . $arInterval);
        }
    }

    public function toArray()
    {
        $arr = [];
        $arr['auto_run']['interval'] = $this->getInterval();
        $arr['auto_run']['batch_size'] = (int) $this->getBatchSize();

        if ( $this->hasQueryCallback() ) {
            $arr['auto_run']['query_callback'] = $this->getQueryCallback();
        }

        return $arr;
    }
}