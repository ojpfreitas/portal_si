<?php

/**
 * app/Views/partials/footer.php
 * Rodapé do portal, compartilhado pelo site público, pelo login/cadastro
 * e pelo painel administrativo. Incluído com require_once pelos três layouts.
 */

use App\Core\Auth;

$anoAtual = date('Y');

?>
<footer class="site-footer">
    <div class="site-footer-inner">

        <div class="footer-brand">
            <a class="logo" href="<?= e(url('/')); ?>">
                <span class="logo-mark">SI</span>
                Portal SI
            </a>
            <p class="footer-tagline">
                Avisos, eventos e notícias do curso de Sistemas de Informação,
                organizados em um só lugar — feito por quem vive o curso.
            </p>
        </div>

        <nav class="footer-col" aria-label="Acesso">
            <h3>Acesso</h3>
            <a href="<?= e(url('/')); ?>">Início</a>
            <a href="<?= e(url('/')); ?>#eventos">Eventos</a>
            <?php if (Auth::logado()): ?>
                <a href="<?= e(url('/admin')); ?>">Painel</a>
                <a href="<?= e(url('/logout')); ?>">Sair</a>
            <?php else: ?>
                <a href="<?= e(url('/login')); ?>">Entrar</a>
                <a href="<?= e(url('/cadastroUsuario')); ?>">Criar conta</a>
            <?php endif; ?>
        </nav>

        <div class="footer-col">
            <h3>Sistemas de Informação</h3>
            <p class="footer-note">Construído em PHP, orientado a objetos e a muito café. ☕</p>
        </div>

    </div>

    <div class="site-footer-bottom">
        <span>&copy; <?= e($anoAtual); ?> Portal SI — Todos os direitos reservados.</span>
        <a class="footer-top-link" href="#topo">Voltar ao topo ↑</a>
    </div>
</footer>
