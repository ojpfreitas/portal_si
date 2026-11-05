<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Categoria;
use App\Models\Conteudo;
use App\Models\Evento;

/**
 * Site público: página inicial com categorias, destaques e próximos eventos.
 */
class HomeController extends Controller
{
    public function index(): void
    {
        $conteudos = (new Conteudo())->publicadas(6);
        $eventos   = (new Evento())->proximos(4);

        $this->view('home/index', [
            'categorias' => (new Categoria())->ativas(),
            'conteudos'  => $conteudos,
            'eventos'    => $eventos,
        ], 'publico');
    }
}
