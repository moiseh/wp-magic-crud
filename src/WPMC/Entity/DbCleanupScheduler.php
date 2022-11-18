<?php

namespace WPMC\Entity;

class DbCleanupScheduler
{
    public function checkScheduledJobs()
    {
        $args = [];
        $args['status'] = [ \ActionScheduler_Store::STATUS_PENDING, \ActionScheduler_Store::STATUS_RUNNING ];
        $args['group'] = 'wpmc_database_cleanups';
        $args['per_page'] = 1000;

        /**
         * @var \ActionScheduler_Action[]
         */
        $scheduledActions = as_get_scheduled_actions($args);
        
        foreach ( $scheduledActions as $scheduleAction ) {
            $schedule = $scheduleAction->get_schedule();

            if ( $schedule instanceof \ActionScheduler_IntervalSchedule ) {
                $hook = $scheduleAction->get_hook();
                $cleanup = $this->findDatabaseCleanup($scheduleAction);

                if ( !empty($cleanup) ) {
                    $recurrence = $schedule->get_recurrence();
                    $interval = $cleanup->getRunInterval(true);

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
        // enqueue Cleanup jobs for needed entities
        //
        $allCleanups = DatabaseCleanup::getAllDbCleanups();
        
        foreach ( $allCleanups as $cleanup ) {
            $cleanup->scheduleCleanupJob();
        }
    }

    private function findDatabaseCleanup(\ActionScheduler_Action $scheduleAction): ?DatabaseCleanup
    {
        $allCleanups = DatabaseCleanup::getAllDbCleanups();
        
        foreach ( $allCleanups as $cleanup ) {

            if ( $scheduleAction->get_hook() == $cleanup->getCleanupHook() ) {
                return $cleanup;
            }
        }

        return null;
    }
}