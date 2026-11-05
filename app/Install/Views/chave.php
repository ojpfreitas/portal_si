<?php
/** Acesso de fora do servidor: pede a chave de config/install.key. Recebe $chaveOk e $erroChave. */
?>

<h2>Confirme que você tem acesso ao servidor</h2>

<?php if ($chaveOk): ?>
    <p class="lead">
        Você está acessando o instalador de fora do próprio servidor. Para garantir que é você, abra o
        arquivo <code>config/install.key</code> na pasta do projeto (pelo FTP, SSH ou área de trabalho
        remota) e digite aqui o código que está dentro dele.
    </p>

    <?php if (!empty($erroChave)): ?>
        <div class="alert alert-error" role="alert"><?= h($erroChave); ?></div>
    <?php endif; ?>

    <form action="?etapa=requisitos" method="POST" novalidate>
        <?= campoCsrf(); ?>
        <input type="hidden" name="acao" value="chave">

        <div class="field">
            <label for="chave">Chave de instalação</label>
            <input class="input" type="text" id="chave" name="chave" autocomplete="off" autofocus required>
        </div>

        <button type="submit" class="btn">Continuar</button>
    </form>
<?php else: ?>
    <div class="alert alert-error" role="alert">
        Não consegui criar o arquivo <code>config/install.key</code>.
    </div>
    <p class="lead">
        Dê permissão de escrita ao PHP na pasta <code>config/</code> e recarregue esta página. Se preferir,
        instale pelo próprio servidor (acessando por <code>localhost</code>) ou pelo terminal, com
        <code>php database/instalar.php</code>.
    </p>
<?php endif; ?>
