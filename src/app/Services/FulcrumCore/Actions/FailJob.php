<?php

namespace App\Services\FulcrumCore\Actions;

use Exception;
use GuzzleHttp\Exception\RequestException;

trait FailJob
{
    /**
     * Fail a job in Fulcrum Core for this agent
     *
     * @param string $jobId The ID of the job to complete
     * @return bool True if the job was successfully completed
     * @throws Exception If the request fails
     */
    public function failJob( string $jobId ): bool
    {
        try {
            // Send a POST request to fail the job
            $this->sendRequest( 'POST', '/api/v1/jobs/' . $jobId . '/fail' );

            return true;
        } catch ( RequestException $e ) {
            // Wrap the error with context about what operation failed
            throw new Exception( "Failed to fail job: " . $e->getMessage() );
        }
    }
}
