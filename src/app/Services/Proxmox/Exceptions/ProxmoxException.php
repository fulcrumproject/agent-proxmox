<?php

namespace App\Services\Proxmox\Exceptions;

use Exception;

class ProxmoxException extends Exception
{
    public function __construct( string $message = "", int $code = 0,  ? \Throwable $previous = null )
    {
        parent::__construct( $message, $code, $previous );
    }
}
