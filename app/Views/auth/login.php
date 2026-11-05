<?php $tituloPagina = 'Entrar'; ?>

<h2>Entrar</h2>
<p class="lead">Acesse o painel do Portal SI com seu e-mail e senha.</p>

<form action="<?= e(url('/login')); ?>" method="POST" novalidate>
    <?= csrf_campo(); ?>

    <div class="field">
        <label for="email">E-mail</label>
        <input class="input" type="email" id="email" name="email"
               value="<?= e($email); ?>" placeholder="voce@exemplo.com"
               autocomplete="username" required>
    </div>

    <div class="field">
        <label for="senha">Senha</label>
        <input class="input" type="password" id="senha" name="senha"
                   autocomplete="current-password" required>
    </div>

    <button type="submit" class="btn">Entrar</button>
</form>

<p class="switch">Ainda não tem conta? <a href="<?= e(url('/cadastroUsuario')); ?>">Criar conta</a></p>
