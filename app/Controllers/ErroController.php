<?php

namespace App\Controllers;

use App\Core\Controller;

/**
 * Páginas de erro (404 e requisição sem token CSRF válido).
 */
class ErroController extends Controller
{
    public function naoEncontrado(): void
    {
        http_response_code(404);
        $this->view('errors/404', [], 'vazio');
    }

    public function proibido(): void
    {
        http_response_code(419);
        $this->view('errors/419', [], 'vazio');
    }

    public function arquivoGrande(): void
    {
        http_response_code(413);
        $this->view('errors/413', [], 'vazio');
    }
}
