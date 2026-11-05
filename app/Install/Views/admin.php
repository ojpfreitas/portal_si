<?php
/** Etapa 3: primeiro Administrador e instalação. Recebe $erros, $valores, $banco e $diag. */
$pendentes = count($diag['pendentes']);
?>

<h2>Administrador e instalação</h2>
<p class="lead">
    Conexão com o PostgreSQL confirmada. Falta criar a conta do primeiro Administrador,
    que vai gerenciar usuários, publicações e eventos.
</p>

<dl class="summary">
    <div><dt>Servidor</dt><dd><?= h($banco['host'] . ':' . $banco['port']); ?></dd></div>
    <div><dt>Banco</dt><dd>
        <?= h($banco['name']); ?>
        <span class="badge <?= $diag['existe'] ? 'badge-info' : 'badge-warning'; ?>">
            <?= $diag['existe'] ? 'já existe' : 'será criado'; ?>
        </span>
    </dd></div>
    <div><dt>Usuário</dt><dd><?= h($banco['user']); ?></dd></div>
    <div><dt>Estrutura</dt><dd>
        <?= $pendentes === 0 ? 'já está atualizada' : h($pendentes . ' migração(ões) a aplicar'); ?>
    </dd></div>
</dl>

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

<form action="?etapa=admin" method="POST" novalidate>
    <?= campoCsrf(); ?>

    <div class="field">
        <label for="nome">Nome completo</label>
        <input class="input" type="text" id="nome" name="nome" value="<?= h($valores['nome']); ?>" autocomplete="name" required>
    </div>

    <div class="field">
        <label for="email">E-mail (será o login)</label>
        <input class="input" type="email" id="email" name="email" value="<?= h($valores['email']); ?>" autocomplete="email" required>
    </div>

    <div class="field-row">
        <div class="field">
            <label for="senha">Senha</label>
            <input class="input" type="password" id="senha" name="senha" minlength="8" autocomplete="new-password" aria-describedby="senha-hint" required>
        </div>
        <div class="field">
            <label for="senha2">Confirmar senha</label>
            <input class="input" type="password" id="senha2" name="senha2" autocomplete="new-password" required>
        </div>
    </div>
    <p class="hint hint-tight" id="senha-hint">Use pelo menos 8 caracteres e evite senhas comuns.</p>

    <div class="field">
        <label for="base_url">Endereço base do portal</label>
        <input class="input" type="text" id="base_url" name="base_url" value="<?= h($valores['base_url']); ?>" autocomplete="off">
        <p class="hint">
            Caminho da URL até a pasta <code>public</code>, sem barra no final. Já vem preenchido com o
            endereço detectado; deixe vazio se o servidor abre o portal direto na raiz do domínio.
        </p>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn">Instalar agora</button>
        <a class="link-btn" href="?etapa=banco">Voltar</a>
    </div>
</form>
