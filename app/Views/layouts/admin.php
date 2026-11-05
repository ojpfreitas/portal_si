<?php
/**
 * Layout do painel administrativo.
 * Espera optionalmente $tituloPagina; usa $conteudo (view já renderizada).
 */
use App\Core\Auth;
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= isset($tituloPagina) ? e($tituloPagina) . ' — ' : ''; ?>Portal SI</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="<?= e(asset('css/base.css')); ?>">
    <link rel="stylesheet" href="<?= e(asset('css/components.css')); ?>">
    <link rel="stylesheet" href="<?= e(asset('css/dashboard.css')); ?>">
</head>

<body id="topo">

    <header class="topbar">
        <div class="topbar-inner">

            <div class="topbar-left">
                <a class="logo" href="<?= e(url('/admin')); ?>">
                    <span class="logo-mark">SI</span>
                    Portal SI
                </a>

                <nav class="topnav">
                    <a href="<?= e(url('/admin/conteudos')); ?>">Publicações</a>
                    <a href="<?= e(url('/admin/eventos')); ?>">Eventos</a>
                    <?php if (Auth::podeVerUsuarios()): ?>
                        <a href="<?= e(url('/admin/usuarios')); ?>">Usuários</a>
                    <?php endif; ?>
                </nav>
            </div>

            <div class="user">
                <div class="user-info">
                    <span class="avatar" aria-hidden="true"><?= e(iniciais(Auth::nome())); ?></span>
                    <span class="user-text">
                        <span class="user-name"><?= e(Auth::nome()); ?></span>
                        <span class="user-role"><?= e(Auth::nomePerfil(Auth::perfil())); ?></span>
                    </span>
                </div>

                <a class="logout" href="<?= e(url('/logout')); ?>">Sair</a>
            </div>

        </div>
    </header>

    <main class="container">
        <?= flashes(); ?>
        <?= $conteudo; ?>
    </main>

    <?php require_once APP_PATH . '/Views/partials/footer.php'; ?>

</body>

</html>
