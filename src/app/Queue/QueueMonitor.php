<?php

namespace App\Queue;

use App\Config;
use App\Enums\QueuePriority;
use App\Logger;
use App\Queue\Jobs\GetPendingJobs;
use App\Queue\Jobs\Heartbeat;
use App\Queue\Jobs\PruneCompletedJobs;
use App\Queue\Jobs\ReportMetric;
use App\Repositories\QueueRepository;
use Exception;

class QueueMonitor
{
    /** @var int */
    private $maxJobsBeforeRestart;

    /** @var int */
    private $maxMemoryUsage;

    /** @var int */
    private $maxExecutionTime;

    /** @var int */
    private $startTime;

    /** @var int Time of last critical job check */
    private $lastCriticalJobCheck = 0;

    /** @var int How often to check for critical jobs (seconds) */
    private $criticalJobCheckInterval;

    /** @var array List of critical job classes */
    private $criticalJobClasses = [
        Heartbeat::class,
        GetPendingJobs::class,
        PruneCompletedJobs::class,
        ReportMetric::class,
    ];

    /**
     * Queue constructor.
     */
    public function __construct()
    {
        $this->maxJobsBeforeRestart = Config::get( 'QUEUE_MAX_JOBS', 1000 );
        $this->maxMemoryUsage = Config::get( 'QUEUE_MAX_MEMORY', 100 ) * 1024 * 1024;
        $this->maxExecutionTime = Config::get( 'QUEUE_MAX_TIME', 3600 );
        $this->criticalJobCheckInterval = Config::get( 'QUEUE_CRITICAL_CHECK_INTERVAL', 30 );

        $this->startTime = time();
    }

    /**
     * Check if worker should restart based on limits
     *
     * @param int $jobsProcessed
     * @return bool True if worker should restart
     */
    public function shouldRestartQueue( int $jobsProcessed ): bool
    {
        if ( $jobsProcessed >= $this->maxJobsBeforeRestart ) {
            return true;
        }

        if ( memory_get_usage( true ) > $this->maxMemoryUsage ) {
            return true;
        }

        if ( ( time() - $this->startTime ) >= $this->maxExecutionTime ) {
            return true;
        }

        return false;
    }

    /**
     * Check if critical jobs should be checked
     *
     * @return bool True if critical jobs should be checked
     */
    public function shouldVerifyCriticalJobsExistance(): bool
    {
        return time() - $this->lastCriticalJobCheck >= $this->criticalJobCheckInterval;
    }

    /**
     * Ensure that critical jobs exist in the queue
     */
    public function ensureCriticalJobsExist(): void
    {
        $queueRepository = QueueRepository::getInstance();

        foreach ( $this->criticalJobClasses as $jobClass ) {
            try {
                $exists = $queueRepository->jobExists( $jobClass );

                if ( !$exists ) {
                    // Schedule for immediate execution
                    $queueRepository->add( new $jobClass, QueuePriority::Critical );
                }
            } catch ( Exception $e ) {
                Logger::log( "Error ensuring job exists {$jobClass}: " . $e->getMessage() );
            }
        }

        $this->lastCriticalJobCheck = time();
    }
}
