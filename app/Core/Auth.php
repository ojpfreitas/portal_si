<?php

namespace App\Core;

/**
 * Sessão do usuário e regras de permissão por perfil.
 */
class Auth
{
    // Códigos dos perfis (users.profile_id = user_profile.id)
    public const PERFIL_ACESSO_TOTAL = 1;
    public const PERFIL_VISUALIZACAO = 2;
    public const PERFIL_DIVULGACAO   = 3;
    public const PERFIL_ADMIN        = 4;

    // Perfil atribuído a quem se cadastra pela tela pública
    public const PERFIL_CADASTRO_PADRAO = self::PERFIL_VISUALIZACAO;

    private const NOMES_PERFIS = [
        self::PERFIL_ACESSO_TOTAL => 'Acesso Total',
        self::PERFIL_VISUALIZACAO => 'Visualização',
        self::PERFIL_DIVULGACAO   => 'Divulgação',
        self::PERFIL_ADMIN        => 'Administrador',
    ];

    // ---------- Sessão ----------

    public static function logado(): bool
    {
        return !empty($_SESSION['usuario_id']);
    }

    public static function id(): int
    {
        return (int) ($_SESSION['usuario_id'] ?? 0);
    }

    public static function nome(): string
    {
        return (string) ($_SESSION['usuario_nome'] ?? '');
    }

    public static function perfil(): int
    {
        return (int) ($_SESSION['usuario_perfil'] ?? 0);
    }

    public static function nomePerfil(int $codigo): string
    {
        return self::NOMES_PERFIS[$codigo] ?? 'Perfil não identificado';
    }

    /** Grava o usuário autenticado na sessão */
    public static function entrar(array $usuario): void
    {
        // Prevenção de fixação de sessão
        session_regenerate_id(true);

        $_SESSION['usuario_id']     = (int) $usuario['id'];
        $_SESSION['usuario_nome']   = $usuario['name'];
        $_SESSION['usuario_email']  = $usuario['email'];
        $_SESSION['usuario_perfil'] = (int) $usuario['profile_id'];
        $_SESSION['ultimo_acesso']  = time();
    }

    /** Encerra a sessão por completo */
    public static function sair(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }

        session_destroy();
    }

    // ---------- Guardas de acesso ----------

    /** Bloqueia a página se não houver usuário logado */
    public static function exigirLogin(): void
    {
        if (!self::logado()) {
            flash('aviso', 'Para acessar essa página, entre com seu e-mail e senha.');
            redirecionar('/login');
        }
    }

    /** Bloqueia a página se o perfil do usuário não estiver na lista */
    public static function exigirPerfis(array $perfis): void
    {
        self::exigirLogin();

        if (!in_array(self::perfil(), $perfis, true)) {
            flash('erro', 'Você não tem permissão para acessar essa página.');
            redirecionar('/admin');
        }
    }

    // ---------- Regras de permissão ----------

    /** Divulgação e Administrador criam e editam publicações e eventos */
    public static function podeGerenciarConteudo(): bool
    {
        return in_array(self::perfil(), [self::PERFIL_DIVULGACAO, self::PERFIL_ADMIN], true);
    }

    /** Somente o Administrador exclui publicações e eventos */
    public static function podeExcluirConteudo(): bool
    {
        return self::perfil() === self::PERFIL_ADMIN;
    }

    /** Acesso Total (somente leitura) e Administrador veem a lista de usuários */
    public static function podeVerUsuarios(): bool
    {
        return in_array(self::perfil(), [self::PERFIL_ACESSO_TOTAL, self::PERFIL_ADMIN], true);
    }

    /** Somente o Administrador cria e edita usuários */
    public static function podeGerenciarUsuarios(): bool
    {
        return self::perfil() === self::PERFIL_ADMIN;
    }
}
