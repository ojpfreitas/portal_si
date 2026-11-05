<?php

namespace App\Models;

use App\Core\Model;
use PDO;

/**
 * Calendário de eventos (tabela events).
 */
class Evento extends Model
{
    /** Situações possíveis (Agendado, Cancelado, Realizado) */
    public function statuses(): array
    {
        $stmt = $this->db->query('SELECT id, name FROM event_status ORDER BY id');

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Filtros: busca (título) e status (id) */
    public function listar(array $filtros = []): array
    {
        $where  = [];
        $params = [];

        if (!empty($filtros['busca'])) {
            $where[] = 'e.title ILIKE :busca';
            $params[':busca'] = '%' . addcslashes($filtros['busca'], '%_\\') . '%';
        }

        if (!empty($filtros['status'])) {
            $where[] = 'e.status_id = :status';
            $params[':status'] = (int) $filtros['status'];
        }

        $sql = 'SELECT e.id, e.title, e.start_date, e.end_date, e.location,
                       s.name AS status_name, u.name AS author_name
                FROM events e
                JOIN event_status s ON s.id = e.status_id
                JOIN users u ON u.id = e.author_id';

        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY e.start_date DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscar(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, title, description, start_date, end_date, location,
                    registration_link, status_id
             FROM events
             WHERE id = :id'
        );
        $stmt->execute([':id' => $id]);

        $registro = $stmt->fetch(PDO::FETCH_ASSOC);

        return $registro ?: null;
    }

    /**
     * $dados: title, description, start_date, end_date (nullable),
     * location, registration_link e status_id. Datas no formato do banco.
     */
    public function criar(array $dados, int $autor): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO events
                (title, description, start_date, end_date, location,
                 registration_link, author_id, status_id)
             VALUES
                (:title, :description, :start_date, :end_date, :location,
                 :registration_link, :author_id, :status_id)'
        );
        $stmt->execute($this->parametros($dados) + [':author_id' => $autor]);
    }

    public function atualizar(int $id, array $dados): void
    {
        $stmt = $this->db->prepare(
            'UPDATE events
             SET title = :title, description = :description, start_date = :start_date,
                 end_date = :end_date, location = :location,
                 registration_link = :registration_link, status_id = :status_id
             WHERE id = :id'
        );
        $stmt->execute($this->parametros($dados) + [':id' => $id]);
    }

    public function excluir(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM events WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    /** Próximos eventos agendados (para o site público) */
    public function proximos(int $limite = 6): array
    {
        $stmt = $this->db->query(
            "SELECT e.id, e.title, e.start_date, e.end_date, e.location, e.registration_link
             FROM events e
             JOIN event_status s ON s.id = e.status_id
             WHERE s.name = 'Agendado' AND e.start_date >= CURRENT_TIMESTAMP
             ORDER BY e.start_date ASC
             LIMIT " . (int) $limite
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Eventos já realizados, do mais recente para o mais antigo (site público) */
    public function realizados(int $limite = 6): array
    {
        $stmt = $this->db->query(
            "SELECT e.id, e.title, e.start_date, e.end_date, e.location, e.registration_link
             FROM events e
             JOIN event_status s ON s.id = e.status_id
             WHERE s.name = 'Realizado'
             ORDER BY e.start_date DESC
             LIMIT " . (int) $limite
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function parametros(array $dados): array
    {
        return [
            ':title'             => $dados['title'],
            ':description'       => $dados['description'] !== '' ? $dados['description'] : null,
            ':start_date'        => $dados['start_date'],
            ':end_date'          => $dados['end_date'],
            ':location'          => $dados['location'] !== '' ? $dados['location'] : null,
            ':registration_link' => $dados['registration_link'] !== '' ? $dados['registration_link'] : null,
            ':status_id'         => (int) $dados['status_id'],
        ];
    }
}
