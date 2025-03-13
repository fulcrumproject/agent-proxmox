<?php

namespace App\Services\FulcrumCore\Actions;

use Exception;
use GuzzleHttp\Exception\RequestException;

trait ClaimJob
{
    /**
     * Claims a job in Fulcrum Core for this agent
     *
     * @param string $jobId The ID of the job to claim
     * @return bool True if the job was successfully claimed
     * @throws Exception If the request fails
     */
    public function claimJob( string $jobId ): bool
    {
        try {
            // Send a POST request to claim the job
            $this->sendRequest( 'POST', '/api/v1/jobs/' . $jobId . '/claim' );

            return true;
        } catch ( RequestException $e ) {
            // Wrap the error with context about what operation failed
            throw new Exception( "Failed to claim job: " . $e->getMessage() );
        }
    }
}
