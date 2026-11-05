<?php
/**
 * Tela pública de uma categoria (/categoria/{slug}).
 * Recebe: $categoria, $categorias, $conteudos e, só na categoria "Eventos",
 * $eventosProximos e $eventosRealizados.
 */
$tituloPagina = $categoria['name'];
$temEventos   = isset($eventosProximos);
?>

<section class="hero hero-categoria">
    <div class="hero-inner">
        <nav class="breadcrumb" aria-label="Você está em">
            <a href="<?= e(url('/')); ?>">Início</a>
            <span aria-hidden="true">/</span>
            <span><?= e($categoria['name']); ?></span>
        </nav>

        <h1>
            <?php if (!empty($categoria['icon'])): ?>
                <i class="bi <?= e($categoria['icon']); ?> hero-icon" aria-hidden="true"></i>
            <?php endif; ?>
            <?= e($categoria['name']); ?>
        </h1>

        <?php if (!empty($categoria['description'])): ?>
            <p><?= e($categoria['description']); ?></p>
        <?php endif; ?>
    </div>
</section>

<nav class="category-tabs" aria-label="Categorias">
    <div class="category-tabs-inner">
        <?php foreach ($categorias as $item): ?>
            <?php
                $destino = $item['slug'] === 'home' ? url('/') : url('/categoria/' . $item['slug']);
                $ativa   = $item['slug'] === $categoria['slug'];
            ?>
            <a class="category-tab<?= $ativa ? ' is-active' : ''; ?>"
               href="<?= e($destino); ?>"
               <?= $ativa ? 'aria-current="page"' : ''; ?>>
                <?= e($item['name']); ?>
            </a>
        <?php endforeach; ?>
    </div>
</nav>

<?php if ($temEventos): ?>
    <section class="section">
        <h2 class="section-title">Próximos eventos</h2>

        <?php if (empty($eventosProximos)): ?>
            <p class="empty-state">Nenhum evento agendado no momento.</p>
        <?php else: ?>
            <div class="cards-grid">
                <?php foreach ($eventosProximos as $item): ?>
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

    <?php if (!empty($eventosRealizados)): ?>
        <section class="section section-tight">
            <h2 class="section-title">Eventos anteriores</h2>
            <div class="cards-grid">
                <?php foreach ($eventosRealizados as $item): ?>
                    <article class="public-card">
                        <span class="public-card-date"><?= e(formatar_data($item['start_date'], 'd/m/Y')); ?></span>
                        <h3><?= e($item['title']); ?></h3>
                        <p><?= e($item['location'] ?: 'Local não informado'); ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
<?php endif; ?>

<section class="section<?= $temEventos ? ' section-tight' : ''; ?>">
    <h2 class="section-title">
        <?= $temEventos ? 'Publicações sobre eventos' : 'Publicações'; ?>
    </h2>

    <?php if (empty($conteudos)): ?>
        <p class="empty-state">Ainda não há publicações em <?= e($categoria['name']); ?>.</p>
    <?php else: ?>
        <div class="cards-grid">
            <?php foreach ($conteudos as $item): ?>
                <article class="public-card">
                    <?php if (verdadeiro($item['featured'])): ?>
                        <span class="badge badge-info">Destaque</span>
                    <?php endif; ?>
                    <h3><?= e($item['title']); ?></h3>
                    <p><?= e($item['summary']); ?></p>
                    <span class="public-card-date"><?= e(formatar_data($item['published_at'], 'd/m/Y')); ?></span>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
