<?php
/**
 * Moldura do assistente: cabeçalho, indicador de passos e o conteúdo da etapa.
 * Recebe $etapa (nome da tela) e $arquivo (caminho da view da etapa).
 */
$passos    = ['requisitos' => 'Requisitos', 'banco' => 'Banco de dados', 'admin' => 'Administrador'];
$ordem     = array_keys($passos);
$indice    = array_search($etapa, $ordem, true);
$concluido = $etapa === 'resultado';
$mostrarPassos = $indice !== false || $concluido;
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Instalação — Portal SI</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/components.css">
    <link rel="stylesheet" href="install.css">
</head>

<body>

    <header class="inst-top">
        <span class="logo">
            <span class="logo-mark">SI</span>
            Portal SI &middot; Instalação
        </span>
    </header>

    <main class="inst-wrap">

        <?php if ($mostrarPassos): ?>
            <ol class="steps" aria-label="Etapas da instalação">
                <?php foreach ($ordem as $posicao => $chave): ?>
                    <?php
                        $classe = '';
                        if ($concluido || ($indice !== false && $posicao < $indice)) {
                            $classe = ' is-done';
                        } elseif ($posicao === $indice) {
                            $classe = ' is-current';
                        }
                    ?>
                    <li class="step<?= $classe; ?>"<?= $posicao === $indice ? ' aria-current="step"' : ''; ?>>
                        <span><?= h($passos[$chave]); ?></span>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>

        <div class="inst-card">
            <?php require $arquivo; ?>
        </div>

        <p class="inst-foot">Instalador do Portal SI</p>
    </main>

</body>

</html>
