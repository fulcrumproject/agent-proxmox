<?php

use App\Database\Migration;

return new class extends Migration
{
    public function up()
    {
        $this->pdo->exec( "
            CREATE TABLE IF NOT EXISTS jobs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                payload TEXT NOT NULL,
                priority UNSIGNED INTEGER DEFAULT 0,
                status TEXT DEFAULT 'pending',
                error TEXT NULL,
                available_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                started_at TIMESTAMP NULL,
                completed_at TIMESTAMP NULL
            )
        " );

        $this->pdo->exec( "
            CREATE INDEX IF NOT EXISTS idx_jobs_status_available
            ON jobs(status, available_at)
        " );

        $this->pdo->exec( "
            CREATE INDEX IF NOT EXISTS idx_jobs_status_priority_created
            ON jobs(status, priority DESC, created_at)
        " );
    }

    public function down()
    {
        $this->pdo->query( "DROP TABLE IF EXISTS jobs" );
    }
};
