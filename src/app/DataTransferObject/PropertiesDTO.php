<?php

namespace App\DataTransferObject;

class PropertiesDTO
{
    // TODO: Make it dynamic
    public int $disk = 16;

    public function __construct(
        public int $cpu = 0,
        public int $memory = 0
    ) {}
}
