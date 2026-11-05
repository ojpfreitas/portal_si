<?php
/** Resumo final. Recebe $resultado, $urlPortal e $avisoRemocao. */
$registro = $resultado['log'] ?? [];
$manual   = $resultado['manual'] ?? [];
$trancado = !empty($resultado['lock']);
$email    = $resultado['email'] ?? '';

// "atualizacao" = perfil somente-atualizar: o banco já existia, não foi criado Administrador
$atualizacao = ($resultado['modo'] ?? '') === 'atualizacao';
$semAdmin    = !empty($resultado['sem_admin']);
?>

<?php if ($atualizacao): ?>
    <h2>Banco atualizado e conexão configurada</h2>
    <p class="lead">O portal agora usa o banco "<?= h($resultado['banco'] ?? ''); ?>". Entre com um Administrador que já existia nele.</p>

    <?php if ($semAdmin): ?>
        <div class="alert alert-warning" role="alert">
            Este banco não tem nenhum Administrador. Crie um pelo terminal: <code>php database/create_admin.php</code>.
        </div>
    <?php endif; ?>
<?php else: ?>
    <h2>Instalação concluída</h2>
    <p class="lead">O portal está pronto. Entre com o e-mail e a senha do Administrador que você acabou de criar.</p>
<?php endif; ?>

<?php if ($email !== ''): ?>
    <div class="alert alert-success" role="status">Login do Administrador: <strong><?= h($email); ?></strong></div>
<?php endif; ?>

<?php if ($manual): ?>
    <div class="alert alert-warning" role="alert">
        O PHP não tem permissão para gravar na pasta <code>config/</code>. Crie os arquivos abaixo à mão,
        com exatamente este conteúdo, para o portal funcionar. Ele contém a senha do banco: não compartilhe.
    </div>

    <?php foreach ($manual as $caminho => $conteudo): ?>
        <div class="field">
            <label for="arq-<?= h(md5($caminho)); ?>"><code><?= h($caminho); ?></code></label>
            <textarea class="input textarea-lg" id="arq-<?= h(md5($caminho)); ?>" readonly rows="14"><?= h($conteudo); ?></textarea>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php if (!$trancado): ?>
    <div class="alert alert-warning" role="alert">
        Não consegui criar o arquivo <code>config/install.lock</code>. Para ninguém reutilizar o instalador,
        apague a pasta <code>public/install</code> agora.
    </div>
<?php endif; ?>

<?php if (!empty($avisoRemocao)): ?>
    <div class="alert alert-warning" role="alert"><?= h($avisoRemocao); ?></div>
<?php endif; ?>

<h3 class="sub">O que foi feito</h3>
<ul class="log">
    <?php foreach ($registro as $linha): ?>
        <li><?= h($linha); ?></li>
    <?php endforeach; ?>
</ul>

<div class="form-actions form-actions-wrap">
    <a class="link-btn link-btn-primary" href="<?= h($urlPortal); ?>/login">Entrar no portal</a>

    <form action="?etapa=resultado" method="POST">
        <?= campoCsrf(); ?>
        <input type="hidden" name="acao" value="remover">
        <button type="submit" class="btn btn-secondary btn-auto">Remover o instalador e entrar no portal</button>
    </form>
</div>
<p class="hint">"Remover o instalador" apaga a pasta <code>public/install</code> do servidor e leva à tela de login. É a opção mais segura em produção. Ao testar na sua máquina, prefira "Entrar no portal".</p>