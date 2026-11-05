<?php

// app/bootstrap.php
// Inicialização comum: constantes, funções globais, autoload e sessão.

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', __DIR__);

date_default_timezone_set('America/Sao_Paulo');

require_once APP_PATH . '/helpers/functions.php';

// config/app.php e config/database.php não vão para o Git (têm dados de cada instalação).
// Sem eles o portal ainda não foi instalado: leva para o instalador ou explica o que fazer.
if (!is_file(BASE_PATH . '/config/app.php') || !is_file(BASE_PATH . '/config/database.php')) {
    if (PHP_SAPI !== 'cli' && is_file(BASE_PATH . '/public/install/index.php')) {
        $pasta = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? ''))), '/');

        header('Location: ' . $pasta . '/install/');
        exit;
    }

    http_response_code(503);
    exit('Portal ainda não configurado. Rode o instalador (public/install ou "php database/instalar.php") '
        . 'ou copie config/app.example.php para config/app.php e config/database.example.php '
        . 'para config/database.php e ajuste os valores.');
}

require_once __DIR__ . '/../config/app.php';

// Autoload simples: App\Models\Usuario => app/Models/Usuario.php
spl_autoload_register(function (string $classe): void {
    $prefixo = 'App\\';

    if (strncmp($classe, $prefixo, strlen($prefixo)) !== 0) {
        return;
    }

    $relativa = substr($classe, strlen($prefixo));
    $arquivo  = APP_PATH . '/' . str_replace('\\', '/', $relativa) . '.php';

    if (is_file($arquivo)) {
        require $arquivo;
    }
});

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
