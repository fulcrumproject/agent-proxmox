<?php

namespace App;

use Dotenv\Dotenv;

class Config
{
    private static ?self $instance = null;
    private array $config = [];

    private function __construct()
    {
        $this->loadEnv();
    }

    private static function getInstance(): self
    {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function loadEnv(): void
    {
        $rootDir = dirname( __DIR__, 1 );

        if ( file_exists( $rootDir . '/.env' ) ) {
            $dotenv = Dotenv::createImmutable( $rootDir );
            $dotenv->load();

            // Load all environment variables into config
            foreach ( $_ENV as $key => $value ) {
                $this->config[$key] = $value;
            }
        }
    }

    public static function get( string $key, $default = null )
    {
        return self::getInstance()->config[$key] ?? $default;
    }
}
