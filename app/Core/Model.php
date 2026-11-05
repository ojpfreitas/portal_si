<?php

namespace App\Core;

use PDO;

/**
 * Base dos models: guarda a conexão com o banco.
 */
abstract class Model
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }
}
