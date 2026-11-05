<?php
$tituloPagina = 'Eventos';
use App\Core\Auth;
?>

<div class="page-head">
    <div>
        <h1>Calendário de eventos</h1>
        <p class="lead">Palestras, prazos e demais eventos do curso.</p>
    </div>

    <?php if (Auth::podeGerenciarConteudo()): ?>
        <a class="btn btn-inline" href="<?= e(url('/admin/eventos/novo')); ?>">Novo evento</a>
    <?php endif; ?>
</div>

<form class="filters" action="<?= e(url('/admin/eventos')); ?>" method="GET">
    <input class="input" type="text" name="busca" placeholder="Buscar por título"
           value="<?= e($filtros['busca']); ?>">

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

<?php if (empty($eventos)): ?>

    <p class="empty-state">Nenhum evento encontrado com os filtros atuais.</p>

<?php else: ?>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Título</th>
                    <th>Início</th>
                    <th>Término</th>
                    <th>Local</th>
                    <th>Situação</th>
                    <th>Autor</th>
                    <th class="col-actions">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($eventos as $item): ?>
                    <tr>
                        <td><?= e($item['title']); ?></td>
                        <td><?= e(formatar_data($item['start_date'])); ?></td>
                        <td><?= e(formatar_data($item['end_date'])); ?></td>
                        <td><?= e($item['location'] ?: '—'); ?></td>
                        <td><span class="badge <?= classe_status($item['status_name']); ?>"><?= e($item['status_name']); ?></span></td>
                        <td><?= e($item['author_name']); ?></td>
                        <td class="col-actions">
                            <?php if (Auth::podeGerenciarConteudo()): ?>
                                <a class="link-action" href="<?= e(url('/admin/eventos/' . $item['id'])); ?>">Editar</a>
                            <?php endif; ?>

                            <?php if (Auth::podeExcluirConteudo()): ?>
                                <a class="link-action link-danger" href="<?= e(url('/admin/eventos/' . $item['id'] . '/excluir')); ?>">Excluir</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

<?php endif; ?>
