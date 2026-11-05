<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Categoria;
use App\Models\Conteudo;
use App\Models\Evento;

/**
 * Site público: uma tela para cada categoria de publicação (/categoria/{slug}).
 */
class CategoriaController extends Controller
{
    public function show(string $slug): void
    {
        // "Home" é a própria página inicial, não precisa de tela separada
        if ($slug === 'home') {
            redirecionar('/');
        }

        $modelo    = new Categoria();
        $categoria = $modelo->buscarPorSlug($slug);

        if ($categoria === null) {
            (new ErroController())->naoEncontrado();
            return;
        }

        $dados = [
            'categoria'  => $categoria,
            'categorias' => $modelo->ativas(),
            'conteudos'  => (new Conteudo())->publicadasPorCategoria((int) $categoria['id']),
        ];

        // A categoria "Eventos" também mostra o calendário de eventos
        if ($slug === 'eventos') {
            $eventos = new Evento();

            $dados['eventosProximos']   = $eventos->proximos(12);
            $dados['eventosRealizados'] = $eventos->realizados(6);
        }

        $this->view('categorias/show', $dados, 'publico');
    }
}
