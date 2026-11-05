<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Usuario;

/**
 * Cadastro, login e logout.
 */
class AuthController extends Controller
{
    public function loginForm(): void
    {
        if (Auth::logado()) {
            redirecionar('/admin');
        }

        $this->view('auth/login', ['email' => ''], 'auth');
    }

    public function login(): void
    {
        $email = texto_de($_POST, 'email');
        $senha = $_POST['senha'] ?? '';

        if ($email === '' || $senha === '') {
            flash('erro', 'Preencha todos os campos para prosseguir.');
            $this->view('auth/login', ['email' => $email], 'auth');
            return;
        }

        $usuario = (new Usuario())->buscarAtivoPorEmail($email);

        // Mensagem intencionalmente genérica, para evitar enumeração de contas
        if (!$usuario || !password_verify($senha, $usuario['password_hash'])) {
            flash('erro', 'E-mail ou senha inválidos.');
            $this->view('auth/login', ['email' => $email], 'auth');
            return;
        }

        Auth::entrar($usuario);
        redirecionar('/admin');
    }

    public function cadastroForm(): void
    {
        if (Auth::logado()) {
            redirecionar('/admin');
        }

        $this->view('auth/cadastro', ['nome' => '', 'email' => ''], 'auth');
    }

    public function cadastro(): void
    {
        $nome              = texto_de($_POST, 'nome');
        $email             = texto_de($_POST, 'email');
        $senha             = $_POST['senha'] ?? '';
        $senhaConfirmacao  = $_POST['senha_confirmacao'] ?? '';

        $erros = [];

        if (mb_strlen($nome) < 3) {
            $erros[] = 'O nome deve conter ao menos 3 caracteres.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erros[] = 'Informe um endereço de e-mail válido.';
        }

        if (mb_strlen($senha) < 8) {
            $erros[] = 'A senha deve conter no mínimo 8 caracteres.';
        }

        if ($senha !== $senhaConfirmacao) {
            $erros[] = 'A confirmação de senha não confere com a senha informada.';
        }

        if (!empty($erros)) {
            foreach ($erros as $erro) {
                flash('erro', $erro);
            }

            $this->view('auth/cadastro', ['nome' => $nome, 'email' => $email], 'auth');
            return;
        }

        $usuarioModel = new Usuario();

        try {
            // false: a conta nasce inativa e só entra em vigor depois que
            // um administrador a aprovar na tela de usuários.
            $usuarioModel->criar(
                $nome,
                $email,
                password_hash($senha, PASSWORD_DEFAULT),
                Auth::PERFIL_CADASTRO_PADRAO,
                false
            );
        } catch (\PDOException $e) {
            // PostgreSQL: violação de UNIQUE (e-mail já cadastrado)
            if ($e->getCode() === '23505') {
                flash('erro', 'O e-mail informado já está cadastrado no sistema.');
            } else {
                error_log('Erro ao cadastrar usuário: ' . $e->getMessage());
                flash('erro', 'Erro interno ao processar cadastro.');
            }

            $this->view('auth/cadastro', ['nome' => $nome, 'email' => $email], 'auth');
            return;
        }

        flash('sucesso', 'Cadastro recebido! Sua conta foi registrada e aguarda a aprovação de um administrador antes do primeiro acesso.');
        redirecionar('/login');
    }

    public function logout(): void
    {
        Auth::sair();
        redirecionar('/login');
    }
}
