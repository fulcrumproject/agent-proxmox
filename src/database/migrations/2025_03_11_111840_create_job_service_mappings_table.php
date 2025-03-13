<?php

use App\Database\Migration;

return new class extends Migration
{
    public function up()
    {
        $this->pdo->exec( "
            CREATE TABLE IF NOT EXISTS job_service_mappings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                job_service_id TEXT NOT NULL,
                vmid INTEGER NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(job_service_id, vmid)
            )
        " );

        $this->pdo->exec( "
            CREATE INDEX IF NOT EXISTS idx_job_service_mappings_service
            ON job_service_mappings(job_service_id)
        " );

        $this->pdo->exec( "
            CREATE INDEX IF NOT EXISTS idx_job_service_mappings_vm
            ON job_service_mappings(vmid)
        " );
    }

    public function down()
    {
        $this->pdo->query( "DROP TABLE IF EXISTS job_service_mappings" );
    }
};
