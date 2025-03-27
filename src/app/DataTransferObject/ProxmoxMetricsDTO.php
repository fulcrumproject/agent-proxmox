<?php

namespace App\DataTransferObject;

class ProxmoxMetricsDTO
{
    public function __construct(
        public readonly string $vmid,
        public readonly float  $memory,
        public readonly float  $cpu,
    ) {}

    public static function fromArray( array $data ): self
    {
        return new self(
            vmid: $data['vmid'],
            memory: $data["mem"] * 100 / $data["maxmem"],
            cpu: $data['cpu']
        );
    }
}
