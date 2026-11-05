<?php
$tituloPagina = $registro['id'] ? 'Editar usuário' : 'Novo usuário';
$acaoForm     = $registro['id']
    ? url('/admin/usuarios/' . $registro['id'])
    : url('/admin/usuarios');
?>

<div class="page-head">
    <h1><?= e($tituloPagina); ?></h1>
    <a class="btn-ghost" href="<?= e(url('/admin/usuarios')); ?>">Voltar à lista</a>
</div>

<?php if (!empty($erros)): ?>
    <div class="alert alert-error" role="alert">
        <p class="alert-title">Não foi possível salvar o usuário</p>
        <ul>
            <?php foreach ($erros as $erro): ?>
                <li><?= e($erro); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form action="<?= e($acaoForm); ?>" method="POST" class="form-card" novalidate>
    <?= csrf_campo(); ?>

    <div class="field">
        <label for="name">Nome completo</label>
        <input class="input" type="text" id="name" name="name" value="<?= e($registro['name']); ?>" required>
    </div>

    <div class="field">
        <label for="email">E-mail</label>
        <input class="input" type="email" id="email" name="email" value="<?= e($registro['email']); ?>" required>
    </div>

    <div class="field-row">
        <div class="field">
            <label for="profile_id">Perfil</label>
            <select class="input" id="profile_id" name="profile_id" required>
                <option value="">Selecione</option>
                <?php foreach ($perfis as $perfil): ?>
                    <option value="<?= (int) $perfil['id']; ?>"
                        <?= (int) $registro['profile_id'] === (int) $perfil['id'] ? 'selected' : ''; ?>>
                        <?= e($perfil['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field">
            <label class="checkbox checkbox-standalone">
                <input type="checkbox" name="status" value="1" <?= !empty($registro['status']) ? 'checked' : ''; ?>>
                Conta ativa
            </label>
        </div>
    </div>

    <div class="field-row">
        <div class="field">
            <label for="senha"><?= $registro['id'] ? 'Nova senha (opcional)' : 'Senha'; ?></label>
            <input class="input" type="password" id="senha" name="senha"
                minlength="8" autocomplete="new-password" <?= $registro['id'] ? '' : 'required'; ?>>
            <?php if ($registro['id']): ?>
                <p class="hint">Deixe em branco para manter a senha atual.</p>
            <?php endif; ?>
        </div>

        <div class="field">
            <label for="senha_confirmacao">Confirmar senha</label>
            <input class="input" type="password" id="senha_confirmacao" name="senha_confirmacao"
                autocomplete="new-password" <?= $registro['id'] ? '' : 'required'; ?>>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn">Salvar usuário</button>
        <a class="btn-ghost" href="<?= e(url('/admin/usuarios')); ?>">Cancelar</a>
    </div>
</form>
