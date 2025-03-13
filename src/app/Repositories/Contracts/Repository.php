<?php

namespace App\Repositories\Contracts;

use App\Database;
use Exception;
use PDO;

abstract class Repository
{
    protected PDO $pdo;
    protected string $tableName;

    /** @var static|null Singleton instance */
    private static ?Repository $instance = null;

    private function __construct()
    {
        $this->pdo = Database::getInstance()->getPdo();

        if ( !$this->tableName ) {
            throw new Exception( "Table name must be defined in the child repository." );
        }
    }

    /**
     * Get the singleton instance of the Repository
     *
     * @return static
     */
    public static function getInstance(): static
    {
        if ( self::$instance === null ) {
            self::$instance = new static();
        }

        return self::$instance;
    }

    /**
     * Delete a record using the primary key
     *
     * @param int $ID primary key
     * @return bool
     */
    public function delete( int $ID ): bool
    {
        $stmt = $this->pdo->prepare( "
            DELETE FROM {$this->tableName}
            WHERE id = :id
        " );

        $stmt->execute( ['id' => $ID] );

        return $stmt->rowCount() > 0;
    }

    /**
     * Truncate the jobs table
     */
    public function truncate(): void
    {
        $this->pdo->exec( "DELETE FROM {$this->tableName}" );
        $this->pdo->exec( "DELETE FROM sqlite_sequence WHERE name='{$this->tableName}'" );
    }
}
