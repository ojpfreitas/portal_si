<?php

namespace App\Models;

use App\Core\Model;
use PDO;

/**
 * Usuários do portal (tabela users).
 * Todos os métodos usam consultas preparadas; o hash da senha só é lido no login.
 */
class Usuario extends Model
{
    /**
     * Usado no login: busca a conta pelo e-mail, ativa ou não, com o hash para conferir a senha.
     * Quem chama confere a senha primeiro e só depois olha o status.
     */
    public function buscarPorEmail(string $email): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, email, password_hash, profile_id, status
             FROM users
             WHERE email = :email
             LIMIT 1'
        );
        $stmt->execute([':email' => $email]);

        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        return $usuario ?: null;
    }

    /**
     * Lista usuários com o nome do perfil.
     * Filtros: busca (nome ou e-mail), perfil (id) e situacao ('ativo' ou 'inativo').
     */
    public function listar(array $filtros = []): array
    {
        $where  = [];
        $params = [];

        if (!empty($filtros['busca'])) {
            $like = '%' . addcslashes($filtros['busca'], '%_\\') . '%';

            $where[] = '(u.name ILIKE :busca_nome OR u.email ILIKE :busca_email)';
            $params[':busca_nome']  = $like;
            $params[':busca_email'] = $like;
        }

        if (!empty($filtros['perfil'])) {
            $where[] = 'u.profile_id = :perfil';
            $params[':perfil'] = (int) $filtros['perfil'];
        }

        if (($filtros['situacao'] ?? '') === 'ativo') {
            $where[] = 'u.status = TRUE';
        } elseif (($filtros['situacao'] ?? '') === 'inativo') {
            $where[] = 'u.status = FALSE';
        }

        $sql = 'SELECT u.id, u.name, u.email, u.status, u.created_at, p.name AS profile_name
                FROM users u
                JOIN user_profile p ON p.id = u.profile_id';

        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY u.name';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Dados de um usuário para o formulário (nunca inclui o hash da senha) */
    public function buscar(int $codigo): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, email, profile_id, status FROM users WHERE id = :codigo'
        );
        $stmt->execute([':codigo' => $codigo]);

        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        return $usuario ?: null;
    }

    public function criar(string $nome, string $email, string $senhaHash, int $perfil, bool $ativo = true): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO users (name, email, password_hash, profile_id, status)
             VALUES (:name, :email, :password_hash, :profile_id, :status)'
        );
        $stmt->execute([
            ':name'          => $nome,
            ':email'         => $email,
            ':password_hash' => $senhaHash,
            ':profile_id'  => $perfil,
            ':status'        => $ativo ? 'true' : 'false',
        ]);
    }

    /** Atualiza o usuário; a senha só muda se $senhaHash for informado */
    public function atualizar(int $codigo, array $dados, ?string $senhaHash = null): void
    {
        $sql = 'UPDATE users
                SET name = :name, email = :email, profile_id = :profile_id,
                    status = :status, updated_at = CURRENT_TIMESTAMP';

        $params = [
            ':name'         => $dados['name'],
            ':email'        => $dados['email'],
            ':profile_id' => (int) $dados['profile_id'],
            ':status'       => $dados['ativo'] ? 'true' : 'false',
            ':codigo'       => $codigo,
        ];

        if ($senhaHash !== null) {
            $sql .= ', password_hash = :password_hash';
            $params[':password_hash'] = $senhaHash;
        }

        $sql .= ' WHERE id = :codigo';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }
}
