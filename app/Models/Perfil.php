<?php

namespace App\Models;

use App\Core\Model;
use PDO;

/**
 * Perfis de acesso (tabela user_profile).
 */
class Perfil extends Model
{
    /** Perfis ativos, para os campos de seleção */
    public function ativos(): array
    {
        $stmt = $this->db->query(
            'SELECT id, name, description FROM user_profile WHERE status = TRUE ORDER BY id'
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
