<?php

namespace App\Queue\Jobs;

use App\Enums\QueuePriority;
use App\Queue;
use App\Queue\Jobs\Contracts\Autoschedule;
use App\Queue\Jobs\Contracts\Job;
use App\Services\FulcrumCore\FulcrumCore;

class GetPendingJobs extends Job implements Autoschedule
{
    public function execute(): void
    {
        $jobs = FulcrumCore::getInstance()->getPendingJobs();

        array_walk( $jobs, fn( $job ) => Queue::push( new ProxmoxControl( $job ) ) );
    }

    public function getScheduleInterval(): int
    {
        return 30;
    }

    public function getSchedulePriority(): QueuePriority
    {
        return QueuePriority::Critical;
    }
}
