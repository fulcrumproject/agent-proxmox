<?php

namespace App\Queue\Jobs;

use App\Config;
use App\DataTransferObject\JobDTO;
use App\Enums\JobServiceAction;
use App\Enums\ProxmoxVmStatus;
use App\Models\JobServiceMapping;
use App\Queue\Jobs\Contracts\Job;
use App\Repositories\JobServiceMappingsRepository;
use App\Services\FulcrumCore\FulcrumCore;
use App\Services\Proxmox\Exceptions\ProxmoxException;
use App\Services\Proxmox\Proxmox;
use Throwable;

class ProxmoxControl extends Job
{
    private FulcrumCore $fulcrum;
    private Proxmox $proxmox;
    private JobServiceMappingsRepository $repository;
    private JobServiceMapping|null $vm;

    public function __construct( protected JobDTO $job ) {}

    public function execute(): void
    {
        $this->fulcrum = FulcrumCore::getInstance();
        $this->proxmox = Proxmox::getInstance();
        $this->repository = JobServiceMappingsRepository::getInstance();

        $this->fulcrum->claimJob( $this->job->id );

        try {
            $this->vm = $this->repository->findByServiceID( $this->job->service->id );

            if ( is_null( $this->vm ) && $this->job->action !== JobServiceAction::ServiceCreate->value ) {
                throw new ProxmoxException( 'VM not found' );
            }

            match ( $this->job->action ) {
                JobServiceAction::ServiceCreate->value => $this->cloneVm(),
                JobServiceAction::ServiceDelete->value => $this->deleteVm(),
                JobServiceAction::ServiceUpdate->value => $this->updateVm(),
                JobServiceAction::ServiceStart->value => $this->updateStatus( ProxmoxVmStatus::Start ),
                JobServiceAction::ServiceStop->value => $this->updateStatus( ProxmoxVmStatus::Stop ),
                default => throw new ProxmoxException( 'Invalid action' ),
            };
        } catch ( ProxmoxException | Throwable $e ) {
            // Mark job as failed
            $this->fulcrum->failJob( $this->job->id );
            return;
        }

        // Mark job as completed
        $this->fulcrum->completeJob( $this->job->id, $this->vm->id );
    }

    /**
     * Create a VM
     */
    private function createVm(): void
    {
        // Getting the next available VM ID
        $nextId = $this->repository->getNextVmID();

        // Create VM
        $taskID = $this->proxmox->createVm( $nextId, $this->job->service->properties );

        if ( !$taskID ) {
            throw new ProxmoxException( 'Failed to create VM' );
        }

        // Verify if the task has completed successfully
        $this->waitForTaskCompletion( $taskID );

        // Store on local DB
        $this->vm = $this->repository->insert( $this->job->service->id, $nextId );
    }

    /**
     * Clone a VM
     */
    private function cloneVm(): void
    {
        // Getting the next available VM ID
        $nextId = $this->repository->getNextVmID();

        // Clone VM
        $taskID = $this->proxmox->cloneVm( Config::get( "PROXMOX_TEMPLATE_ID" ), $nextId );

        if ( !$taskID ) {
            throw new ProxmoxException( 'Failed to clone VM' );
        }

        $this->waitForTaskCompletion( $taskID );

        // Update VM resources
        $configParams = [
            'cores' => $this->job->service->properties->cpu,
            'memory' => $this->job->service->properties->memory,
        ];

        // Add storage resize if specified
        if ( $this->job->service->properties->storage ) {
            $configParams['scsi0'] = sprintf(
                '%s:%d',
                Config::get( "PROXMOX_STORAGE" ),
                $this->job->service->properties->storage
            );
        }

        // Update VM
        $taskID = $this->proxmox->updateVmConfig( $nextId, $configParams );

        if ( !$taskID ) {
            throw new ProxmoxException( 'Failed to update VM' );
        }

        // Verify if the task has completed successfully
        $this->waitForTaskCompletion( $taskID );

        // Store on local DB
        $this->vm = $this->repository->insert( $this->job->service->id, $nextId );
    }

    /**
     * Delete a VM
     */
    private function deleteVm(): void
    {
        $previousStatus = $this->proxmox->getVmStatus( $this->vm->vmid );

        // Before deleting the VM must be stopped
        if ( $previousStatus === "running" ) {
            $this->updateStatus( ProxmoxVmStatus::Stop );
        }

        // Delete VM
        $taskID = $this->proxmox->deleteVm( $this->vm->vmid );

        if ( !$taskID ) {
            throw new ProxmoxException( 'Failed to delete VM' );
        }

        // Verify if the task has completed successfully
        $this->waitForTaskCompletion( $taskID );

        // Remove from local DB
        $this->repository->delete( $this->vm->id );
    }

    /**
     * Update a VM
     */
    private function updateVm(): void
    {
        $previousStatus = $this->proxmox->getVmStatus( $this->vm->vmid );

        // Before updating the VM must be stopped
        if ( $previousStatus === "running" ) {
            $this->updateStatus( ProxmoxVmStatus::Stop );
        }

        // Update VM
        $taskID = $this->proxmox->updateVmConfig(
            $this->vm->vmid,
            [
                "cores" => $this->job->service->properties->cpu,
                "memory" => $this->job->service->properties->memory,
            ]
        );

        if ( !$taskID ) {
            throw new ProxmoxException( 'Failed to update VM' );
        }

        // Verify if the task has completed successfully
        $this->waitForTaskCompletion( $taskID );

        if ( $previousStatus === "running" ) {
            $this->updateStatus( ProxmoxVmStatus::Start );
        }
    }

    /**
     * Update VM status
     */
    private function updateStatus( ProxmoxVmStatus $status ): void
    {
        $taskID = $this->proxmox->setVmStatus( $this->vm->vmid, $status );

        if ( !$taskID ) {
            throw new ProxmoxException( 'Failed to update VM status' );
        }

        // Verify if the task has completed successfully
        $this->waitForTaskCompletion( $taskID );
    }

    /**
     * Wait for task completion with exponential backoff
     *
     * @param string $taskId Task ID to monitor
     * @throws ProxmoxException If task fails or times out
     */
    private function waitForTaskCompletion( string $taskId ): void
    {
        $maxWaitTime = 60;
        $initialDelay = 1;
        $startTime = time();
        $attempt = 1;

        while ( time() - $startTime < $maxWaitTime ) {
            if ( $this->proxmox->checkTaskHasCompleted( $taskId ) ) {
                return;
            }

            // Calculate next delay with exponential backoff
            $currentDelay = min( $initialDelay * pow( 2, $attempt ), 8 ); // Max 8 seconds between checks

            if ( time() + $currentDelay - $startTime >= $maxWaitTime ) {
                break; // Would exceed max wait time
            }

            sleep( $currentDelay );
            $attempt++;
        }

        throw new ProxmoxException(
            sprintf( "Task %s timed out (%d attempts)", $taskId, $attempt )
        );
    }
}
