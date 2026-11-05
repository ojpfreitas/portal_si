<?php
/** Etapa 1: confere o ambiente. Recebe $requisitos e $requisitosOk. */
$classes = ['ok' => 'badge-success', 'aviso' => 'badge-warning', 'erro' => 'badge-danger'];
$rotulos = ['ok' => 'OK', 'aviso' => 'Atenção', 'erro' => 'Problema'];
?>

<h2>Vamos instalar o portal</h2>
<p class="lead">
    Este assistente confere o ambiente, prepara o banco PostgreSQL e cria o primeiro
    Administrador. Leva poucos minutos.
</p>

<ul class="req-list">
    <?php foreach ($requisitos as $item): ?>
        <li class="req">
            <span class="badge <?= h($classes[$item['nivel']]); ?>"><?= h($rotulos[$item['nivel']]); ?></span>
            <div>
                <strong><?= h($item['nome']); ?></strong>
                <span class="req-detail"><?= h($item['detalhe']); ?></span>
            </div>
        </li>
    <?php endforeach; ?>
</ul>

<?php if ($requisitosOk): ?>
    <form action="?etapa=requisitos" method="POST">
        <?= campoCsrf(); ?>
        <button type="submit" class="btn">Continuar</button>
    </form>
<?php else: ?>
    <div class="alert alert-error" role="alert">
        Corrija os itens marcados como "Problema" e confira de novo.
    </div>
    <a class="link-btn" href="?etapa=requisitos">Verificar novamente</a>
<?php endif; ?>
