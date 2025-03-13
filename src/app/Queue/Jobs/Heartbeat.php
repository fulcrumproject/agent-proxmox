<?php

namespace App\Queue\Jobs;

use App\Enums\QueuePriority;
use App\Queue\Jobs\Contracts\Autoschedule;
use App\Queue\Jobs\Contracts\Job;
use App\Services\FulcrumCore\FulcrumCore;

class Heartbeat extends Job implements Autoschedule
{
    public function execute(): void
    {
        FulcrumCore::getInstance()->updateAgentStatus( "Connected" );
    }

    public function getScheduleInterval(): int
    {
        return 60;
    }

    public function getSchedulePriority(): QueuePriority
    {
        return QueuePriority::Critical;
    }
}
