<?php
$tituloPagina = $registro['id'] ? 'Editar publicação' : 'Nova publicação';
$acaoForm     = $registro['id']
    ? url('/admin/conteudos/' . $registro['id'])
    : url('/admin/conteudos');
?>

<div class="page-head">
    <h1><?= e($tituloPagina); ?></h1>
    <a class="btn-ghost" href="<?= e(url('/admin/conteudos')); ?>">Voltar à lista</a>
</div>

<?php if (!empty($erros)): ?>
    <div class="alert alert-error" role="alert">
        <p class="alert-title">Não foi possível salvar a publicação</p>
        <ul>
            <?php foreach ($erros as $erro): ?>
                <li><?= e($erro); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form action="<?= e($acaoForm); ?>" method="POST" class="form-card" enctype="multipart/form-data" novalidate>
    <?= csrf_campo(); ?>

    <div class="field">
        <label for="title">Título</label>
        <input class="input" type="text" id="title" name="title" value="<?= e($registro['title']); ?>" required>
    </div>

    <div class="field-row">
        <div class="field">
            <label for="publication_type_id">Categoria</label>
            <select class="input" id="publication_type_id" name="publication_type_id" required>
                <option value="">Selecione</option>
                <?php foreach ($categorias as $categoria): ?>
                    <option value="<?= (int) $categoria['id']; ?>"
                        <?= (int) $registro['publication_type_id'] === (int) $categoria['id'] ? 'selected' : ''; ?>>
                        <?= e($categoria['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
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
        <label for="summary">Resumo</label>
        <textarea class="input textarea-sm" id="summary" name="summary" maxlength="500"
                  placeholder="Texto curto exibido nas listagens"><?= e($registro['summary']); ?></textarea>
    </div>

    <div class="field">
        <label for="body">Conteúdo</label>
        <textarea class="input textarea-lg" id="body" name="body" required><?= e($registro['body']); ?></textarea>
    </div>

    <div class="field-row">
        <div class="field">
            <label for="capa">Imagem de capa</label>
            <?php if (!empty($registro['cover_path'])): ?>
                <img src="<?= e(url($registro['cover_path'])); ?>" alt="Capa atual" width="160">
            <?php endif; ?>
            <input class="input" type="file" id="capa" name="capa" accept="image/jpeg,image/png,image/webp">
            <small>JPG, PNG ou WebP, até 2 MB. Envie outro arquivo para trocar a capa.</small>
        </div>

        <div class="field">
            <label for="youtube_link">Vídeo do YouTube (URL)</label>
            <input class="input" type="text" id="youtube_link" name="youtube_link" value="<?= e($registro['youtube_link']); ?>">
        </div>
    </div>

    <label class="checkbox">
        <input type="checkbox" name="featured" value="1" <?= !empty($registro['featured']) ? 'checked' : ''; ?>>
        Exibir em destaque na página inicial
    </label>

    <div class="form-actions">
        <button type="submit" class="btn">Salvar publicação</button>
        <a class="btn-ghost" href="<?= e(url('/admin/conteudos')); ?>">Cancelar</a>
    </div>
</form>
