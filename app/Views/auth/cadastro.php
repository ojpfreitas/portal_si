<?php $tituloPagina = 'Criar conta'; ?>

<h2>Criar conta</h2>
<p class="lead">Preencha os dados abaixo para acessar o portal.</p>

<form action="<?= e(url('/cadastroUsuario')); ?>" method="POST" novalidate>
    <?= csrf_campo(); ?>

    <div class="field">
        <label for="nome">Nome completo</label>
        <input class="input" type="text" id="nome" name="nome"
               value="<?= e($nome); ?>" placeholder="Como aparece na sua matrícula"
               autocomplete="name" required>
    </div>

    <div class="field">
        <label for="email">E-mail</label>
        <input class="input" type="email" id="email" name="email"
               value="<?= e($email); ?>" placeholder="voce@exemplo.com"
               autocomplete="email" required>
    </div>

    <div class="field">
        <label for="senha">Senha</label>
        <input class="input" type="password" id="senha" name="senha"
                   minlength="8" autocomplete="new-password" aria-describedby="senha-hint" required>
        <p class="hint" id="senha-hint">Use pelo menos 8 caracteres.</p>
    </div>

    <div class="field">
        <label for="senha_confirmacao">Confirmar senha</label>
        <input class="input" type="password" id="senha_confirmacao" name="senha_confirmacao"
                   autocomplete="new-password" required>
    </div>

    <button type="submit" class="btn">Criar conta</button>
</form>

<p class="switch">Já tem conta? <a href="<?= e(url('/login')); ?>">Entrar</a></p>
