<?php

namespace App\Services\FulcrumCore\Actions;

use App\DataTransferObject\JobDTO;
use Exception;

trait GetPendingJobs
{
    /**
     * Get a list of pending jobs from the Fulcrum API
     *
     * @return array
     */
    public function getPendingJobs(): array
    {
        try {
            // Send a GET request to retrieve pending jobs
            $response = $this->sendRequest( 'GET', '/api/v1/jobs/pending' );

            // Check if the status code is 200 (OK)
            if ( $response->getStatusCode() !== 200 ) {
                throw new Exception( "Unexpected status code: " . $response->getStatusCode() );
            }

            // Get the response body as a string
            $responseBody = $response->getBody()->getContents();

            // Decode the JSON response into an array of job data
            $jobsData = json_decode( $responseBody, true );

            // Check if JSON decoding was successful
            if ( $jobsData === null && json_last_error() !== JSON_ERROR_NONE ) {
                throw new Exception( "JSON decode error: " . json_last_error_msg() );
            }

            // Convert each job data array into a JobDTO object
            $jobs = [];
            foreach ( $jobsData as $jobData ) {
                $jobs[] = new JobDTO( $jobData );
            }

            return $jobs;
        } catch ( Exception $e ) {
            // Wrap the error with context about what operation failed
            throw new Exception( "Failed to get pending jobs: " . $e->getMessage() );
        }
    }
}
