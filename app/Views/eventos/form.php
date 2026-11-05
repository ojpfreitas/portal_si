<?php
$tituloPagina = $registro['id'] ? 'Editar evento' : 'Novo evento';
$acaoForm     = $registro['id']
    ? url('/admin/eventos/' . $registro['id'])
    : url('/admin/eventos');
?>

<div class="page-head">
    <h1><?= e($tituloPagina); ?></h1>
    <a class="btn-ghost" href="<?= e(url('/admin/eventos')); ?>">Voltar à lista</a>
</div>

<?php if (!empty($erros)): ?>
    <div class="alert alert-error" role="alert">
        <p class="alert-title">Não foi possível salvar o evento</p>
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
        <label for="title">Título</label>
        <input class="input" type="text" id="title" name="title" value="<?= e($registro['title']); ?>" required>
    </div>

    <div class="field">
        <label for="description">Descrição</label>
        <textarea class="input textarea-sm" id="description" name="description"><?= e($registro['description']); ?></textarea>
    </div>

    <div class="field-row">
        <div class="field">
            <label for="start_date">Início</label>
            <input class="input" type="datetime-local" id="start_date" name="start_date"
                   value="<?= e(data_para_input($registro['start_date'])); ?>" required>
        </div>

        <div class="field">
            <label for="end_date">Término (opcional)</label>
            <input class="input" type="datetime-local" id="end_date" name="end_date"
                   value="<?= e(data_para_input($registro['end_date'])); ?>">
        </div>
    </div>

    <div class="field-row">
        <div class="field">
            <label for="location">Local</label>
            <input class="input" type="text" id="location" name="location" value="<?= e($registro['location']); ?>">
        </div>

        <div class="field">
            <label for="status_id">Situação</label>
            <select class="input" id="status_id" name="status_id" required>
                <option value="">Selecione</option>
                <?php foreach ($statuses as $status): ?>
                    <option value="<?= (int) $status['id']; ?>"
                        <?= (int) $registro['status_id'] === (int) $status['id'] ? 'selected' : ''; ?>>
                        <?= e($status['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="field">
        <label for="registration_link">Link de inscrição (opcional)</label>
        <input class="input" type="text" id="registration_link" name="registration_link"
               value="<?= e($registro['registration_link']); ?>">
    </div>

    <div class="form-actions">
        <button type="submit" class="btn">Salvar evento</button>
        <a class="btn-ghost" href="<?= e(url('/admin/eventos')); ?>">Cancelar</a>
    </div>
</form>
