<?php

namespace App\Services\FulcrumCore\Actions;

use Exception;
use GuzzleHttp\Exception\RequestException;

trait UpdateAgentStatus
{
    /**
     * Updates the agent's status in Fulcrum Core
     *
     * @param string $status The new status to set for the agent
     * @return bool True if successful
     * @throws Exception If the request fails
     */
    public function updateAgentStatus( string $status ): bool
    {
        try {
            // Send the PUT request with the status data
            $this->sendRequest( 'PUT', '/api/v1/agents/me/status', ['state' => $status] );

            return true;
        } catch ( RequestException $e ) {
            // Wrap the error with context about what operation failed
            throw new Exception( "Failed to update agent status: " . $e->getMessage() );
        }
    }
}
