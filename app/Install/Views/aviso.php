<?php
/** Mensagem simples (instalação já feita, requisição inválida...). Recebe $titulo, $mensagem e $urlPortal. */
?>

<h2><?= h($titulo); ?></h2>
<p class="lead"><?= h($mensagem); ?></p>

<?php if (!empty($urlPortal) || $urlPortal === ''): ?>
    <a class="link-btn link-btn-primary" href="<?= h($urlPortal); ?>/">Ir para o portal</a>
<?php else: ?>
    <a class="link-btn" href="?etapa=requisitos">Voltar ao início</a>
<?php endif; ?>
