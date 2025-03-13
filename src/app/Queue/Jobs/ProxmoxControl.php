<?php

namespace App\Queue\Jobs;

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
                JobServiceAction::ServiceCreate->value => $this->createVm(),
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
        $this->fulcrum->completeJob( $this->job->id, $this->vm->vmid );
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
        $this->taskVerificationWithExponentialBackoff( $taskID );

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
        $this->taskVerificationWithExponentialBackoff( $taskID );

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
            $this->job->service->properties
        );

        if ( !$taskID ) {
            throw new ProxmoxException( 'Failed to update VM' );
        }

        // Verify if the task has completed successfully
        $this->taskVerificationWithExponentialBackoff( $taskID );

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
        $this->taskVerificationWithExponentialBackoff( $taskID );
    }

    /**
     * Verify if a task has completed successfully
     * Exponential backoff retry strategy. Max 3 attempts with an increased delay of one second for each interaction.
     *
     * Starting from 1s (operations are not instant).
     *
     * @param string $task
     * @throws ProxmoxException
     */
    private function taskVerificationWithExponentialBackoff( string $task ): void
    {
        for ( $attempt = 1; $attempt <= 3; $attempt++ ) {
            usleep( 1000 * 500 * $attempt ); // Sleep 1s, 2s, 3s

            if ( $this->proxmox->checkTaskHasCompleted( $task ) ) {
                return;
            }
        }

        throw new ProxmoxException( 'Exponential backoff retry limit exceeded—condition verification failed.' );
    }
}
