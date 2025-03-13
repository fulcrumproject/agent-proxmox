<?php

namespace App\Services\FulcrumCore\Actions;

use Exception;
use GuzzleHttp\Exception\RequestException;

trait AddMetric
{
    /**
     * Adds a metric to Fulcrum Core
     *
     * @param int $externalId The ID of the external resource
     * @param string $typename The type name of the metric
     * @param float $value The value of the metric
     * @return bool True if the metric was successfully added
     * @throws Exception If the request fails
     */
    public function addMetric( int $externalId, string $typename, float $value ): bool
    {
        try {
            // Send a POST request to add the metric
            $this->sendRequest( 'POST', '/api/v1/metric-entries', [
                'externalId' => $externalId,
                'resourceId' => time(),
                'typeName' => $typename,
                'value' => $value,
            ] );

            return true;
        } catch ( RequestException $e ) {
            // Wrap the error with context about what operation failed
            throw new Exception( "Failed to report metric " . $e->getMessage() );
        }
    }
}
