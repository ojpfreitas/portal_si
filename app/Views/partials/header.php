<?php

/**
 * app/Views/partials/header.php
 * Cabeçalho do site público: logo, menu com as categorias de publicação
 * (consultadas direto do banco) e o link de acesso ao painel.
 * As categorias ficam dentro de um dropdown "Categorias" (clique para abrir),
 * para não lotar a tela por mais categorias que existam no banco. Em telas
 * estreitas, o menu inteiro ainda recolhe num botão "hambúrguer". As duas
 * coisas são só HTML/CSS — o dropdown usa <details>/<summary> nativos e o
 * hambúrguer usa um <input type="checkbox"> oculto; nenhuma linha de JS.
 * Incluído pelo layout publico.php com require_once.
 */

use App\Models\Categoria;

$categoriasMenu = (new Categoria())->ativas();

?>
<header class="site-header">
    <div class="site-header-inner">

        <a class="logo" href="<?= e(url('/')); ?>">
            <span class="logo-mark logo-mark-light">SI</span>
            Portal SI
        </a>

        <input type="checkbox" id="menu-toggle" class="menu-toggle">
        <label for="menu-toggle" class="menu-btn" aria-label="Abrir menu" aria-controls="site-menu">
            <span></span>
            <span></span>
            <span></span>
        </label>

        <div class="site-menu" id="site-menu">
            <nav aria-label="Menu principal">
                <a class="nav-link" href="<?= e(url('/')); ?>">Início</a>

                <details class="nav-dropdown">
                    <summary>
                        Categorias
                        <svg class="nav-dropdown-chevron" viewBox="0 0 12 8" aria-hidden="true">
                            <path d="M1 1l5 5 5-5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </summary>

                    <div class="nav-dropdown-panel">
                        <?php if (empty($categoriasMenu)): ?>
                            <span class="nav-dropdown-empty">Nenhuma categoria cadastrada ainda.</span>
                        <?php endif; ?>

                        <?php foreach ($categoriasMenu as $categoria): ?>
                            <?php
                                // Cada categoria tem a sua tela em /categoria/{slug};
                                // só a "Home" aponta para a página inicial.
                                $destino = $categoria['slug'] === 'home'
                                    ? url('/')
                                    : url('/categoria/' . $categoria['slug']);
                            ?>
                            <a href="<?= e($destino); ?>"><?= e($categoria['name']); ?></a>
                        <?php endforeach; ?>
                    </div>
                </details>
            </nav>

            <a class="btn-ghost" href="<?= e(url('/login')); ?>">Entrar</a>
        </div>

    </div>
</header>
