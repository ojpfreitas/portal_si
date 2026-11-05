<?php

// config/database.example.php
// MODELO. O instalador (public/install) cria o config/database.php verdadeiro por você.
// Para fazer à mão: copie este arquivo para config/database.php e ajuste os valores.
// Este arquivo define a variável $pdo (usada por App\Core\Database e por
// database/create_admin.php).

$host     = 'localhost';
$dbname   = 'portal_si';
$username = 'postgres';
$password = 'troque-esta-senha';
$port     = '5432';

try {
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec("SET client_encoding TO 'UTF8'");
} catch (PDOException $e) {
    // O detalhe vai para o log do servidor; a tela não expõe usuário nem servidor.
    error_log('Portal SI - erro de conexão com o banco: ' . $e->getMessage());
    http_response_code(500);
    exit('Não foi possível conectar ao banco de dados.');
}
