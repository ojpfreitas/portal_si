<?php $tituloPagina = 'Início'; ?>

<section class="hero">
    <div class="hero-inner">
        <h1>Portal de Comunicação — Sistemas de Informação</h1>
        <p>Avisos, notícias e eventos do curso, em um só lugar.</p>
    </div>
</section>

<section class="section">
    <h2 class="section-title">Explore por categoria</h2>

    <div class="category-grid">
        <?php foreach ($categorias as $item): ?>
            <?php if ($item['slug'] === 'home') { continue; } ?>
            <a class="category-card" href="<?= e(url('/categoria/' . $item['slug'])); ?>">
                <?php if (!empty($item['icon'])): ?>
                    <span class="category-card-icon"><i class="bi <?= e($item['icon']); ?>" aria-hidden="true"></i></span>
                <?php endif; ?>
                <span class="category-card-name"><?= e($item['name']); ?></span>
                <?php if (!empty($item['description'])): ?>
                    <span class="category-card-desc"><?= e($item['description']); ?></span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<section class="section">
    <h2 class="section-title">Publicações recentes</h2>

    <?php if (empty($conteudos)): ?>
        <p class="empty-state">Nenhuma publicação disponível no momento.</p>
    <?php else: ?>
        <div class="cards-grid">
            <?php foreach ($conteudos as $item): ?>
                <article class="public-card">
                    <?php if (verdadeiro($item['featured'])): ?>
                        <span class="badge badge-info">Destaque</span>
                    <?php endif; ?>
                    <span class="public-card-type"><?= e($item['type_name']); ?></span>
                    <h3><?= e($item['title']); ?></h3>
                    <p><?= e($item['summary']); ?></p>
                    <span class="public-card-date"><?= e(formatar_data($item['published_at'], 'd/m/Y')); ?></span>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="section" id="eventos">
    <h2 class="section-title">Próximos eventos</h2>

    <?php if (empty($eventos)): ?>
        <p class="empty-state">Nenhum evento agendado no momento.</p>
    <?php else: ?>
        <div class="cards-grid">
            <?php foreach ($eventos as $item): ?>
                <article class="public-card">
                    <span class="public-card-date"><?= e(formatar_data($item['start_date'], 'd/m/Y \à\s H:i')); ?></span>
                    <h3><?= e($item['title']); ?></h3>
                    <p><?= e($item['location'] ?: 'Local a definir'); ?></p>
                    <?php if (!empty($item['registration_link'])): ?>
                        <a class="link-action" href="<?= e($item['registration_link']); ?>" target="_blank" rel="noopener">Inscreva-se</a>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
