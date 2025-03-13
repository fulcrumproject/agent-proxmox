<?php

namespace App\Queue\Jobs;

use App\Enums\QueuePriority;
use App\Queue\Jobs\Contracts\Autoschedule;
use App\Queue\Jobs\Contracts\Job;
use App\Repositories\QueueRepository;

class PruneCompletedJobs extends Job implements Autoschedule
{
    public function execute(): void
    {
        $repo = QueueRepository::getInstance();

        $repo->deleteOldCompletedJobs();
    }

    public function getScheduleInterval(): int
    {
        return 60 * 60 * 24; // 24 hours
    }

    public function getSchedulePriority(): QueuePriority
    {
        return QueuePriority::Low;
    }
}
