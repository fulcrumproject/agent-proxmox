<?php

namespace App\Queue\Jobs;

use App\Enums\QueuePriority;
use App\Logger;
use App\Queue\Jobs\Contracts\Autoschedule;
use App\Queue\Jobs\Contracts\Job;
use App\Repositories\JobServiceMappingsRepository;
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
        $jobServiceMappingsRepository = JobServiceMappingsRepository::getInstance();

        try {
            $results = $proxmox->getMetrics();

            foreach ( $results as $res ) {
                $map = $jobServiceMappingsRepository->findByVmID( $res->vmid );

                if ( $map === null ) {
                    continue;
                }

                $fulcrum->addMetric( $map->id, "vm.memory.usage", $res->memory );
                $fulcrum->addMetric( $map->id, "vm.cpu.usage", $res->cpu );
            }
        } catch ( ProxmoxException | Throwable $e ) {
            Logger::log( "Failed to report metrics: " . $e->getMessage() );

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
