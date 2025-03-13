<?php

namespace App\Repositories;

use App\Models\JobServiceMapping;
use App\Repositories\Contracts\Repository;

class JobServiceMappingsRepository extends Repository
{
    protected string $tableName = "job_service_mappings";

    /**
     * Insert a new job service mapping
     *
     * @param string $serviceID The ID of the job service
     * @param int $vmID The ID of the VM
     * @return JobServiceMapping The ID of the new mapping
     * @throws \Exception
     */
    public function insert( string $serviceID, int $vmID ): JobServiceMapping
    {
        $stmt = $this->pdo->prepare( "
            INSERT INTO job_service_mappings (job_service_id, vmid)
            VALUES (:job_service_id, :vmid)
        " );

        $stmt->execute( ['job_service_id' => $serviceID, 'vmid' => $vmID] );

        $insertedID = $this->pdo->lastInsertId();

        if ( !$insertedID ) {
            throw new \Exception( "Failed to insert job service mapping" );
        }

        return $this->find( (int) $insertedID );
    }

    /**
     * Get the next available VM ID
     *
     * @return int
     */
    public function getNextVmID(): int
    {
        $stmt = $this->pdo->query( "
            SELECT COALESCE(MAX(vmid), 99) + 1 AS next_id
            FROM job_service_mappings
        " );

        return (int) $stmt->fetchColumn();
    }

    /**
     * Find a VM by service ID
     *
     * @param string $serviceID The ID of the job service
     * @return JobServiceMapping|null The VM or null if not found
     */
    public function findByServiceID( string $serviceID ): ?JobServiceMapping
    {
        $stmt = $this->pdo->prepare( "
            SELECT *
            FROM job_service_mappings
            WHERE job_service_id = :job_service_id
            LIMIT 1
        " );

        $stmt->execute( ['job_service_id' => $serviceID] );

        $result = $stmt->fetch();

        return $result !== false ? $this->castAsModel( $result ) : null;
    }

    /**
     * Find a VM ID by its primary ID
     *
     * @param int $vmID The ID of the VM
     * @return JobServiceMapping|null The VM or null if not found
     */
    public function find( int $id ): ?JobServiceMapping
    {
        $stmt = $this->pdo->prepare( "
            SELECT *
            FROM job_service_mappings
            WHERE id = :id
        " );

        $stmt->execute( ['id' => $id] );

        $result = $stmt->fetch();

        return $result !== false ? $this->castAsModel( $result ) : null;
    }

    /**
     * Find a VM by VM ID
     *
     * @param int $vmID The ID of the VM
     * @return JobServiceMapping|null The VM or null if not found
     */
    public function findByVmID( int $vmID ): ?JobServiceMapping
    {
        $stmt = $this->pdo->prepare( "
            SELECT *
            FROM job_service_mappings
            WHERE vmid = :vmid
            LIMIT 1
        " );

        $stmt->execute( ['vmid' => $vmID] );

        $result = $stmt->fetch();

        return $result !== false ? $this->castAsModel( $result ) : null;
    }

    /**
     * Cast an array to a DTO
     *
     * @param array $data The data to cast
     * @return JobServiceMapping
     */
    private function castAsModel( array $data ): JobServiceMapping
    {
        return new JobServiceMapping( $data );
    }
}
