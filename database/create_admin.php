<?php

// database/create_admin.php
// Cria (ou promove) um usuário Administrador. Rode uma vez, pela linha de comando:
//
//   php database/create_admin.php "Seu Nome" seu@email.com "sua-senha-forte"
//
// Depois de usar, é seguro apagar este arquivo — ele não faz parte do site
// e só funciona rodado por CLI (não abre pelo navegador).

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Este script só pode ser executado pela linha de comando.');
}

if ($argc !== 4) {
    fwrite(STDERR, "Uso: php create_admin.php \"Nome Completo\" email@exemplo.com \"senha\"\n");
    exit(1);
}

[, $nome, $email, $senha] = $argv;

if (mb_strlen($nome) < 3) {
    fwrite(STDERR, "O nome deve ter ao menos 3 caracteres.\n");
    exit(1);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "E-mail inválido.\n");
    exit(1);
}

if (mb_strlen($senha) < 8) {
    fwrite(STDERR, "A senha deve ter ao menos 8 caracteres.\n");
    exit(1);
}

if (!is_file(__DIR__ . '/../config/database.php')) {
    fwrite(STDERR, "config/database.php não encontrado. Rode primeiro o instalador (public/install ou php database/instalar.php).\n");
    exit(1);
}

require __DIR__ . '/../config/database.php'; // define $pdo

const PERFIL_ADMIN = 4;

$hash = password_hash($senha, PASSWORD_DEFAULT);

// Se o e-mail já existir, promove a conta a Administrador e atualiza a senha.
// Caso contrário, cria uma conta nova já como Administrador.
$stmt = $pdo->prepare(
    'INSERT INTO users (name, email, password_hash, profile_id, status)
     VALUES (:name, :email, :password_hash, :profile_id, TRUE)
     ON CONFLICT (email) DO UPDATE
        SET password_hash = EXCLUDED.password_hash,
            profile_id  = EXCLUDED.profile_id,
            status        = TRUE,
            updated_at    = CURRENT_TIMESTAMP'
);

$stmt->execute([
    ':name'          => $nome,
    ':email'         => $email,
    ':password_hash' => $hash,
    ':profile_id'  => PERFIL_ADMIN,
]);

echo "Pronto! {$email} agora é Administrador.\n";
