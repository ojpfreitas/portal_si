<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Evento;

/**
 * Calendário de eventos. Mesmas regras de acesso dos conteúdos.
 */
class EventoController extends Controller
{
    public function index(): void
    {
        Auth::exigirLogin();

        $modelo = new Evento();

        $filtros = [
            'busca'  => texto_de($_GET, 'busca'),
            'status' => inteiro_de($_GET, 'status'),
        ];

        $this->view('eventos/index', [
            'eventos'  => $modelo->listar($filtros),
            'statuses' => $modelo->statuses(),
            'filtros'  => $filtros,
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

        $registro = (new Evento())->buscar($id);

        if ($registro === null) {
            flash('erro', 'Evento não encontrado.');
            redirecionar('/admin/eventos');
        }

        $this->formulario($registro);
    }

    public function store(): void
    {
        Auth::exigirPerfis([Auth::PERFIL_DIVULGACAO, Auth::PERFIL_ADMIN]);

        [$dados, $erros] = $this->validar($_POST);

        if (!empty($erros)) {
            $this->formulario(array_merge(['id' => null], $_POST), $erros);
            return;
        }

        (new Evento())->criar($dados, Auth::id());

        flash('sucesso', 'Evento criado com sucesso.');
        redirecionar('/admin/eventos');
    }

    public function update(int $id): void
    {
        Auth::exigirPerfis([Auth::PERFIL_DIVULGACAO, Auth::PERFIL_ADMIN]);

        [$dados, $erros] = $this->validar($_POST);

        if (!empty($erros)) {
            $this->formulario(array_merge(['id' => $id], $_POST), $erros);
            return;
        }

        (new Evento())->atualizar($id, $dados);

        flash('sucesso', 'Evento atualizado com sucesso.');
        redirecionar('/admin/eventos');
    }

    public function confirmarExclusao(int $id): void
    {
        Auth::exigirPerfis([Auth::PERFIL_ADMIN]);

        $registro = (new Evento())->buscar($id);

        if ($registro === null) {
            flash('erro', 'Evento não encontrado.');
            redirecionar('/admin/eventos');
        }

        $this->view('shared/confirmar-exclusao', [
            'tituloPagina' => 'Excluir evento',
            'mensagem'     => 'Tem certeza que deseja excluir o evento "' . $registro['title'] . '"? Essa ação não pode ser desfeita.',
            'acaoForm'     => url('/admin/eventos/' . $id . '/excluir'),
            'urlCancelar'  => url('/admin/eventos'),
        ], 'admin');
    }

    public function destroy(int $id): void
    {
        Auth::exigirPerfis([Auth::PERFIL_ADMIN]);

        (new Evento())->excluir($id);

        flash('sucesso', 'Evento excluído.');
        redirecionar('/admin/eventos');
    }

    private function formulario(array $registro = [], array $erros = []): void
    {
        $padrao = [
            'id' => null, 'title' => '', 'description' => '',
            'start_date' => '', 'end_date' => '', 'location' => '',
            'registration_link' => '', 'status_id' => '',
        ];

        $this->view('eventos/form', [
            'registro' => array_merge($padrao, $registro),
            'erros'    => $erros,
            'statuses' => (new Evento())->statuses(),
        ], 'admin');
    }

    /** @return array{0: array<string,mixed>, 1: string[]} */
    private function validar(array $entrada): array
    {
        $inicio = data_para_banco(texto_de($entrada, 'start_date'));
        $fimTxt = texto_de($entrada, 'end_date');
        $fim    = $fimTxt !== '' ? data_para_banco($fimTxt) : null;

        $dados = [
            'title'              => texto_de($entrada, 'title'),
            'description'        => texto_de($entrada, 'description'),
            'start_date'         => $inicio,
            'end_date'           => $fim,
            'location'           => texto_de($entrada, 'location'),
            'registration_link'  => texto_de($entrada, 'registration_link'),
            'status_id'          => inteiro_de($entrada, 'status_id'),
        ];

        $erros = [];

        if (mb_strlen($dados['title']) < 5) {
            $erros[] = 'O título deve ter ao menos 5 caracteres.';
        }

        if ($inicio === null) {
            $erros[] = 'Informe uma data de início válida.';
        }

        if ($fimTxt !== '' && $fim === null) {
            $erros[] = 'A data de término informada é inválida.';
        }

        if ($inicio !== null && $fim !== null && $fim < $inicio) {
            $erros[] = 'A data de término não pode ser anterior à data de início.';
        }

        if ($dados['status_id'] <= 0) {
            $erros[] = 'Selecione uma situação.';
        }

        return [$dados, $erros];
    }
}
