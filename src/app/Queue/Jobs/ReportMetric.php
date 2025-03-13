<?php

namespace App\Queue\Jobs;

use App\Enums\QueuePriority;
use App\Queue\Jobs\Contracts\Autoschedule;
use App\Queue\Jobs\Contracts\Job;
use App\Services\FulcrumCore\FulcrumCore;
use App\Services\Proxmox\Exceptions\ProxmoxException;
use App\Services\Proxmox\Proxmox;
use Throwable;

class ReportMetric extends Job implements Autoschedule
{
    public function execute(): void
    {
        $proxmox = Proxmox::getInstance();
        $fulcrum = FulcrumCore::getInstance();

        try {
            $results = $proxmox->getMetrics();

            foreach ( $results as $res ) {
                $fulcrum->addMetric( $res->externalId, "vm.memory.usage", $res->memory );
                $fulcrum->addMetric( $res->externalId, "vm.cpu.usage", $res->cpu );
            }
        } catch ( ProxmoxException | Throwable $e ) {
            return;
        }
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
