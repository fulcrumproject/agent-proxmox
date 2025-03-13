<?php

namespace App\Queue;

use App\Logger;
use App\Queue\Jobs\Contracts\Autoschedule;
use App\Repositories\QueueRepository;
use Exception;

class JobProcessor
{
    public static function process( array $job )
    {
        $queueRepository = QueueRepository::getInstance();

        try {
            $jobData = json_decode( $job['payload'], true );
            $className = $jobData['class'];
            $serializedJob = $jobData['serialized'];

            if ( !class_exists( $className ) ) {
                throw new Exception( "Class {$className} does not exist." );
            }

            Logger::log( "Processing job {$className}" );

            $jobInstance = unserialize( $serializedJob );
            $jobInstance->execute();

            if ( $jobInstance instanceof Autoschedule ) {
                $queueRepository->add(
                    new $className(),
                    $jobInstance->getSchedulePriority(),
                    $jobInstance->getScheduleInterval()
                );
            }

            $queueRepository->markJobCompleted( $job['id'] );
            return true;
        } catch ( Exception $e ) {
            $queueRepository->markJobFailed( $job['id'], $e->getMessage() );

            Logger::log( "Failed to process job {$job['id']}: " . $e->getMessage() );

            return false;
        }
    }
}
