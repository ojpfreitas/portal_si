<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Perfil;
use App\Models\Usuario;

/**
 * Administração de usuários. Acesso Total só visualiza; só o Administrador
 * cria e edita (a exclusão foi propositalmente omitida: contas são desativadas).
 */
class UsuarioController extends Controller
{
    public function index(): void
    {
        Auth::exigirPerfis([Auth::PERFIL_ACESSO_TOTAL, Auth::PERFIL_ADMIN]);

        $modelo = new Usuario();

        $filtros = [
            'busca'    => texto_de($_GET, 'busca'),
            'perfil'   => inteiro_de($_GET, 'perfil'),
            'situacao' => texto_de($_GET, 'situacao'),
        ];

        $this->view('usuarios/index', [
            'usuarios' => $modelo->listar($filtros),
            'perfis'   => (new Perfil())->ativos(),
            'filtros'  => $filtros,
        ], 'admin');
    }

    public function create(): void
    {
        Auth::exigirPerfis([Auth::PERFIL_ADMIN]);

        $this->formulario();
    }

    public function edit(int $id): void
    {
        Auth::exigirPerfis([Auth::PERFIL_ADMIN]);

        $registro = (new Usuario())->buscar($id);

        if ($registro === null) {
            flash('erro', 'Usuário não encontrado.');
            redirecionar('/admin/usuarios');
        }

        $this->formulario($registro);
    }

    public function store(): void
    {
        Auth::exigirPerfis([Auth::PERFIL_ADMIN]);

        [$dados, $erros] = $this->validar($_POST, null);

        if (!empty($erros)) {
            $this->formulario(array_merge(['id' => null], $_POST, ['status' => !empty($_POST['status'])]), $erros);
            return;
        }

        $modelo = new Usuario();

        try {
            $modelo->criar(
                $dados['name'],
                $dados['email'],
                password_hash($dados['senha'], PASSWORD_DEFAULT),
                $dados['profile_id'],
                $dados['ativo']
            );
        } catch (\PDOException $e) {
            if ($e->getCode() === '23505') {
                $this->formulario(array_merge(['id' => null], $_POST), ['O e-mail informado já está cadastrado.']);
                return;
            }

            throw $e;
        }

        flash('sucesso', 'Usuário criado com sucesso.');
        redirecionar('/admin/usuarios');
    }

    public function update(int $id): void
    {
        Auth::exigirPerfis([Auth::PERFIL_ADMIN]);

        [$dados, $erros] = $this->validar($_POST, $id);

        if (!empty($erros)) {
            $this->formulario(array_merge(['id' => $id], $_POST, ['status' => !empty($_POST['status'])]), $erros);
            return;
        }

        $senhaHash = $dados['senha'] !== '' ? password_hash($dados['senha'], PASSWORD_DEFAULT) : null;

        try {
            (new Usuario())->atualizar($id, $dados, $senhaHash);
        } catch (\PDOException $e) {
            if ($e->getCode() === '23505') {
                $this->formulario(array_merge(['id' => $id], $_POST), ['O e-mail informado já está cadastrado.']);
                return;
            }

            throw $e;
        }

        flash('sucesso', 'Usuário atualizado com sucesso.');
        redirecionar('/admin/usuarios');
    }

    private function formulario(array $registro = [], array $erros = []): void
    {
        $padrao = [
            'id' => null, 'name' => '', 'email' => '',
            'profile_id' => '', 'status' => true,
        ];

        $this->view('usuarios/form', [
            'registro' => array_merge($padrao, $registro),
            'erros'    => $erros,
            'perfis'   => (new Perfil())->ativos(),
        ], 'admin');
    }

    /** @return array{0: array<string,mixed>, 1: string[]} */
    private function validar(array $entrada, ?int $idAtual): array
    {
        $senha            = $entrada['senha'] ?? '';
        $senhaConfirmacao = $entrada['senha_confirmacao'] ?? '';

        $dados = [
            'name'         => texto_de($entrada, 'name'),
            'email'        => texto_de($entrada, 'email'),
            'profile_id' => inteiro_de($entrada, 'profile_id'),
            'ativo'        => !empty($entrada['status']),
            'senha'        => $senha,
        ];

        $erros = [];

        if (mb_strlen($dados['name']) < 3) {
            $erros[] = 'O nome deve conter ao menos 3 caracteres.';
        }

        if (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
            $erros[] = 'Informe um endereço de e-mail válido.';
        }

        if ($dados['profile_id'] <= 0) {
            $erros[] = 'Selecione um perfil.';
        }

        // Ao criar, a senha é obrigatória; ao editar, só é validada se foi preenchida
        $exigirSenha = $idAtual === null;

        if ($exigirSenha || $senha !== '') {
            if (mb_strlen($senha) < 8) {
                $erros[] = 'A senha deve conter no mínimo 8 caracteres.';
            }

            if ($senha !== $senhaConfirmacao) {
                $erros[] = 'A confirmação de senha não confere com a senha informada.';
            }
        }

        return [$dados, $erros];
    }
}
