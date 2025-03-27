<?php

namespace App\Repositories;

use App\Database;
use App\Enums\QueuePriority;
use App\Repositories\Contracts\Repository;
use Exception;
use PDO;

class QueueRepository extends Repository
{
    protected string $tableName = "jobs";

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
     * Add a job to the queue
     *
     * @param mixed $job The job to add to the queue
     * @param QueuePriority $priority The priority of the job
     * @param int|null $delaySeconds The number of seconds to delay the job
     * @return int The ID of the job
     */
    public function add( $job, QueuePriority $priority, ?int $delaySeconds = null ): int
    {
        $availableAt = $delaySeconds ? "datetime('now', '+{$delaySeconds} seconds')" : "datetime('now')";

        $stmt = $this->pdo->prepare( "
            INSERT INTO {$this->tableName} (payload, priority, status, available_at, created_at)
            VALUES (:payload, :priority, 'pending', {$availableAt}, datetime('now'))
        " );

        $jobData = [
            'class' => get_class( $job ),
            'serialized' => serialize( $job ),
        ];

        $stmt->execute( [
            'payload' => json_encode( $jobData ),
            'priority' => max( 0, $priority->value ),
        ] );

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Find the next available job and reserve it for processing
     *
     * @return array|null The job data or null if no job is available
     */
    public function findAndReserveNextAvailableJob(): ?array
    {
        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare( "
                SELECT id, payload
                FROM {$this->tableName}
                WHERE status = 'pending'
                AND available_at <= datetime('now')
                ORDER BY priority DESC, created_at ASC
                LIMIT 1
            " );

            $stmt->execute();
            $job = $stmt->fetch();

            if ( $job ) {
                $updateStmt = $this->pdo->prepare( "
                    UPDATE {$this->tableName} SET status = 'processing', started_at = datetime('now') WHERE id = :id
                " );
                $updateStmt->execute( ['id' => $job['id']] );
            }

            $this->pdo->commit();
            return $job ?: null;
        } catch ( Exception $e ) {
            if ( $this->pdo->inTransaction() ) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Mark a job as completed
     *
     * @param int $jobId
     */
    public function markJobCompleted( int $jobId ): void
    {
        $stmt = $this->pdo->prepare( "
            UPDATE {$this->tableName} SET status = 'completed', completed_at = datetime('now') WHERE id = :id
        " );

        $stmt->execute( ['id' => $jobId] );
    }

    /**
     * Mark a job as failed
     *
     * @param int $jobId
     * @param string $error
     */
    public function markJobFailed( int $jobId, string $error ): void
    {
        $stmt = $this->pdo->prepare( "
            UPDATE {$this->tableName} SET status = 'failed', error = :error, completed_at = datetime('now') WHERE id = :id
        " );

        $stmt->execute( ['id' => $jobId, 'error' => $error] );
    }

    /**
     * Check if a job exists and is pending or processing.
     *
     * @param string $jobClass The class name of the job to check
     * @return bool
     */
    public function jobExists( string $jobClass ): bool
    {
        $className = addslashes( $jobClass );

        $stmt = $this->pdo->prepare( "
            SELECT EXISTS (
                SELECT 1
                FROM {$this->tableName}
                WHERE payload LIKE :className
                AND (status = 'pending' OR status = 'processing')
            ) AS job_exists
        " );

        $stmt->execute( ['className' => "%{$className}%"] );
        $result = $stmt->fetch( PDO::FETCH_ASSOC );

        return $result['job_exists'] == '1' ? true : false;
    }

    /**
     * Delete completed jobs older than 30 days
     */
    public function deleteOldCompletedJobs(): void
    {
        $stmt = $this->pdo->prepare( "
            DELETE FROM {$this->tableName}
            WHERE status = 'completed'
            AND completed_at <= datetime('now', '-30 days')
        " );

        $stmt->execute();
    }
}
