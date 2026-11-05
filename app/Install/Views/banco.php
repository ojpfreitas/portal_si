<?php
/** Etapa 2: escolha do banco e credenciais. Recebe $erros, $valores e $perfis. */
?>

<h2>Banco de dados</h2>
<p class="lead">
    Escolha o banco de dados e informe o usuário e a senha do PostgreSQL.
</p>

<?php if ($erros): ?>
    <div class="alert alert-error" role="alert">
        <?php if (count($erros) === 1): ?>
            <?= h($erros[0]); ?>
        <?php else: ?>
            <ul>
                <?php foreach ($erros as $erro): ?>
                    <li><?= h($erro); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
<?php endif; ?>

<form action="?etapa=banco" method="POST" novalidate>
    <?= campoCsrf(); ?>

    <fieldset class="field">
        <legend>Banco de dados</legend>
        <?php foreach ($perfis as $chave => $perfil): ?>
            <label class="radio">
                <input type="radio" name="perfil" value="<?= h((string) $chave); ?>" <?= ($valores['perfil'] ?? '') === (string) $chave ? 'checked' : ''; ?>>
                <span class="radio-text">
                    <strong><?= h($perfil['nome']); ?></strong>
                    <span class="radio-detail">Servidor: <?= h($perfil['host']); ?></span>
                    <span class="radio-detail">Porta: <?= h($perfil['port']); ?> · Banco: <?= h($perfil['name']); ?></span>
                    <?php if (!empty($perfil['somente_atualizar'])): ?>
                        <span class="radio-detail">Banco já instalado: o assistente só aplica as atualizações pendentes e configura a conexão, sem criar Administrador.</span>
                    <?php endif; ?>
                </span>
            </label>
        <?php endforeach; ?>
    </fieldset>

    <div class="field-row">
        <div class="field">
            <label for="user">Usuário do PostgreSQL</label>
            <input class="input" type="text" id="user" name="user" value="<?= h($valores['user'] ?? ''); ?>" autocomplete="off" required>
        </div>
        <div class="field">
            <label for="pass">Senha do usuário</label>
            <input class="input" type="password" id="pass" name="pass" value="" autocomplete="new-password">
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn">Testar conexão e continuar</button>
        <a class="link-btn" href="?etapa=requisitos">Voltar</a>
    </div>
</form>