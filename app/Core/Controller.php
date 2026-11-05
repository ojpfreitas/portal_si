<?php

namespace App\Core;

/**
 * Base dos controllers: renderização de views dentro de um layout.
 */
abstract class Controller
{
    /**
     * Renderiza app/Views/{arquivoView}.php dentro de app/Views/layouts/{moldura}.php.
     * As chaves de $variaveis viram variáveis dentro da view e do layout.
     */
    protected function view(string $arquivoView, array $variaveis = [], string $moldura = 'admin'): void
    {
        extract($variaveis, EXTR_SKIP);

        ob_start();
        require APP_PATH . '/Views/' . $arquivoView . '.php';
        $conteudo = ob_get_clean();

        require APP_PATH . '/Views/layouts/' . $moldura . '.php';
    }
}
