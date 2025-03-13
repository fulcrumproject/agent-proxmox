<?php

namespace App;

use App\Config;
use PDO;
use PDOException;

class Database
{
    private static $instance = null;
    private PDO $pdo;

    private function __construct()
    {
        $dbPath = Config::get( 'DB_PATH' );

        try {
            $this->pdo = new PDO( 'sqlite:' . $dbPath );
            $this->pdo->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
            $this->pdo->setAttribute( PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC );
            $this->pdo->exec( 'PRAGMA foreign_keys = ON' );
        } catch ( PDOException $e ) {
            die( "Database connection failed: " . $e->getMessage() );
        }
    }

    public static function getInstance(): self
    {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    public function query( string $sql, array $params = [] ): \PDOStatement
    {
        $statement = $this->pdo->prepare( $sql );
        $statement->execute( $params );
        return $statement;
    }
}
