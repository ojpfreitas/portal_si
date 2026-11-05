<?php

// public/install/index.php
// Assistente de instalação do Portal SI (roda no navegador).
//
// Segurança, em resumo:
//   - só funciona enquanto o portal NÃO estiver instalado (config/install.lock
//     ou um Administrador já existente no banco configurado trancam tudo);
//   - fora do localhost, exige a chave que o instalador grava em config/install.key
//     (prova de que quem instala tem acesso ao servidor);
//   - todo POST exige token CSRF; a sessão do instalador é separada da do portal;
//   - depois de instalar, a pasta public/install pode ser apagada pelo próprio assistente.

require dirname(__DIR__, 2) . '/app/Install/Instalador.php';

use App\Install\InstalacaoException;
use App\Install\Instalador;

header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header(
    "Content-Security-Policy: default-src 'none'; style-src 'self' https://fonts.googleapis.com; "
    . "font-src https://fonts.gstatic.com; img-src 'self' data:; form-action 'self'; "
    . "base-uri 'none'; frame-ancestors 'none'"
);

date_default_timezone_set('America/Sao_Paulo');

// Sessão própria do instalador (não se mistura com a do portal)
session_name('portal_si_install');
session_set_cookie_params([
    'path'     => rtrim(dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/')), '/') . '/',
    'httponly' => true,
    'samesite' => 'Strict',
    'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
]);
session_start();

$pastaViews = dirname(__DIR__, 2) . '/app/Install/Views';

// ---------- Funções de apoio ----------

function h($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function tokenCsrf(): string
{
    if (empty($_SESSION['inst_csrf'])) {
        $_SESSION['inst_csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['inst_csrf'];
}

function campoCsrf(): string
{
    return '<input type="hidden" name="csrf" value="' . h(tokenCsrf()) . '">';
}

function irPara(string $etapa): void
{
    header('Location: ?etapa=' . $etapa);
    exit;
}

/** Desenha uma página do assistente e encerra */
function pagina(string $etapa, array $variaveis = [], int $status = 200): void
{
    global $pastaViews;

    http_response_code($status);
    extract($variaveis, EXTR_SKIP);

    $arquivo = $pastaViews . '/' . $etapa . '.php';

    require $pastaViews . '/layout.php';
    exit;
}

// ---------- Fluxo ----------

$inst   = new Instalador();
$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$etapa  = (string) ($_GET['etapa'] ?? '');
$acao   = (string) ($_POST['acao'] ?? '');

// Todo POST precisa do token CSRF
if ($metodo === 'POST') {
    $enviado = $_POST['csrf'] ?? '';

    if (!is_string($enviado) || !hash_equals(tokenCsrf(), $enviado)) {
        pagina('aviso', [
            'titulo'    => 'Requisição inválida',
            'mensagem'  => 'A sessão expirou ou o formulário não veio desta página. Volte ao início e tente de novo.',
            'urlPortal' => null,
        ], 403);
    }
}

// Resumo de uma instalação que já não existe (lock apagado para reinstalar): descarta
if (!empty($_SESSION['inst_concluido']) && !$inst->resultadoValido((array) ($_SESSION['inst_resultado'] ?? []))) {
    unset($_SESSION['inst_concluido'], $_SESSION['inst_resultado']);
}

// 1) Instalação concluída nesta sessão: mostra o resumo e oferece remover o instalador
if (!empty($_SESSION['inst_concluido'])) {
    $avisoRemocao = null;

    if ($metodo === 'POST' && $acao === 'remover') {
        if ($inst->removerInstalador()) {
            unset($_SESSION['inst_concluido'], $_SESSION['inst_resultado']);
            header('Location: ' . $inst->baseUrlAtual() . '/login');
            exit;
        }

        $restos = implode(', ', $inst->restosDoInstalador());

        $avisoRemocao = 'Não consegui apagar tudo de public/install'
            . ($restos !== '' ? ' (restou: ' . $restos . ')' : '')
            . '. Apague a pasta manualmente. O instalador já está trancado, então não há risco enquanto isso.';
    }

    pagina('resultado', [
        'resultado'    => $_SESSION['inst_resultado'] ?? [],
        'urlPortal'    => $inst->baseUrlAtual(),
        'avisoRemocao' => $avisoRemocao,
    ]);
}

// 2) Já instalado? Então o instalador fica trancado
$motivo = $inst->bloqueio();

if ($motivo !== null) {
    pagina('aviso', [
        'titulo'    => 'Instalação já concluída',
        'mensagem'  => $motivo,
        'urlPortal' => $inst->baseUrlAtual(),
    ], 403);
}

// 3) Fora do localhost: exige a chave de config/install.key
if (!$inst->acessoLocal() && empty($_SESSION['inst_auth'])) {
    $chaveOk = $inst->prepararChave();
    $erroChave = null;

    if ($metodo === 'POST' && $acao === 'chave') {
        if ($chaveOk && $inst->chaveConfere((string) ($_POST['chave'] ?? ''))) {
            session_regenerate_id(true);
            $_SESSION['inst_auth'] = true;
            irPara('requisitos');
        }

        usleep(700000); // freia tentativas em sequência
        $erroChave = 'Chave incorreta.';
    }

    pagina('chave', ['chaveOk' => $chaveOk, 'erroChave' => $erroChave]);
}

// 4) Etapas do assistente
$requisitos   = $inst->requisitos();
$requisitosOk = $inst->requisitosOk($requisitos);

if ($etapa === '' || $etapa === 'requisitos') {
    if ($metodo === 'POST' && $requisitosOk) {
        irPara('banco');
    }

    pagina('requisitos', ['requisitos' => $requisitos, 'requisitosOk' => $requisitosOk]);
}

if (!$requisitosOk) {
    irPara('requisitos');
}

if ($etapa === 'banco') {
    $erros   = [];
    $perfis  = $inst->perfisBanco();
    $valores = $_SESSION['inst_banco'] ?? ['perfil' => (string) array_key_first($perfis), 'user' => '', 'pass' => ''];

    if ($perfis === []) {
        $erros[] = 'Nenhum banco configurado. Verifique o arquivo config/bancos.php.';
    }

    if (!empty($_SESSION['inst_aviso'])) {
        $erros[] = $_SESSION['inst_aviso'];
        unset($_SESSION['inst_aviso']);
    }

    if ($metodo === 'POST') {
        $chave  = (string) ($_POST['perfil'] ?? '');
        $perfil = $perfis[$chave] ?? null;

        if ($perfil === null) {
            $erros[] = 'Escolha um dos bancos da lista.';
        } else {
            $valores = $inst->normalizarBanco([
                'host'  => $perfil['host'],
                'port'  => $perfil['port'],
                'name'  => $perfil['name'],
                'user'  => $_POST['user'] ?? '',
                'pass'  => $_POST['pass'] ?? '',
                'criar' => $perfil['criar_banco'],
                'sslmode'   => $perfil['sslmode'] ?? 'prefer',
                'atualizar' => !empty($perfil['somente_atualizar']),
            ]) + ['perfil' => $chave];
            $erros = $inst->errosBanco($valores);
        }

        if ($erros === []) {
            try {
                $diag     = $inst->diagnosticar($valores);
                $problema = $inst->impedimento($valores, $diag);

                if ($problema !== null) {
                    $erros[] = $problema;
                }
            } catch (InstalacaoException $e) {
                $erros[] = $e->getMessage();
            }
        }

        // Perfil "somente atualizar" (ex.: Aiven): aplica as migrações, grava a conexão e
        // vai direto ao resumo, sem a etapa do Administrador. A senha do banco não fica na sessão.
        if ($erros === [] && !empty($valores['atualizar'])) {
            try {
                $resultado = $inst->configurarEAtualizar($valores, $inst->baseUrlAtual());

                $_SESSION['inst_concluido'] = true;
                $_SESSION['inst_resultado'] = $resultado;
                unset($_SESSION['inst_banco']);
                session_regenerate_id(true);

                irPara('resultado');
            } catch (InstalacaoException $e) {
                $erros[] = $e->getMessage();
            }
        }

        if ($erros === []) {
            $_SESSION['inst_banco'] = $valores;
            irPara('admin');
        }
    }

    pagina('banco', ['erros' => $erros, 'valores' => $valores, 'perfis' => $perfis]);
}

if ($etapa === 'admin') {
    $banco = $_SESSION['inst_banco'] ?? null;

    if (!is_array($banco)) {
        irPara('banco');
    }

    // Confere de novo o banco (ele pode ter mudado desde a etapa anterior)
    try {
        $diag     = $inst->diagnosticar($banco);
        $problema = $inst->impedimento($banco, $diag);

        if ($problema !== null) {
            throw new InstalacaoException($problema);
        }
    } catch (InstalacaoException $e) {
        $_SESSION['inst_aviso'] = $e->getMessage();
        irPara('banco');
    }

    $erros   = [];
    $valores = ['nome' => '', 'email' => '', 'base_url' => $inst->baseUrlAtual() !== '' ? $inst->baseUrlAtual() : $inst->detectarBaseUrl()];

    if ($metodo === 'POST') {
        $admin = [
            'nome'  => trim((string) ($_POST['nome'] ?? '')),
            'email' => trim((string) ($_POST['email'] ?? '')),
            'senha' => (string) ($_POST['senha'] ?? ''),
            'senha2' => (string) ($_POST['senha2'] ?? ''),
        ];
        $baseUrl = $inst->normalizarBaseUrl((string) ($_POST['base_url'] ?? ''));

        $valores = ['nome' => $admin['nome'], 'email' => $admin['email'], 'base_url' => $baseUrl];
        $erros   = array_merge($inst->errosAdmin($admin), $inst->errosBaseUrl($baseUrl));

        if ($erros === []) {
            try {
                $resultado = $inst->instalar($banco, $admin, $baseUrl);

                $_SESSION['inst_concluido'] = true;
                $_SESSION['inst_resultado'] = $resultado;
                unset($_SESSION['inst_banco']);
                session_regenerate_id(true);

                irPara('resultado');
            } catch (InstalacaoException $e) {
                $erros[] = $e->getMessage();
            }
        }
    }

    pagina('admin', ['erros' => $erros, 'valores' => $valores, 'banco' => $banco, 'diag' => $diag]);
}

irPara('requisitos');