<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Categoria;
use App\Models\Conteudo;
use App\Models\Midia;

/**
 * Publicações e notícias. Acesso Total e Administrador veem tudo;
 * Divulgação e Administrador criam, editam e excluem.
 */
class ConteudoController extends Controller
{
    /** Tamanho máximo da imagem de capa: 2 MB */
    private const CAPA_TAMANHO_MAXIMO = 2097152;

    public function index(): void
    {
        Auth::exigirLogin();

        $modelo = new Conteudo();

        $filtros = [
            'busca'     => texto_de($_GET, 'busca'),
            'status'    => inteiro_de($_GET, 'status'),
            'categoria' => inteiro_de($_GET, 'categoria'),
        ];

        $this->view('conteudos/index', [
            'conteudos' => $modelo->listar($filtros),
            'statuses'  => $modelo->statuses(),
            'categorias' => (new Categoria())->ativas(),
            'filtros'   => $filtros,
        ], 'admin');
    }

    public function create(): void
    {
        Auth::exigirPerfis([Auth::PERFIL_DIVULGACAO, Auth::PERFIL_ADMIN]);

        $this->formulario();
    }

    public function edit(int $id): void
    {
        Auth::exigirPerfis([Auth::PERFIL_DIVULGACAO, Auth::PERFIL_ADMIN]);

        $registro = (new Conteudo())->buscar($id);

        if ($registro === null) {
            flash('erro', 'Publicação não encontrada.');
            redirecionar('/admin/conteudos');
        }

        $this->formulario($registro);
    }

    public function store(): void
    {
        Auth::exigirPerfis([Auth::PERFIL_DIVULGACAO, Auth::PERFIL_ADMIN]);

        [$dados, $erros] = $this->validar($_POST);

        if (empty($erros)) {
            [$dados['media_id'], $erros] = $this->receberCapa();
        }

        if (!empty($erros)) {
            $this->formulario(array_merge(['id' => null], $dados), $erros);
            return;
        }

        (new Conteudo())->criar($dados, Auth::id());

        flash('sucesso', 'Publicação criada com sucesso.');
        redirecionar('/admin/conteudos');
    }

    public function update(int $id): void
    {
        Auth::exigirPerfis([Auth::PERFIL_DIVULGACAO, Auth::PERFIL_ADMIN]);

        [$dados, $erros] = $this->validar($_POST);

        if (empty($erros)) {
            [$dados['media_id'], $erros] = $this->receberCapa();
        }

        if (!empty($erros)) {
            $atual = (new Conteudo())->buscar($id);

            $this->formulario(array_merge(['id' => $id, 'cover_path' => $atual['cover_path'] ?? ''], $dados), $erros);
            return;
        }

        (new Conteudo())->atualizar($id, $dados);

        flash('sucesso', 'Publicação atualizada com sucesso.');
        redirecionar('/admin/conteudos');
    }

    public function confirmarExclusao(int $id): void
    {
        Auth::exigirPerfis([Auth::PERFIL_ADMIN]);

        $registro = (new Conteudo())->buscar($id);

        if ($registro === null) {
            flash('erro', 'Publicação não encontrada.');
            redirecionar('/admin/conteudos');
        }

        $this->view('shared/confirmar-exclusao', [
            'tituloPagina' => 'Excluir publicação',
            'mensagem'     => 'Tem certeza que deseja excluir a publicação "' . $registro['title'] . '"? Essa ação não pode ser desfeita.',
            'acaoForm'     => url('/admin/conteudos/' . $id . '/excluir'),
            'urlCancelar'  => url('/admin/conteudos'),
        ], 'admin');
    }

    public function destroy(int $id): void
    {
        Auth::exigirPerfis([Auth::PERFIL_ADMIN]);

        (new Conteudo())->excluir($id);

        flash('sucesso', 'Publicação excluída.');
        redirecionar('/admin/conteudos');
    }

    /** @param array<string,mixed> $registro Já preenchido em erro de validação ou edição */
    private function formulario(array $registro = [], array $erros = []): void
    {
        $modelo = new Conteudo();

        $padrao = [
            'id' => null, 'title' => '', 'summary' => '', 'body' => '',
            'media_id' => null, 'cover_path' => '', 'youtube_link' => '',
            'publication_type_id' => '', 'status_id' => '', 'featured' => false,
        ];

        $this->view('conteudos/form', [
            'registro'   => array_merge($padrao, $registro),
            'erros'      => $erros,
            'statuses'   => $modelo->statuses(),
            'categorias' => (new Categoria())->ativas(),
        ], 'admin');
    }

    /** @return array{0: array<string,mixed>, 1: string[]} */
    private function validar(array $entrada): array
    {
        $dados = [
            'title'                => texto_de($entrada, 'title'),
            'summary'              => texto_de($entrada, 'summary'),
            'body'                 => trim((string) ($entrada['body'] ?? '')),
            'media_id'             => null,
            'youtube_link'         => texto_de($entrada, 'youtube_link'),
            'publication_type_id'  => inteiro_de($entrada, 'publication_type_id'),
            'status_id'            => inteiro_de($entrada, 'status_id'),
            'featured'             => !empty($entrada['featured']),
        ];

        $erros = [];

        if (mb_strlen($dados['title']) < 5) {
            $erros[] = 'O título deve ter ao menos 5 caracteres.';
        }

        if ($dados['body'] === '') {
            $erros[] = 'O conteúdo da publicação não pode ficar vazio.';
        }

        if ($dados['publication_type_id'] <= 0) {
            $erros[] = 'Selecione uma categoria.';
        }

        if ($dados['status_id'] <= 0) {
            $erros[] = 'Selecione uma situação.';
        }

        return [$dados, $erros];
    }

    /**
     * Recebe a imagem de capa enviada no campo "capa".
     *
     * @return array{0: ?int, 1: string[]} id em media (null se não veio arquivo) e erros
     */
    private function receberCapa(): array
    {
        $arquivo = $_FILES['capa'] ?? null;
        $codigo  = (int) ($arquivo['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($arquivo === null || $codigo === UPLOAD_ERR_NO_FILE) {
            return [null, []];
        }

        if ($codigo === UPLOAD_ERR_INI_SIZE || $codigo === UPLOAD_ERR_FORM_SIZE) {
            return [null, ['A imagem de capa deve ter no máximo 2 MB.']];
        }

        if ($codigo !== UPLOAD_ERR_OK
            || !is_string($arquivo['tmp_name'] ?? null)
            || !is_string($arquivo['name'] ?? null)) {
            return [null, ['Não foi possível enviar a imagem de capa. Tente novamente.']];
        }

        if ((int) $arquivo['size'] > self::CAPA_TAMANHO_MAXIMO) {
            return [null, ['A imagem de capa deve ter no máximo 2 MB.']];
        }

        // O tipo vem do conteúdo do arquivo, não do nome nem do tipo informado pelo navegador
        $info  = @getimagesize($arquivo['tmp_name']);
        $mime  = $info['mime'] ?? '';
        $midia = new Midia();
        $tipo  = null;

        foreach ($midia->tiposDeImagem() as $candidato) {
            if ($candidato['name'] === $mime) {
                $tipo = $candidato;
                break;
            }
        }

        if ($tipo === null) {
            return [null, ['A imagem de capa deve ser JPG, PNG ou WebP.']];
        }

        $pasta = BASE_PATH . '/public/uploads';

        if (!is_dir($pasta) && !mkdir($pasta, 0755, true)) {
            return [null, ['Não foi possível salvar a imagem de capa no servidor.']];
        }

        $nome    = bin2hex(random_bytes(16)) . '.' . $tipo['extension'];
        $destino = $pasta . '/' . $nome;

        if (!move_uploaded_file($arquivo['tmp_name'], $destino)) {
            return [null, ['Não foi possível salvar a imagem de capa no servidor.']];
        }

        try {
            $id = $midia->criar(
                mb_substr(basename($arquivo['name']), 0, 255),
                'uploads/' . $nome,
                (int) $tipo['id'],
                (int) $arquivo['size'],
                Auth::id()
            );
        } catch (\Throwable $e) {
            @unlink($destino);
            throw $e;
        }

        return [$id, []];
    }
}
