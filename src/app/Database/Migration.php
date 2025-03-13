<?php

namespace App\Database;

use App\Database;
use PDO;

abstract class Migration
{
    protected PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getPdo();
    }

    abstract public function up();

    abstract public function down();
}
