<?php

namespace App\Queue\Jobs\Contracts;

use App\Enums\QueuePriority;

/**
 * Interface for jobs that need to be automatically rescheduled
 */
interface Autoschedule
{
    /**
     * Get the interval in seconds for how often this job should run
     *
     * @return int Interval in seconds
     */
    public function getScheduleInterval(): int;

    /**
     * Get the priority for this job when rescheduled
     *
     * @return QueuePriority Priority (higher number = higher priority)
     */
    public function getSchedulePriority(): QueuePriority;
}
