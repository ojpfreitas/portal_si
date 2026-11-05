<?php

namespace App\Core;

/**
 * Proteção contra CSRF: um token por sessão, conferido em toda requisição POST.
 */
class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    public static function validar(): bool
    {
        $enviado = $_POST['csrf_token'] ?? '';

        return is_string($enviado) && hash_equals(self::token(), $enviado);
    }
}
