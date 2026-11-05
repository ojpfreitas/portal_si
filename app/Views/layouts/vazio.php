<?php
/** Layout mínimo, usado pelas páginas de erro. */
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= isset($tituloPagina) ? e($tituloPagina) : 'Portal SI'; ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/base.css')); ?>">
    <link rel="stylesheet" href="<?= e(asset('css/components.css')); ?>">
</head>

<body>
    <main class="container" style="max-width:560px; text-align:center; padding-top:96px;">
        <?= $conteudo; ?>
    </main>
</body>

</html>
