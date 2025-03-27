<?php

namespace App\Repositories\Contracts;

use PDO;

abstract class Repository
{
    protected PDO $pdo;
    protected string $tableName;

    /**
     * Check if a record exists using the primary key
     *
     * @param int $ID primary key
     * @param string $column column name
     * @return bool
     */
    public function exists( int $ID, string $column = "id" ): bool
    {
        $stmt = $this->pdo->prepare( "
            SELECT EXISTS ( SELECT 1 FROM {$this->tableName} WHERE {$column} = :id )
        " );

        $stmt->execute( ['id' => $ID] );

        return (bool) $stmt->fetchColumn();
    }

    /**
     * Check if a record does not exist using the primary key
     *
     * @param int $ID primary key
     * @param string $column column name
     * @return bool
     */
    public function doesntExist( int $ID, string $column = "id" ): bool
    {
        return !$this->exists( $ID, $column );
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
