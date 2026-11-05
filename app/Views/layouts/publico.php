<?php
/**
 * Layout do site público (página inicial).
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

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="<?= e(asset('css/base.css')); ?>">
    <link rel="stylesheet" href="<?= e(asset('css/components.css')); ?>">
    <link rel="stylesheet" href="<?= e(asset('css/publico.css')); ?>">
</head>

<body id="topo">

    <?php require_once APP_PATH . '/Views/partials/header.php'; ?>

    <main>
        <?= $conteudo; ?>
    </main>

    <?php require_once APP_PATH . '/Views/partials/footer.php'; ?>

</body>

</html>
