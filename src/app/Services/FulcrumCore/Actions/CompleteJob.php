<?php

namespace App\Services\FulcrumCore\Actions;

use Exception;
use GuzzleHttp\Exception\RequestException;

trait CompleteJob
{
    /**
     * Completes a job in Fulcrum Core for this agent
     *
     * @param string $jobId The ID of the job to complete
     * @param int $externalId Our ID that Fulcrum Core stores related for the given service. We can later send metrics using externalId
     * @return bool True if the job was successfully completed
     * @throws Exception If the request fails
     */
    public function completeJob( string $jobId, int $externalId ): bool
    {
        try {
            // Send a POST request to complete the job
            $this->sendRequest( 'POST', '/api/v1/jobs/' . $jobId . '/complete', [
                'externalId' => $externalId,
            ] );

            return true;
        } catch ( RequestException $e ) {
            // Wrap the error with context about what operation failed
            throw new Exception( "Failed to complete job: " . $e->getMessage() );
        }
    }
}
