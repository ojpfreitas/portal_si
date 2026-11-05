<?php
$tituloPagina = 'Publicações';
use App\Core\Auth;
?>

<div class="page-head">
    <div>
        <h1>Publicações e notícias</h1>
        <p class="lead">Avisos, notícias e páginas de conteúdo exibidos no site do curso.</p>
    </div>

    <?php if (Auth::podeGerenciarConteudo()): ?>
        <a class="btn btn-inline" href="<?= e(url('/admin/conteudos/novo')); ?>">Nova publicação</a>
    <?php endif; ?>
</div>

<form class="filters" action="<?= e(url('/admin/conteudos')); ?>" method="GET">
    <input class="input" type="text" name="busca" placeholder="Buscar por título"
           value="<?= e($filtros['busca']); ?>">

    <select class="input" name="categoria">
        <option value="">Todas as categorias</option>
        <?php foreach ($categorias as $categoria): ?>
            <option value="<?= (int) $categoria['id']; ?>" <?= $filtros['categoria'] === (int) $categoria['id'] ? 'selected' : ''; ?>>
                <?= e($categoria['name']); ?>
            </option>
        <?php endforeach; ?>
    </select>

    <select class="input" name="status">
        <option value="">Todas as situações</option>
        <?php foreach ($statuses as $status): ?>
            <option value="<?= (int) $status['id']; ?>" <?= $filtros['status'] === (int) $status['id'] ? 'selected' : ''; ?>>
                <?= e($status['name']); ?>
            </option>
        <?php endforeach; ?>
    </select>

    <button type="submit" class="btn btn-secondary">Filtrar</button>
</form>

<?php if (empty($conteudos)): ?>

    <p class="empty-state">Nenhuma publicação encontrada com os filtros atuais.</p>

<?php else: ?>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Título</th>
                    <th>Categoria</th>
                    <th>Situação</th>
                    <th>Autor</th>
                    <th>Publicado em</th>
                    <th class="col-actions">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($conteudos as $item): ?>
                    <tr>
                        <td>
                            <?= e($item['title']); ?>
                            <?php if (verdadeiro($item['featured'])): ?>
                                <span class="badge badge-info">Destaque</span>
                            <?php endif; ?>
                        </td>
                        <td><?= e($item['type_name']); ?></td>
                        <td><span class="badge <?= classe_status($item['status_name']); ?>"><?= e($item['status_name']); ?></span></td>
                        <td><?= e($item['author_name']); ?></td>
                        <td><?= e(formatar_data($item['published_at'], 'd/m/Y')); ?></td>
                        <td class="col-actions">
                            <?php if (Auth::podeGerenciarConteudo()): ?>
                                <a class="link-action" href="<?= e(url('/admin/conteudos/' . $item['id'])); ?>">Editar</a>
                            <?php endif; ?>

                            <?php if (Auth::podeExcluirConteudo()): ?>
                                <a class="link-action link-danger" href="<?= e(url('/admin/conteudos/' . $item['id'] . '/excluir')); ?>">Excluir</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

<?php endif; ?>
