<?php

use App\DataTransferObject\JobDTO;
use App\Queue\Jobs\ProxmoxControl;

require __DIR__ . '/vendor/autoload.php';

// $repo = JobServiceMappingsRepository::getInstance();
// $repo->truncate();

$job = new JobDTO( [
    "id" => "019580c2-0e7d-7bb9-90ce-0b2b71940593",
    "action" => "ServiceDelete",
    "state" => "Pending",
    "priority" => "1",
    "createdAt" => "2025-03-10T15:52:36Z",
    "updatedAt" => "2025-03-10T15:52:36Z",
    "service" => [
        "id" => "319580c2-0e79-7ee2-b47c-8e3b6ceeaa51",
        "agentId" => "019580c1-1314-7b03-b647-669840514cc7",
        "serviceTypeId" => "22222222-2222-2222-2222-222222222222",
        "groupId" => "019580c1-b933-7d95-ac72-7be984d9997b",
        "name" => "Test Job Service",
        "attributes" => [
            "environment" => [
                "test",
            ],
        ],
        "currentState" => "Creating",
        "targetState" => "Created",
        "targetProperties" => [
            "cpu" => "4",
            "memory" => "16",
        ],
        "createdAt" => "2025-03-10T15:52:36Z",
        "updatedAt" => "2025-03-10T15:52:36Z",
    ],
] );

$pc = new ProxmoxControl( $job );

$pc->execute();
