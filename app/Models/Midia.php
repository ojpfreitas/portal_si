<?php

namespace App\Models;

use App\Core\Model;
use PDO;

/**
 * Arquivos enviados (tabela media). O tipo do arquivo vem da tabela mime_types.
 */
class Midia extends Model
{
    /** Tipos de imagem aceitos como capa (image/jpeg, image/png, image/webp) */
    public function tiposDeImagem(): array
    {
        $stmt = $this->db->query(
            "SELECT id, name, extension FROM mime_types WHERE name LIKE 'image/%' ORDER BY id"
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Registra um arquivo já salvo no disco e devolve o id criado */
    public function criar(string $nomeArquivo, string $caminho, int $mimeTypeId, int $tamanho, int $enviadoPor): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO media (file_name, path, mime_type_id, size_bytes, uploaded_by)
             VALUES (:file_name, :path, :mime_type_id, :size_bytes, :uploaded_by)
             RETURNING id'
        );
        $stmt->execute([
            ':file_name'    => $nomeArquivo,
            ':path'         => $caminho,
            ':mime_type_id' => $mimeTypeId,
            ':size_bytes'   => $tamanho,
            ':uploaded_by'  => $enviadoPor,
        ]);

        return (int) $stmt->fetchColumn();
    }

    /** Marca a mídia como excluída; o arquivo continua no disco */
    public function marcarExcluida(int $id): void
    {
        $stmt = $this->db->prepare(
            'UPDATE media SET status = FALSE, deleted_at = CURRENT_TIMESTAMP
             WHERE id = :id AND status = TRUE
               AND NOT EXISTS (SELECT 1 FROM publications WHERE media_id = :id_uso)'
        );
        $stmt->execute([':id' => $id, ':id_uso' => $id]);
    }
}
