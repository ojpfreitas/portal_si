<?php
$tituloPagina = 'Painel';
use App\Core\Auth;
?>

<h1>Olá, <?= e(explode(' ', Auth::nome())[0] ?? Auth::nome()); ?></h1>
<p class="lead">Escolha um módulo para gerenciar o conteúdo e as atividades do curso.</p>

<h2 class="section-title">Módulos de gestão do curso</h2>

<ul class="modules">

    <li>
        <a class="module" href="<?= e(url('/admin/conteudos')); ?>">
            <span class="module-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M6 3h9l4 4v14H6z" />
                    <path d="M14 3v5h5" />
                    <path d="M9 13h7M9 17h7" />
                </svg>
            </span>
            <span class="module-title">Publicações e notícias</span>
            <span class="module-desc">Crie, edite e publique avisos e notícias para os alunos do curso.</span>
        </a>
    </li>

    <li>
        <a class="module" href="<?= e(url('/admin/eventos')); ?>">
            <span class="module-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="4" y="5" width="16" height="15" rx="2" />
                    <path d="M4 10h16M9 3v4M15 3v4" />
                </svg>
            </span>
            <span class="module-title">Calendário de eventos</span>
            <span class="module-desc">Organize datas de palestras, prazos e demais eventos do curso.</span>
        </a>
    </li>

    <?php if (Auth::podeVerUsuarios()): ?>

        <li>
            <a class="module" href="<?= e(url('/admin/usuarios')); ?>">
                <span class="module-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="9" cy="8" r="3.5" />
                        <path d="M2.5 20c0-3.6 2.9-6 6.5-6s6.5 2.4 6.5 6" />
                        <path d="M16 5.2a3.4 3.4 0 0 1 0 5.6M18 14.4c2.2.7 3.5 2.6 3.5 5.6" />
                    </svg>
                </span>
                <span class="module-title">Administração de usuários</span>
                <span class="module-desc">
                    <?= Auth::podeGerenciarUsuarios()
                        ? 'Gerencie contas, perfis de acesso e usuários ativos do portal.'
                        : 'Consulte as contas e perfis de acesso cadastrados no portal.'; ?>
                </span>
                <span class="module-tag">
                    <?= Auth::podeGerenciarUsuarios() ? 'Somente administradores' : 'Somente visualização'; ?>
                </span>
            </a>
        </li>

    <?php endif; ?>

</ul>
