<?php

namespace App;

class Logger
{
    /**
     * Available log levels
     */
    public const LEVEL_DEBUG = 'DEBUG';
    public const LEVEL_INFO = 'INFO';
    public const LEVEL_WARNING = 'WARNING';
    public const LEVEL_ERROR = 'ERROR';
    public const LEVEL_CRITICAL = 'CRITICAL';

    /**
     * Log a message to Docker container logs
     *
     * @param string $message Message to log
     * @param string $level Log level (default: INFO)
     * @param array $context Additional context data for the log entry
     */
    public static function log(
        string $message,
        string $level = self::LEVEL_INFO,
        array  $context = []
    ): void {
        $timestamp = date( 'Y-m-d H:i:s' );

        // Format context as JSON if provided
        $contextString = empty( $context ) ? '' : ' ' . json_encode( $context );

        // Format log message for better readability in Docker logs
        $logMessage = "[$timestamp] [$level] $message$contextString" . PHP_EOL;

        // Write to stdout (this will be captured by Docker logging)
        fwrite( STDOUT, $logMessage );
    }

    /**
     * Log a debug message
     */
    public static function debug( string $message, array $context = [] ): void
    {
        self::log( $message, self::LEVEL_DEBUG, $context );
    }

    /**
     * Log an info message
     */
    public static function info( string $message, array $context = [] ): void
    {
        self::log( $message, self::LEVEL_INFO, $context );
    }

    /**
     * Log a warning message
     */
    public static function warning( string $message, array $context = [] ): void
    {
        self::log( $message, self::LEVEL_WARNING, $context );
    }

    /**
     * Log an error message
     */
    public static function error( string $message, array $context = [] ): void
    {
        self::log( $message, self::LEVEL_ERROR, $context );
    }

    /**
     * Log a critical message
     */
    public static function critical( string $message, array $context = [] ): void
    {
        self::log( $message, self::LEVEL_CRITICAL, $context );
    }
}
