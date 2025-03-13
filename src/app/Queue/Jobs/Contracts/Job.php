<?php

namespace App\Queue\Jobs\Contracts;

abstract class Job
{
    public function __construct()
    {
        // This is a no-op constructor
    }

    abstract public function execute(): void;
}
