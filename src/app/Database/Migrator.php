<?php

namespace App\Database;

use App\Database;

class Migrator
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function migrate(): void
    {
        $this->db->query( "
            CREATE TABLE IF NOT EXISTS migrations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                migration VARCHAR(255) NOT NULL,
                batch INTEGER NOT NULL,
                executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        " );

        // Get executed migrations
        $executedMigrations = $this->db->query( "SELECT migration FROM migrations" )->fetchAll( \PDO::FETCH_COLUMN );

        // Get migration files
        $migrationFiles = scandir( 'database/migrations' );

        if ( empty( $migrationFiles ) ) {
            $this->log( "No migrations found.\n" );
            return;
        }

        // Get the current batch number
        $batchResult = $this->db->query( "SELECT MAX(batch) as batch FROM migrations" )->fetch();
        $batch = $batchResult['batch'] ?? 0;
        $batch++;

        $executed = 0;

        foreach ( $migrationFiles as $file ) {
            $migrationName = basename( $file );

            if ( $file === '.' || $file === '..' || pathinfo( $file, PATHINFO_EXTENSION ) !== 'php' ) {
                continue;
            }

            if ( in_array( $migrationName, $executedMigrations ) ) {
                continue;
            }

            $executed++;

            $migration = require "database/migrations/" . $file;

            if ( !$migration instanceof Migration ) {
                continue;
            }

            $this->up( $migration, $migrationName, $batch );
        }

        if ( $executed === 0 ) {
            $this->log( "Nothing to migrate.\n" );
        }
    }

    public function rollback(): void
    {
        $batchResult = $this->db->query( "SELECT MAX(batch) as batch FROM migrations" )->fetch();
        $batch = $batchResult['batch'] ?? null;

        if ( $batch === null ) {
            $this->log( "No migrations to rollback.\n" );
            return;
        }

        $migrations = $this->db->query( "SELECT migration FROM migrations WHERE batch = ? ORDER BY id DESC", [$batch] )->fetchAll( \PDO::FETCH_COLUMN );

        foreach ( $migrations as $migrationName ) {
            $migration = require "database/migrations/" . $migrationName;
            if ( $migration instanceof Migration ) {
                $this->down( $migration, $migrationName );
            }
        }
    }

    public function refresh(): void
    {
        $migrations = $this->db->query( "SELECT migration FROM migrations ORDER BY batch DESC, id DESC" )->fetchAll( \PDO::FETCH_COLUMN );

        foreach ( $migrations as $migrationName ) {
            $migration = require "database/migrations/" . $migrationName;
            if ( $migration instanceof Migration ) {
                $this->down( $migration, $migrationName );
                $this->up( $migration, $migrationName, 1 );
            }
        }
    }

    private function up( Migration $migration, string $name, int $batch ): void
    {
        if ( !method_exists( $migration, 'up' ) ) {
            $this->log( "Skipping, missing `up` method for migration: $name\n" );
            return;
        }

        $this->log( "Migrating: $name\n" );

        $migration->up();

        $this->db->query(
            "INSERT INTO migrations (migration, batch) VALUES (?, ?)",
            [$name, $batch]
        );

        $this->log( "Migrated: $name\n" );
    }

    private function down( Migration $migration, string $name ): void
    {
        if ( !method_exists( $migration, 'down' ) ) {
            $this->log( "Skipping, missing `down` method for migration: $name\n" );
            return;
        }

        $this->log( "Rolling back: $name\n" );

        $migration->down();

        $this->db->query( "DELETE FROM migrations WHERE migration = ?", [$name] );

        $this->log( "Rolled back: $name\n" );
    }

    private function log( string $message ): void
    {
        echo $message;
    }
}
