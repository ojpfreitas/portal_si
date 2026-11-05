<?php
/**
 * app/Views/shared/confirmar-exclusao.php
 * Segunda tela antes de excluir um registro — substitui o antigo confirm()
 * do JavaScript por uma confirmação em PHP puro, sem nenhuma linha de JS.
 *
 * Espera: $tituloPagina, $mensagem, $acaoForm (URL do POST que exclui de
 * fato) e $urlCancelar (para onde volta se a pessoa desistir).
 */
?>

<div class="confirm-card">
    <h1><?= e($tituloPagina); ?></h1>
    <p class="lead"><?= e($mensagem); ?></p>

    <div class="form-actions">
        <form action="<?= e($acaoForm); ?>" method="POST">
            <?= csrf_campo(); ?>
            <button type="submit" class="btn btn-danger">Sim, excluir</button>
        </form>
        <a class="btn-ghost" href="<?= e($urlCancelar); ?>">Cancelar</a>
    </div>
</div>
