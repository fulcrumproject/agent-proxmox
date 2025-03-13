<?php

namespace App;

use App\Enums\QueuePriority;
use App\Queue\JobProcessor;
use App\Queue\QueueMonitor;
use App\Repositories\QueueRepository;
use Exception;

class Queue
{
    private static ?self $instance = null;

    /** @var QueueRepository */
    private QueueRepository $queueRepository;

    /** @var QueueMonitor */
    private QueueMonitor $queueMonitor;

    /** @var int */
    private $jobsProcessed = 0;

    /**
     * Queue constructor.
     */
    private function __construct()
    {
        $this->queueRepository = QueueRepository::getInstance();
        $this->queueMonitor = new QueueMonitor();
    }

    /**
     * Get the singleton instance of Queue.
     *
     * @return Queue
     */
    public static function getInstance(): Queue
    {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Run the queue worker
     */
    public function run(): void
    {
        Logger::log( "Worker started at: " . date( 'Y-m-d H:i:s' ) );

        // Main processing loop
        while ( true ) {
            if ( $this->queueMonitor->shouldRestartQueue( $this->jobsProcessed ) ) {
                exit( 0 );
            }

            // Periodically check if critical jobs exist
            if ( $this->queueMonitor->shouldVerifyCriticalJobsExistance() ) {
                $this->queueMonitor->ensureCriticalJobsExist();
            }

            // Process available jobs
            try {
                $job = $this->queueRepository->findAndReserveNextAvailableJob();

                if ( $job ) {
                    JobProcessor::process( $job );
                    $this->jobsProcessed++;
                } else {
                    // No jobs available, sleep to prevent CPU spinning
                    sleep( 1 );
                }
            } catch ( Exception $e ) {
                Logger::log( "Error: " . $e->getMessage() );
                sleep( 5 ); // Wait before retry
            }
        }
    }

    /**
     * Add a job to the queue
     *
     * @param mixed $job Job data
     * @param QueuePriority|null $priority Job priority (higher number = higher priority)
     * @param int|null $delaySeconds Delay in seconds before job becomes available
     * @return int The ID of the newly created job
     */
    public static function push(
                       $job,
        ?QueuePriority $priority = QueuePriority::Low,
        ?int           $delaySeconds = null
    ): int {
        return self::getInstance()->queueRepository->add( $job, $priority, $delaySeconds );
    }
}
