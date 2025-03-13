<?php

namespace App\Models;

class JobServiceMapping
{
    public readonly int $id;
    public readonly string $job_service_id;
    public readonly int $vmid;

    public function __construct( array $data )
    {
        $this->id = $data['id'];
        $this->job_service_id = $data['job_service_id'];
        $this->vmid = $data['vmid'];
    }
}
