<?php

namespace App\Core;

use App\Controllers\ErroController;

/**
 * Roteador: associa método HTTP + caminho a um método de controller.
 * Parâmetros aceitos no caminho: {id} (número) e {slug} (texto-com-hifens).
 */
class Router
{
    private array $rotas = [];

    public function get(string $caminho, array $acao): void
    {
        $this->adicionar('GET', $caminho, $acao);
    }

    public function post(string $caminho, array $acao): void
    {
        $this->adicionar('POST', $caminho, $acao);
    }

    private function adicionar(string $metodo, string $caminho, array $acao): void
    {
        // {id} aceita somente números; {slug} aceita letras minúsculas, números e hífen
        $padrao = '#^' . str_replace(['{id}', '{slug}'], ['(\d+)', '([a-z0-9-]+)'], $caminho) . '$#';

        // Ordem dos parâmetros do caminho, para saber o que converter em número
        preg_match_all('/\{(id|slug)\}/', $caminho, $parametros);

        $this->rotas[] = [
            'metodo'     => $metodo,
            'padrao'     => $padrao,
            'parametros' => $parametros[1],
            'acao'       => $acao,
        ];
    }

    /** Caminho da requisição sem a pasta base, ex.: /admin/conteudos/5 */
    public static function caminhoAtual(): string
    {
        $uri  = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $base = base_path();

        if ($base !== '' && strpos($uri, $base) === 0) {
            $uri = substr($uri, strlen($base));
        }

        return '/' . trim($uri, '/');
    }

    public function despachar(): void
    {
        $metodo  = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $caminho = self::caminhoAtual();

        // Envio maior que post_max_size: o PHP descarta o corpo inteiro (POST e arquivos)
        if ($metodo === 'POST' && empty($_POST) && empty($_FILES) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
            (new ErroController())->arquivoGrande();
            return;
        }

        // Todo POST precisa carregar um token CSRF válido
        if ($metodo === 'POST' && !Csrf::validar()) {
            (new ErroController())->proibido();
            return;
        }

        foreach ($this->rotas as $rota) {
            if ($rota['metodo'] !== $metodo) {
                continue;
            }

            if (preg_match($rota['padrao'], $caminho, $encontrados)) {
                array_shift($encontrados);

                [$classe, $acao] = $rota['acao'];

                // {id} chega como int; {slug} continua texto
                foreach ($rota['parametros'] as $posicao => $tipo) {
                    if ($tipo === 'id') {
                        $encontrados[$posicao] = (int) $encontrados[$posicao];
                    }
                }

                $controller = new $classe();
                $controller->$acao(...$encontrados);

                return;
            }
        }

        (new ErroController())->naoEncontrado();
    }
}
