<?php
$tituloPagina = 'Usuários';
use App\Core\Auth;
?>

<div class="page-head">
    <div>
        <h1>Administração de usuários</h1>
        <p class="lead">Contas com acesso ao painel do Portal SI.</p>
    </div>

    <?php if (Auth::podeGerenciarUsuarios()): ?>
        <a class="btn btn-inline" href="<?= e(url('/admin/usuarios/novo')); ?>">Novo usuário</a>
    <?php endif; ?>
</div>

<?php if (!Auth::podeGerenciarUsuarios()): ?>
    <div class="alert alert-info" role="status">Seu perfil tem acesso somente de visualização a esta página.</div>
<?php endif; ?>

<form class="filters" action="<?= e(url('/admin/usuarios')); ?>" method="GET">
    <input class="input" type="text" name="busca" placeholder="Buscar por nome ou e-mail"
           value="<?= e($filtros['busca']); ?>">

    <select class="input" name="perfil">
        <option value="">Todos os perfis</option>
        <?php foreach ($perfis as $perfil): ?>
            <option value="<?= (int) $perfil['id']; ?>" <?= $filtros['perfil'] === (int) $perfil['id'] ? 'selected' : ''; ?>>
                <?= e($perfil['name']); ?>
            </option>
        <?php endforeach; ?>
    </select>

    <select class="input" name="situacao">
        <option value="">Ativos e inativos</option>
        <option value="ativo" <?= $filtros['situacao'] === 'ativo' ? 'selected' : ''; ?>>Somente ativos</option>
        <option value="inativo" <?= $filtros['situacao'] === 'inativo' ? 'selected' : ''; ?>>Somente inativos</option>
    </select>

    <button type="submit" class="btn btn-secondary">Filtrar</button>
</form>

<?php if (empty($usuarios)): ?>

    <p class="empty-state">Nenhum usuário encontrado com os filtros atuais.</p>

<?php else: ?>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th>Perfil</th>
                    <th>Situação</th>
                    <th>Desde</th>
                    <?php if (Auth::podeGerenciarUsuarios()): ?>
                        <th class="col-actions">Ações</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $item): ?>
                    <tr>
                        <td><?= e($item['name']); ?></td>
                        <td><?= e($item['email']); ?></td>
                        <td><?= e($item['profile_name']); ?></td>
                        <td>
                            <span class="badge <?= verdadeiro($item['status']) ? 'badge-success' : 'badge-neutral'; ?>">
                                <?= verdadeiro($item['status']) ? 'Ativo' : 'Inativo'; ?>
                            </span>
                        </td>
                        <td><?= e(formatar_data($item['created_at'], 'd/m/Y')); ?></td>
                        <?php if (Auth::podeGerenciarUsuarios()): ?>
                            <td class="col-actions">
                                <a class="link-action" href="<?= e(url('/admin/usuarios/' . $item['id'])); ?>">Editar</a>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

<?php endif; ?>
