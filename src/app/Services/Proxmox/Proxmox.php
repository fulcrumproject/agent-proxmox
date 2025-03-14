<?php

namespace App\Services\Proxmox;

use App\Config;
use App\DataTransferObject\PropertiesDTO;
use App\DataTransferObject\ProxmoxMetricsDTO;
use App\Enums\ProxmoxVmStatus;
use App\Services\Proxmox\Exceptions\ProxmoxException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class Proxmox
{
    private static ?self $instance = null;

    private Client $client;
    private array $options;
    private string $node;

    private function __construct()
    {
        $this->node = Config::get( 'PROXMOX_NODE' );

        $this->client = new Client( [
            'base_uri' => Config::get( 'PROXMOX_BASE_URI' ),
            'verify' => false,
            'curl' => [
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_SSL_VERIFYPEER => 0,
                CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2,
            ],
        ] );

        $this->options = [
            'http_errors' => true,
            'allow_redirects' => true,
            'timeout' => 30,
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => sprintf(
                    "PVEAPIToken=%s!%s=%s",
                    Config::get( 'PROXMOX_USER' ),
                    Config::get( 'PROXMOX_TOKEN' ),
                    Config::get( 'PROXMOX_SECRET' )
                ),
            ],
        ];
    }

    public static function getInstance(): self
    {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function request( $method, $endpoint, $params = [] ): array
    {
        $options = $this->options;

        if ( !empty( $params ) ) {
            $options['json'] = $params;
            $options["headers"]["Content-Type"] = "application/json";
        }

        try {
            $response = $this->client->request(
                $method,
                $endpoint,
                $options
            );

            $result = json_decode( $response->getBody(), true );

            if ( isset( $result['success'] ) && $result['success'] === false ) {
                throw new ProxmoxException(
                    $result['errors'] ?? $result['message'] ?? 'Unknown Proxmox error',
                    $response->getStatusCode()
                );
            }

            return $result;

        } catch ( GuzzleException $e ) {
            throw new ProxmoxException(
                'Proxmox API request failed: ' . $e->getMessage(),
                $e->getCode() ?: 500,
                $e
            );
        }
    }

    public function createVm( int $id, PropertiesDTO $properties ): ?string
    {
        $res = $this->request( 'POST', "/api2/json/nodes/{$this->node}/qemu", [
            'vmid' => $id,
            'cores' => $properties->cpu,
            'memory' => $properties->memory,
        ] );

        // Return the task ID
        return $res["data"];
    }

    public function deleteVm( int $id ): ?string
    {
        $res = $this->request( 'DELETE', "/api2/json/nodes/{$this->node}/qemu/$id" );

        // Return the task ID
        return $res["data"];
    }

    public function updateVmConfig( int $id, array $data ): ?string
    {
        $res = $this->request( 'POST', "/api2/json/nodes/{$this->node}/qemu/$id/config", $data );

        // Return the task ID
        return $res["data"];
    }

    public function setVmStatus( int $id, ProxmoxVmStatus $status ): ?string
    {
        $res = $this->request( 'POST', "/api2/extjs/nodes/{$this->node}/qemu/$id/status/{$status->value}" );

        // Return the task ID
        return $res["data"];
    }

    /**
     * Get the status of a VM
     *
     * @param int $id
     */
    public function getVmStatus( int $id ): ?string
    {
        $res = $this->request( 'GET', "/api2/extjs/nodes/{$this->node}/qemu/$id/status/current" );

        return $res["data"]["status"];
    }

    /**
     * Get metrics for all VMs
     *
     * @return ProxmoxMetricsDTO[]
     */
    public function getMetrics(): array
    {
        $response = $this->request( 'GET', "/api2/extjs/cluster/resources?type=vm" );

        return array_map( fn( array $metrics ) => ProxmoxMetricsDTO::fromArray( $metrics ), $response["data"] );
    }

    /**
     * Create a full clone of a VM
     *
     * @param int $sourceId The ID of the source VM
     * @param int $targetId The ID of the new VM
     * @return string|null Task ID
     */
    public function cloneVm( int $sourceId, int $targetId ): ?string
    {
        $res = $this->request( 'POST', "/api2/json/nodes/{$this->node}/qemu/{$sourceId}/clone", [
            'newid' => $targetId,
            'full' => 1,
            'storage' => Config::get( 'PROXMOX_STORAGE' ),
        ] );

        return $res["data"];
    }

    /**
     * Get task status.
     * Usefull to check if a specific operation (create, delete, start, stop) has completed.
     *
     * @param string $taskId
     * @return bool
     */
    public function checkTaskHasCompleted( string $taskId ): bool
    {
        $res = $this->request( 'GET', "/api2/json/nodes/{$this->node}/tasks/{$taskId}/status" );

        if ( $res["data"]["status"] !== "stopped" ) {
            return false;
        }

        if ( $res["data"]["exitstatus"] !== 'OK' ) {
            throw new ProxmoxException(
                'Task failed: ' . $taskId
            );
        }

        return true;
    }
}
