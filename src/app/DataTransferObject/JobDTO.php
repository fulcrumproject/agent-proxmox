<?php

namespace App\DataTransferObject;

class JobDTO
{
    public string $id;
    public string $action;
    public string $status;
    public ServiceDTO $service;

    /**
     * Create a new JobDTO from an associative array
     *
     * @param array $data The job data from the API
     */
    public function __construct( array $data = [] )
    {
        $this->id = $data['id'];
        $this->action = $data['action'];
        $this->status = $data['status'];

        $this->service = ServiceDTO::fromArray( $data["service"] );
    }
}
