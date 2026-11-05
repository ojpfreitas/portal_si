<?php

// database/instalar.php
// Instalador pela linha de comando (alternativa ao assistente em /install).
// Faz o mesmo que o assistente web: cria o banco, aplica as migrações, cria o
// primeiro Administrador e grava config/database.php e config/app.php.
//
//   php database/instalar.php                  instalação guiada (pergunta tudo)
//   php database/instalar.php --atualizar      só aplica migrações novas no banco
//                                              configurado (use depois de atualizar o código)
//   php database/instalar.php --ajuda          lista as opções
//
// Dá para passar tudo por opções, sem perguntas (útil em scripts):
//   --host= --porta= --banco= --usuario= --senha-banco=
//   --nome= --email= --senha= --base-url= --nao-criar-banco
//
// Depois de instalado, este script recusa uma nova instalação (config/install.lock),
// mas o --atualizar continua funcionando.

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Este script só pode ser executado pela linha de comando.');
}

require __DIR__ . '/../app/Install/Instalador.php';

use App\Install\InstalacaoException;
use App\Install\Instalador;

function saida(string $texto = ''): void
{
    fwrite(STDOUT, $texto . PHP_EOL);
}

function falha(string $texto): void
{
    fwrite(STDERR, PHP_EOL . 'ERRO: ' . $texto . PHP_EOL);
    exit(1);
}

function perguntar(string $texto, string $padrao = '', bool $secreto = false): string
{
    fwrite(STDOUT, $texto . ($padrao !== '' ? " [$padrao]" : '') . ': ');

    $ocultar = $secreto && DIRECTORY_SEPARATOR === '/' && function_exists('shell_exec');

    if ($ocultar) {
        @shell_exec('stty -echo 2>/dev/null');
    }

    $linha = fgets(STDIN);

    if ($ocultar) {
        @shell_exec('stty echo 2>/dev/null');
        fwrite(STDOUT, PHP_EOL);
    }

    $linha = $linha === false ? '' : rtrim($linha, "\r\n");

    return $linha === '' ? $padrao : $linha;
}

$opcoes = getopt('', [
    'host:', 'porta:', 'banco:', 'usuario:', 'senha-banco:',
    'nome:', 'email:', 'senha:', 'base-url:',
    'nao-criar-banco', 'atualizar', 'ajuda',
]);

if (isset($opcoes['ajuda'])) {
    saida('Uso: php database/instalar.php [--atualizar] [--host= --porta= --banco= --usuario= --senha-banco=]');
    saida('                                [--nome= --email= --senha= --base-url= --nao-criar-banco]');
    exit(0);
}

$inst = new Instalador();
$log  = static function (string $mensagem): void {
    saida('  - ' . $mensagem);
};

// ---------- Só atualizar o banco ----------
if (isset($opcoes['atualizar'])) {
    saida('Aplicando migrações pendentes...');

    try {
        $total = $inst->atualizarExistente($log);
    } catch (InstalacaoException $e) {
        falha($e->getMessage());
    }

    saida($total === 0 ? 'Nada a fazer: o banco já está atualizado.' : "Pronto: $total migração(ões) aplicada(s).");
    exit(0);
}

// ---------- Instalação ----------
saida('Instalador do Portal SI');
saida(str_repeat('-', 40));

$requisitos = $inst->requisitos(false);

foreach ($requisitos as $item) {
    if ($item['nivel'] !== 'ok') {
        saida(($item['nivel'] === 'erro' ? '[ERRO]  ' : '[aviso] ') . $item['nome'] . ': ' . $item['detalhe']);
    }
}

if (!$inst->requisitosOk($requisitos)) {
    falha('Corrija os problemas acima e rode de novo.');
}

if (($motivo = $inst->bloqueio()) !== null) {
    falha($motivo);
}

$banco = $inst->normalizarBanco([
    'host'  => $opcoes['host']        ?? perguntar('Servidor do PostgreSQL', 'localhost'),
    'port'  => $opcoes['porta']       ?? perguntar('Porta', '5432'),
    'name'  => $opcoes['banco']       ?? perguntar('Nome do banco', 'portal_si'),
    'user'  => $opcoes['usuario']     ?? perguntar('Usuário do PostgreSQL', 'postgres'),
    'pass'  => $opcoes['senha-banco'] ?? perguntar('Senha do usuário do PostgreSQL', '', true),
    'criar' => !isset($opcoes['nao-criar-banco']),
]);

$erros = $inst->errosBanco($banco);

if ($erros !== []) {
    falha(implode(PHP_EOL . '      ', $erros));
}

$interativo = !isset($opcoes['senha']);

$admin = [
    'nome'  => trim((string) ($opcoes['nome']  ?? perguntar('Nome do Administrador'))),
    'email' => trim((string) ($opcoes['email'] ?? perguntar('E-mail do Administrador (será o login)'))),
    'senha' => (string) ($opcoes['senha'] ?? perguntar('Senha do Administrador (mín. 8 caracteres)', '', true)),
];

if ($interativo && $admin['senha'] !== perguntar('Confirme a senha', '', true)) {
    falha('A confirmação da senha não confere.');
}

$baseUrl = $inst->normalizarBaseUrl((string) ($opcoes['base-url'] ?? perguntar('Endereço base do portal (vazio = raiz)', $inst->baseUrlAtual())));

$erros = array_merge($inst->errosAdmin($admin), $inst->errosBaseUrl($baseUrl));

if ($erros !== []) {
    falha(implode(PHP_EOL . '      ', $erros));
}

saida();
saida('Instalando...');

try {
    $resultado = $inst->instalar($banco, $admin, $baseUrl, $log);
} catch (InstalacaoException $e) {
    falha($e->getMessage());
}

foreach ($resultado['manual'] as $caminho => $conteudo) {
    saida();
    saida("Sem permissão para gravar $caminho. Crie o arquivo com este conteúdo:");
    saida(str_repeat('-', 40));
    saida($conteudo);
    saida(str_repeat('-', 40));
}

saida();
saida('Instalação concluída! Entre com ' . $resultado['email'] . ' em ' . ($baseUrl === '' ? '/' : $baseUrl . '/') . 'login');

if (!$resultado['lock']) {
    saida('Atenção: não consegui criar config/install.lock. Apague a pasta public/install para ninguém reutilizar o instalador.');
}
