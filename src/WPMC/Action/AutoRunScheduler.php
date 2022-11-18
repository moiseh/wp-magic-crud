<?php

namespace WPMC\Action;

class AutoRunScheduler
{
    public function checkScheduledJobs()
    {
        $args = [];
        $args['status'] = [ \ActionScheduler_Store::STATUS_PENDING, \ActionScheduler_Store::STATUS_RUNNING ];
        $args['group'] = 'wpmc_autorun_checker';
        $args['per_page'] = 1000;

        /**
         * @var \ActionScheduler_Action[]
         */
        $scheduledActions = as_get_scheduled_actions($args);
        
        foreach ( $scheduledActions as $scheduleAction ) {
            $schedule = $scheduleAction->get_schedule();

            if ( $schedule instanceof \ActionScheduler_IntervalSchedule ) {
                $hook = $scheduleAction->get_hook();
                $autoRunAction = $this->findAutoRunAction($scheduleAction);

                if ( !empty($autoRunAction) ) {
                    $recurrence = $schedule->get_recurrence();
                    $autoRun = $autoRunAction->getAutoRun();
                    $interval = $autoRun->getInterval(true);

                    if ( $recurrence != $interval ) {
                        as_unschedule_all_actions($hook);
                        psInfoLog("Unscheduling {$hook} (interval changed)");
                    }
                }
                else {
                    as_unschedule_all_actions($hook);
                    psInfoLog("Unscheduling {$hook} (action removed or renamed)");
                }
            }
        }

        //
        // enqueue AutoRun job checker for needed entities
        //
        $entities = wpmc_load_app_entities();

        foreach ( $entities as $ent ) {
            if ( $ent->hasActions() ) {
                $autoRunActions = $ent->actionsCollection()->getAutoRunJobActions();

                foreach ( $autoRunActions as $action ) {
                    $autoRun = $action->getAutoRun();
                    $autoRun->scheduleAutoJob();
                }
            }
        }
    }

    private function findAutoRunAction(\ActionScheduler_Action $scheduleAction): ?BackgroundAction
    {
        $allAutoRunActions = $this->getAllAutoRunActions();
        
        foreach ( $allAutoRunActions as $action ) {
            $autoRun = $action->getAutoRun();

            if ( $scheduleAction->get_hook() == $autoRun->getCheckerJobHook() ) {
                return $action;
            }
        }

        return null;
    }

    /**
     * @return \WPMC\Action\BackgroundAction[]
     */
    public function getAllAutoRunActions()
    {
        static $arActions = null;

        if ( !isset($arActions) ) {
            $arActions = [];
            $entities = wpmc_load_app_entities();

            foreach( $entities as $entity ) {
                $arActions = array_merge($arActions, $entity->actionsCollection()->getAutoRunJobActions());
            }
        }

        return $arActions;
    }
}