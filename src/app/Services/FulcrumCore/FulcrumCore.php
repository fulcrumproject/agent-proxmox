<?php

namespace App\Services\FulcrumCore;

use App\Config;
use App\Services\FulcrumCore\Actions\AddMetric;
use App\Services\FulcrumCore\Actions\ClaimJob;
use App\Services\FulcrumCore\Actions\CompleteJob;
use App\Services\FulcrumCore\Actions\FailJob;
use App\Services\FulcrumCore\Actions\GetPendingJobs;
use App\Services\FulcrumCore\Actions\UpdateAgentStatus;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Response;

class FulcrumCore
{
    use GetPendingJobs, ClaimJob, CompleteJob, FailJob, UpdateAgentStatus, AddMetric;

    private static ?self $instance = null;

    private Client $client;
    private string $token;

    private function __construct()
    {
        $this->token = Config::get( "FULCRUM_CORE_API_TOKEN" );

        $this->client = new Client( [
            'base_uri' => rtrim( Config::get( "FULCRUM_CORE_API_URL" ), '/' ),
        ] );
    }

    public static function getInstance(): self
    {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Sends an HTTP request to the Fulcrum API
     *
     * @param string $method The HTTP method to use (GET, POST, PUT, etc.)
     * @param string $endpoint The API endpoint to call
     * @param array $data The data to send in the request body
     * @return Response The Guzzle response object
     * @throws Exception If the request fails for any reason
     */
    private function sendRequest( string $method, string $endpoint, array $data = [] ): Response
    {
        try {
            $options = [
                'headers' => [
                    'Authorization' => "Bearer " . $this->token,
                    'Content-Type' => 'application/json',
                ],
            ];

            // Add JSON body data if provided
            if ( !empty( $data ) ) {
                // This handles the JSON marshalling step from the Go code
                $options['json'] = $data;
            }

            // Send the request and return the response
            return $this->client->request( $method, $endpoint, $options );

        } catch ( ServerException $e ) {
            // Server errors (5xx responses)
            $response = $e->getResponse();
            throw new Exception( "Server error: " . $response->getStatusCode() . " - " . $response->getBody() );

        } catch ( ClientException $e ) {
            // Client errors (4xx responses)
            $response = $e->getResponse();
            throw new Exception( "Client error: " . $response->getStatusCode() . " - " . $response->getBody() );

        } catch ( RequestException $e ) {
            // Network errors or other request problems
            if ( $e->hasResponse() ) {
                $response = $e->getResponse();
                throw new Exception( "Request failed: " . $response->getStatusCode() . " - " . $response->getBody() );
            }
            throw new Exception( "Request failed: " . $e->getMessage() );

        } catch ( Exception $e ) {
            // Any other exceptions
            throw new Exception( "Request error: " . $e->getMessage() );
        }
    }
}
