<?php

namespace App\Models;

use App\Core\Model;
use PDO;

/**
 * Seções das publicações (tabela publication_types).
 */
class Categoria extends Model
{
    public function ativas(): array
    {
        $stmt = $this->db->query(
            'SELECT id, name, slug, description, icon
             FROM publication_types
             WHERE active = TRUE
             ORDER BY order_position, name'
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Categoria ativa pelo endereço (slug), ou null se não existir */
    public function buscarPorSlug(string $slug): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, slug, description, icon
             FROM publication_types
             WHERE slug = :slug AND active = TRUE'
        );
        $stmt->execute([':slug' => $slug]);

        $registro = $stmt->fetch(PDO::FETCH_ASSOC);

        return $registro ?: null;
    }
}
