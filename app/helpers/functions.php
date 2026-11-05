<?php

// app/helpers/functions.php
// Funções globais usadas pelas views e pelos controllers.

// ---------- URLs ----------

/** Caminho base da aplicação, ex.: /phpau/PHPau/portal-si/public */

// function base_path(): string
// {
//     $pasta = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));

//     return ($pasta === '/' || $pasta === '.') ? '' : rtrim($pasta, '/');
// }
function base_path(): string
{
    return BASE_URL;
}

/** Monta uma URL da aplicação a partir de um caminho, ex.: url('/admin') */
function url(string $caminho = '/'): string
{
    return base_path() . '/' . ltrim($caminho, '/');
}

/** URL de um arquivo em public/assets, ex.: asset('css/base.css') */
function asset(string $arquivo): string
{
    return base_path() . '/assets/' . ltrim($arquivo, '/');
}

/** Redireciona para um caminho da aplicação e encerra a execução */
function redirecionar(string $caminho): void
{
    header('Location: ' . url($caminho));
    exit;
}

// ---------- Segurança e saída ----------

/** Escapa texto para exibir com segurança no HTML */
function e($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

/** Campo oculto com o token CSRF, para todos os formulários POST */
function csrf_campo(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(\App\Core\Csrf::token()) . '">';
}

// ---------- Mensagens (flash) ----------

/** Guarda uma mensagem para a próxima página. Tipos: sucesso, aviso, erro, info */
function flash(string $tipo, string $mensagem): void
{
    $_SESSION['flash'][] = ['tipo' => $tipo, 'mensagem' => $mensagem];
}

/** Devolve o HTML das mensagens pendentes e as remove da sessão */
function flashes(): string
{
    $mensagens = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);

    $classes = [
        'sucesso' => 'alert-success',
        'aviso'   => 'alert-warning',
        'erro'    => 'alert-error',
        'info'    => 'alert-info',
    ];

    $html = '';

    foreach ($mensagens as $mensagem) {
        $classe = $classes[$mensagem['tipo']] ?? 'alert-info';
        $papel  = $mensagem['tipo'] === 'erro' ? 'alert' : 'status';

        $html .= '<div class="alert ' . $classe . '" role="' . $papel . '">'
            . e($mensagem['mensagem'])
            . '</div>';
    }

    return $html;
}

// ---------- Leitura segura de entradas ----------

/** Texto de um campo de $_GET ou $_POST, sem espaços nas pontas */
function texto_de(array $origem, string $chave): string
{
    $valor = $origem[$chave] ?? '';

    return is_string($valor) ? trim($valor) : '';
}

/** Número inteiro de um campo de $_GET ou $_POST (0 se inválido) */
function inteiro_de(array $origem, string $chave): int
{
    $valor = $origem[$chave] ?? 0;

    return is_numeric($valor) ? (int) $valor : 0;
}

/** Interpreta um boolean vindo do PostgreSQL */
function verdadeiro($valor): bool
{
    return $valor === true || $valor === 1 || $valor === '1' || $valor === 't' || $valor === 'true';
}

// ---------- Formatação ----------

/** Formata uma data vinda do banco para exibição */
function formatar_data(?string $valor, string $formato = 'd/m/Y H:i'): string
{
    if ($valor === null || $valor === '') {
        return '—';
    }

    $timestamp = strtotime($valor);

    return $timestamp === false ? '—' : date($formato, $timestamp);
}

/** Converte o valor de um <input type="datetime-local"> para o formato do banco */
function data_para_banco(string $valor): ?string
{
    foreach (['Y-m-d\TH:i', 'Y-m-d\TH:i:s'] as $formato) {
        $data = DateTime::createFromFormat($formato, $valor);

        if ($data !== false) {
            return $data->format('Y-m-d H:i:s');
        }
    }

    return null;
}

/** Converte uma data do banco para o valor de um <input type="datetime-local"> */
function data_para_input(?string $valor): string
{
    if ($valor === null || $valor === '') {
        return '';
    }

    $timestamp = strtotime($valor);

    return $timestamp === false ? '' : date('Y-m-d\TH:i', $timestamp);
}

/** Classe CSS do selo (badge) para cada situação */
function classe_status(string $nome): string
{
    switch ($nome) {
        case 'Publicado':
        case 'Realizado':
        case 'Ativo':
            return 'badge-success';
        case 'Rascunho':
            return 'badge-warning';
        case 'Agendado':
            return 'badge-info';
        case 'Cancelado':
            return 'badge-danger';
        default:
            return 'badge-neutral';
    }
}

/** Gera um slug (endereço amigável) a partir de um texto */
function gerar_slug(string $texto): string
{
    $acentos = [
        'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a',
        'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
        'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
        'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
        'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
        'ç' => 'c', 'ñ' => 'n',
    ];

    $texto = strtr(mb_strtolower($texto, 'UTF-8'), $acentos);
    $texto = preg_replace('/[^a-z0-9]+/', '-', $texto);

    return trim((string) $texto, '-');
}

/** Iniciais de um nome, para o avatar (ex.: "Maria Souza" => "MS") */
function iniciais(string $nome): string
{
    $partes = preg_split('/\s+/', trim($nome), -1, PREG_SPLIT_NO_EMPTY);

    if (empty($partes)) {
        return '';
    }

    $iniciais = mb_strtoupper(mb_substr($partes[0], 0, 1));

    if (count($partes) > 1) {
        $iniciais .= mb_strtoupper(mb_substr($partes[count($partes) - 1], 0, 1));
    }

    return $iniciais;
}
