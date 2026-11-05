<?php

namespace App\Models;

use App\Core\Model;
use PDO;

/**
 * Publicações e notícias (tabela publications).
 */
class Conteudo extends Model
{
    /** Situações possíveis (Rascunho, Publicado, Arquivado) */
    public function statuses(): array
    {
        $stmt = $this->db->query('SELECT id, name FROM publication_status ORDER BY id');

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Filtros: busca (título), status (id) e categoria (id) */
    public function listar(array $filtros = []): array
    {
        $where  = [];
        $params = [];

        if (!empty($filtros['busca'])) {
            $where[] = 'p.title ILIKE :busca';
            $params[':busca'] = '%' . addcslashes($filtros['busca'], '%_\\') . '%';
        }

        if (!empty($filtros['status'])) {
            $where[] = 'p.status_id = :status';
            $params[':status'] = (int) $filtros['status'];
        }

        if (!empty($filtros['categoria'])) {
            $where[] = 'p.publication_type_id = :categoria';
            $params[':categoria'] = (int) $filtros['categoria'];
        }

        $sql = 'SELECT p.id, p.title, p.featured, p.published_at,
                       t.name AS type_name, s.name AS status_name, u.name AS author_name
                FROM publications p
                JOIN publication_types t ON t.id = p.publication_type_id
                JOIN publication_status s ON s.id = p.status_id
                JOIN users u ON u.id = p.author_id';

        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY p.id DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscar(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT p.id, p.title, p.summary, p.body, p.media_id, m.path AS cover_path,
                    p.youtube_link, p.publication_type_id, p.status_id,
                    p.featured, p.published_at
             FROM publications p
             LEFT JOIN media m ON m.id = p.media_id
             WHERE p.id = :id'
        );
        $stmt->execute([':id' => $id]);

        $registro = $stmt->fetch(PDO::FETCH_ASSOC);

        return $registro ?: null;
    }

    /**
     * Cria a publicação. $dados: title, summary, body, media_id (null sem capa), youtube_link,
     * publication_type_id, status_id e featured (bool).
     */
    public function criar(array $dados, int $autor): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO publications
                (title, slug, summary, body, media_id, youtube_link,
                 publication_type_id, author_id, status_id, featured, published_at)
             VALUES
                (:title, :slug, :summary, :body, :media_id, :youtube_link,
                 :type_id, :author_id, :status_id, :featured, :published_at)'
        );
        $stmt->execute([
            ':title'        => $dados['title'],
            ':slug'         => $this->slugUnico(gerar_slug($dados['title'])),
            ':summary'      => $dados['summary'] !== '' ? $dados['summary'] : null,
            ':body'         => $dados['body'],
            ':media_id'     => $dados['media_id'],
            ':youtube_link' => $dados['youtube_link'] !== '' ? $dados['youtube_link'] : null,
            ':type_id'      => (int) $dados['publication_type_id'],
            ':author_id'    => $autor,
            ':status_id'    => (int) $dados['status_id'],
            ':featured'     => $dados['featured'] ? 'true' : 'false',
            ':published_at' => $this->dataPublicacao((int) $dados['status_id'], null),
        ]);
    }

    /** O endereço (slug) não muda na edição, para não quebrar links já divulgados */
    public function atualizar(int $id, array $dados): void
    {
        $atual = $this->buscar($id);

        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare(
                'UPDATE publications
                 SET title = :title, summary = :summary, body = :body,
                     media_id = COALESCE(:media_id, media_id), youtube_link = :youtube_link,
                     publication_type_id = :type_id, status_id = :status_id,
                     featured = :featured, published_at = :published_at
                 WHERE id = :id'
            );
            $stmt->execute([
                ':title'        => $dados['title'],
                ':summary'      => $dados['summary'] !== '' ? $dados['summary'] : null,
                ':body'         => $dados['body'],
                ':media_id'     => $dados['media_id'],
                ':youtube_link' => $dados['youtube_link'] !== '' ? $dados['youtube_link'] : null,
                ':type_id'      => (int) $dados['publication_type_id'],
                ':status_id'    => (int) $dados['status_id'],
                ':featured'     => $dados['featured'] ? 'true' : 'false',
                ':published_at' => $this->dataPublicacao((int) $dados['status_id'], $atual['published_at'] ?? null),
                ':id'           => $id,
            ]);

            // Capa nova: marca a capa antiga como excluída
            $anterior = $atual['media_id'] ?? null;

            if ($dados['media_id'] !== null && $anterior !== null && (int) $anterior !== (int) $dados['media_id']) {
                (new Midia())->marcarExcluida((int) $anterior);
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /** Apaga a publicação e marca a capa como excluída */
    public function excluir(int $id): void
    {
        $atual = $this->buscar($id);

        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare('DELETE FROM publications WHERE id = :id');
            $stmt->execute([':id' => $id]);

            if (($atual['media_id'] ?? null) !== null) {
                (new Midia())->marcarExcluida((int) $atual['media_id']);
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /** Publicações já publicadas, com destaques primeiro (para o site público) */
    public function publicadas(int $limite = 9): array
    {
        $stmt = $this->db->query(
            "SELECT p.id, p.title, p.summary, p.featured, p.published_at, t.name AS type_name
             FROM publications p
             JOIN publication_types t ON t.id = p.publication_type_id
             JOIN publication_status s ON s.id = p.status_id
             WHERE s.name = 'Publicado'
             ORDER BY p.featured DESC, p.published_at DESC NULLS LAST
             LIMIT " . (int) $limite
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Publicações já publicadas de uma categoria (tela pública da categoria) */
    public function publicadasPorCategoria(int $categoriaId): array
    {
        $stmt = $this->db->prepare(
            "SELECT p.id, p.title, p.summary, p.featured, p.published_at, t.name AS type_name
             FROM publications p
             JOIN publication_types t ON t.id = p.publication_type_id
             JOIN publication_status s ON s.id = p.status_id
             WHERE s.name = 'Publicado' AND p.publication_type_id = :categoria
             ORDER BY p.featured DESC, p.published_at DESC NULLS LAST, p.id DESC"
        );
        $stmt->execute([':categoria' => $categoriaId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * A data de publicação é preenchida na primeira vez que a publicação
     * passa para a situação "Publicado" e depois não muda mais.
     */
    private function dataPublicacao(int $statusId, ?string $atual): ?string
    {
        if ($atual !== null) {
            return $atual;
        }

        foreach ($this->statuses() as $status) {
            if ((int) $status['id'] === $statusId && $status['name'] === 'Publicado') {
                return date('Y-m-d H:i:s');
            }
        }

        return null;
    }

    /** Gera um slug que ainda não exista (acrescenta -2, -3... se preciso) */
    private function slugUnico(string $base): string
    {
        $base = $base !== '' ? $base : 'publicacao';
        $slug = $base;
        $n    = 2;

        $stmt = $this->db->prepare('SELECT 1 FROM publications WHERE slug = :slug');

        while (true) {
            $stmt->execute([':slug' => $slug]);

            if (!$stmt->fetchColumn()) {
                return $slug;
            }

            $slug = $base . '-' . $n;
            $n++;
        }
    }
}
