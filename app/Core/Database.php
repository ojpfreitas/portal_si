<?php

namespace App\Core;

use PDO;

/**
 * Entrega a conexão PDO criada em config/database.php.
 * O arquivo config/database.php continua definindo a variável $pdo.
 */
class Database
{
    private static ?PDO $conexao = null;

    public static function connection(): PDO
    {
        if (self::$conexao === null) {
            require BASE_PATH . '/config/database.php';

            self::$conexao = $pdo;

            self::registrarOrigem($pdo);
        }

        return self::$conexao;
    }

    /** Informa ao banco o usuário e o IP da requisição, para a auditoria */
    private static function registrarOrigem(PDO $pdo): void
    {
        $usuario = Auth::id();
        $ip      = $_SERVER['REMOTE_ADDR'] ?? '';

        $stmt = $pdo->prepare(
            "SELECT set_config('app.user_id', :usuario, false),
                    set_config('app.origin_ip', :ip, false)"
        );
        $stmt->execute([
            ':usuario' => $usuario > 0 ? (string) $usuario : '',
            ':ip'      => filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '',
        ]);
    }
}
