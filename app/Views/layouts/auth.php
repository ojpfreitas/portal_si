<?php
/**
 * Layout das telas de login e cadastro (fora do painel).
 */
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
    <link rel="stylesheet" href="<?= e(asset('css/auth.css')); ?>">
</head>

<body id="topo">

    <div class="layout">

        <aside class="brand">
            <a class="logo" href="<?= e(url('/')); ?>">
                <span class="logo-mark">SI</span>
                Portal SI
            </a>

            <div class="brand-copy">
                <h1>Toda a comunicação do curso em um só lugar.</h1>
                <p>Avisos da coordenação, materiais das disciplinas e mensagens entre alunos e professores, sempre à mão.</p>
            </div>

            <svg class="brand-network" viewBox="0 0 380 260" fill="none" aria-hidden="true">
                <g stroke="#7a9bf0" stroke-width="1.2" stroke-opacity="0.6">
                    <line x1="60" y1="200" x2="150" y2="130" />
                    <line x1="150" y1="130" x2="250" y2="170" />
                    <line x1="150" y1="130" x2="210" y2="50" />
                    <line x1="250" y1="170" x2="330" y2="100" />
                    <line x1="210" y1="50" x2="330" y2="100" />
                    <line x1="250" y1="170" x2="290" y2="240" />
                </g>
                <g fill="#0e1b3d" stroke="#9db6f5" stroke-width="1.5">
                    <circle cx="60" cy="200" r="7" />
                    <circle cx="150" cy="130" r="10" />
                    <circle cx="250" cy="170" r="7" />
                    <circle cx="210" cy="50" r="6" />
                    <circle cx="330" cy="100" r="9" />
                    <circle cx="290" cy="240" r="5" />
                </g>
            </svg>
        </aside>

        <main class="content">
            <div class="card">
                <?= flashes(); ?>
                <?= $conteudo; ?>
            </div>
        </main>

    </div>

    <?php require_once APP_PATH . '/Views/partials/footer.php'; ?>

</body>

</html>