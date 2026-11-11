<?php

namespace App\Install;

use PDO;
use PDOException;
use Throwable;

/**
 * Erro com mensagem já pronta para mostrar a quem está instalando.
 */
class InstalacaoException extends \RuntimeException
{
}

/**
 * Lógica do instalador do Portal SI.
 *
 * Esta classe não imprime nada: é usada tanto pelo assistente web
 * (public/install/index.php) quanto pela linha de comando (database/instalar.php).
 *
 * O que ela faz, em ordem:
 *   1. confere os requisitos do ambiente;
 *   2. conecta ao PostgreSQL (e cria o banco, se preciso);
 *   3. aplica as migrações de database/versions (mesma tabela schema_version
 *      dos arquivos .bat, então os dois jeitos de instalar são compatíveis);
 *   4. cria o primeiro Administrador;
 *   5. grava config/database.php e config/app.php;
 *   6. cria config/install.lock, que tranca o instalador.
 */
final class Instalador
{
    public const VERSAO       = '1.0';
    public const PERFIL_ADMIN = 4;
    public const PHP_MINIMO   = '8.0.0';
    public const MIN_SENHA    = 8;

    /** Modos de SSL aceitos pelo libpq (campo 'sslmode' dos perfis em config/bancos.php) */
    private const MODOS_SSL = ['disable', 'allow', 'prefer', 'require', 'verify-ca', 'verify-full'];

    private const SENHAS_FRACAS = [
        '12345678', '123456789', '1234567890', '11111111', '00000000',
        'password', 'password1', 'senha123', 'senha1234', 'admin123',
        'admin1234', 'qwerty123', 'abc12345', 'mudar123', 'portalsi1',
    ];

    private string $raiz;

    public function __construct(?string $raiz = null)
    {
        // este arquivo fica em app/Install: dois níveis acima é a raiz do projeto
        $this->raiz = $raiz ?? dirname(__DIR__, 2);
    }

    public function caminho(string $relativo): string
    {
        return $this->raiz . '/' . ltrim($relativo, '/');
    }

    // ======================================================================
    // 1. Requisitos do ambiente
    // ======================================================================

    /**
     * @return array<int, array{nome:string, nivel:string, detalhe:string}>
     *         nivel: 'ok', 'aviso' (não impede) ou 'erro' (impede)
     *
     * Com $web = false (linha de comando) ficam de fora os itens que só fazem
     * sentido no navegador: sessão, mod_rewrite e HTTPS.
     */
    public function requisitos(bool $web = true): array
    {
        $itens = [];
        $add   = function (string $nome, string $nivel, string $detalhe) use (&$itens): void {
            $itens[] = ['nome' => $nome, 'nivel' => $nivel, 'detalhe' => $detalhe];
        };

        $phpOk = version_compare(PHP_VERSION, self::PHP_MINIMO, '>=');
        $add(
            'Versão do PHP',
            $phpOk ? 'ok' : 'erro',
            $phpOk
                ? 'PHP ' . PHP_VERSION
                : 'PHP ' . PHP_VERSION . ' instalado; é necessário ' . self::PHP_MINIMO . ' ou superior.'
        );

        $extensoes = [
            'pdo'       => 'PDO',
            'pdo_pgsql' => 'Driver PDO do PostgreSQL (pdo_pgsql)',
            'mbstring'  => 'Extensão mbstring',
        ];

        foreach ($extensoes as $extensao => $rotulo) {
            $carregada = extension_loaded($extensao);
            $add(
                $rotulo,
                $carregada ? 'ok' : 'erro',
                $carregada
                    ? 'Habilitada'
                    : 'Habilite no php.ini (tire o ";" da linha extension=' . $extensao . ') e reinicie o Apache.'
            );
        }

        if ($web) {
            $sessaoOk = session_status() === PHP_SESSION_ACTIVE;
            $add(
                'Sessões do PHP',
                $sessaoOk ? 'ok' : 'erro',
                $sessaoOk ? 'Funcionando' : 'Não foi possível iniciar a sessão (confira session.save_path no php.ini).'
            );
        }

        $migracoes = $this->migracoesDisponiveis();
        $add(
            'Arquivos de migração do banco',
            $migracoes ? 'ok' : 'erro',
            $migracoes
                ? count($migracoes) . ' arquivo(s) em database/versions'
                : 'Nenhum arquivo .sql encontrado em database/versions.'
        );

        $pastaConfig = $this->caminho('config');
        $gravavel    = is_dir($pastaConfig) && is_writable($pastaConfig);
        $add(
            'Gravação na pasta config/',
            $gravavel ? 'ok' : 'aviso',
            $gravavel
                ? 'O instalador consegue gravar a configuração sozinho.'
                : 'Sem permissão de escrita. O instalador vai mostrar o conteúdo dos arquivos para você colar à mão.'
        );

        if ($web && function_exists('apache_get_modules')) {
            $rewrite = in_array('mod_rewrite', apache_get_modules(), true);
            $add(
                'Apache mod_rewrite',
                $rewrite ? 'ok' : 'aviso',
                $rewrite
                    ? 'Habilitado'
                    : 'Não aparece como habilitado. Sem ele, só a página inicial abre; as demais rotas dão erro 404.'
            );
        }

        if ($web && !$this->acessoLocal() && !$this->https()) {
            $add(
                'Conexão segura (HTTPS)',
                'aviso',
                'Você está acessando por HTTP: a senha do banco e a do administrador trafegam sem criptografia. '
                . 'Prefira HTTPS ou rode o instalador em localhost.'
            );
        }

        return $itens;
    }

    /** @param array<int, array{nivel:string}> $itens */
    public function requisitosOk(array $itens): bool
    {
        foreach ($itens as $item) {
            if ($item['nivel'] === 'erro') {
                return false;
            }
        }

        return true;
    }

    private function https(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['SERVER_PORT'] ?? '') === '443');
    }

    // ======================================================================
    // 2. Trava e controle de acesso
    // ======================================================================

    /**
     * Motivo pelo qual o instalador NÃO pode rodar, ou null se pode.
     *
     * Bloqueia quando existe config/install.lock ou quando o banco configurado
     * já tem um Administrador (cobre quem instalou antes pelos arquivos .bat).
     */
    public function bloqueio(): ?string
    {
        if (is_file($this->caminho('config/install.lock'))) {
            return 'O portal já foi instalado (arquivo config/install.lock encontrado). '
                . 'Apagar o banco de dados não destranca o instalador: para instalar de novo, apague também '
                . 'o arquivo config/install.lock no servidor.';
        }

        $cfg = $this->lerConfigBanco();

        if ($cfg !== null && $this->errosBanco($cfg + ['criar' => false]) === []) {
            try {
                $pdo = $this->conectar($cfg, null, 3);

                if ($this->temAdministrador($pdo)) {
                    return 'O banco configurado em config/database.php já possui um Administrador. '
                        . 'Se você quer instalar do zero, apague esse banco ou o arquivo config/database.php '
                        . '(que aponta para ele). Para recuperar o acesso, use database/create_admin.php; para '
                        . 'aplicar atualizações do banco, rode no terminal: php database/instalar.php --atualizar';
                }
            } catch (Throwable $e) {
                // Sem conexão com o banco configurado: ainda não há nada instalado.
            }
        }

        return null;
    }

    /** True quando a requisição vem do próprio servidor (localhost) */
    public function acessoLocal(): bool
    {
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');

        return $ip === '127.0.0.1'
            || $ip === '::1'
            || strncmp($ip, '::ffff:127.', 11) === 0;
    }

    /**
     * Fora do localhost, quem instala precisa provar que tem acesso ao servidor:
     * o instalador cria config/install.key e a pessoa digita o conteúdo dele.
     * Retorna false se o arquivo não pôde ser criado.
     */
    public function prepararChave(): bool
    {
        $arquivo = $this->caminho('config/install.key');

        if (is_file($arquivo)) {
            return true;
        }

        if (@file_put_contents($arquivo, bin2hex(random_bytes(16)) . PHP_EOL, LOCK_EX) === false) {
            return false;
        }

        @chmod($arquivo, 0600);

        return true;
    }

    public function chaveConfere(string $informada): bool
    {
        $arquivo = $this->caminho('config/install.key');
        $real    = is_file($arquivo) ? trim((string) @file_get_contents($arquivo)) : '';

        return $real !== '' && hash_equals($real, trim($informada));
    }

    // ======================================================================
    // 3. Validação
    // ======================================================================

    /**
     * Bancos oferecidos pelo instalador (arquivo config/bancos.php).
     *
     * @return array<string, array{nome:string, host:string, port:string, name:string, criar_banco:bool}>
     */
    public function perfisBanco(): array
    {
        $arquivo = $this->caminho('config/bancos.php');
        $perfis  = is_file($arquivo) ? require $arquivo : [];

        return is_array($perfis) ? $perfis : [];
    }

    /**
     * 'sslmode'   : modo SSL da conexão (padrão 'prefer', o mesmo comportamento anterior).
     * 'atualizar' : true = perfil "somente atualizar" (banco já existente: só aplica
     *               migrações e grava a conexão, sem criar banco nem Administrador).
     *
     * @return array{host:string, port:string, name:string, user:string, pass:string, criar:bool, sslmode:string, atualizar:bool}
     */
    public function normalizarBanco(array $entrada): array
    {
        $sslmode = strtolower(trim((string) ($entrada['sslmode'] ?? '')));

        return [
            'host'      => trim((string) ($entrada['host'] ?? '')),
            'port'      => trim((string) ($entrada['port'] ?? '')),
            'name'      => trim((string) ($entrada['name'] ?? '')),
            'user'      => trim((string) ($entrada['user'] ?? '')),
            'pass'      => (string) ($entrada['pass'] ?? ''),
            'criar'     => !empty($entrada['criar']),
            'sslmode'   => $sslmode === '' ? 'prefer' : $sslmode,
            'atualizar' => !empty($entrada['atualizar']),
        ];
    }

    /** @return string[] mensagens de erro (vazio se estiver tudo certo) */
    public function errosBanco(array $d): array
    {
        $erros = [];

        $host = (string) ($d['host'] ?? '');
        $port = (string) ($d['port'] ?? '');
        $nome = (string) ($d['name'] ?? '');
        $user = (string) ($d['user'] ?? '');
        $pass = (string) ($d['pass'] ?? '');

        if ($host === '' || !preg_match('/^[A-Za-z0-9.:\-]+$/', $host)) {
            $erros[] = 'Informe um servidor válido (ex.: localhost ou 127.0.0.1).';
        }

        if (!ctype_digit($port) || (int) $port < 1 || (int) $port > 65535) {
            $erros[] = 'A porta deve ser um número entre 1 e 65535 (o padrão do PostgreSQL é 5432).';
        }

        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]{0,62}$/', $nome)) {
            $erros[] = 'O nome do banco deve começar com letra ou "_" e usar só letras, números e "_" (até 63 caracteres).';
        }

        if ($user === '' || strlen($user) > 128 || preg_match('/[\x00-\x1F]/', $user)) {
            $erros[] = 'Informe o usuário do PostgreSQL.';
        }

        if (strpos($pass, "\0") !== false) {
            $erros[] = 'A senha do banco contém um caractere inválido.';
        }

        if (!in_array((string) ($d['sslmode'] ?? 'prefer'), self::MODOS_SSL, true)) {
            $erros[] = 'Modo SSL inválido em config/bancos.php (use ' . implode(', ', self::MODOS_SSL) . ').';
        }

        return $erros;
    }

    /** @return string[] */
    public function errosAdmin(array $a): array
    {
        $erros = [];

        $nome  = trim((string) ($a['nome'] ?? ''));
        $email = trim((string) ($a['email'] ?? ''));
        $senha = (string) ($a['senha'] ?? '');

        if (mb_strlen($nome) < 3) {
            $erros[] = 'O nome deve ter ao menos 3 caracteres.';
        } elseif (mb_strlen($nome) > 100) {
            $erros[] = 'O nome deve ter no máximo 100 caracteres.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
            $erros[] = 'Informe um e-mail válido.';
        }

        if (mb_strlen($senha) < self::MIN_SENHA) {
            $erros[] = 'A senha deve ter ao menos ' . self::MIN_SENHA . ' caracteres.';
        } elseif (
            in_array(mb_strtolower($senha), self::SENHAS_FRACAS, true)
            || mb_strtolower($senha) === mb_strtolower($email)
        ) {
            $erros[] = 'Escolha uma senha menos óbvia (essa é muito comum ou igual ao e-mail).';
        }

        if (array_key_exists('senha2', $a) && $senha !== (string) $a['senha2']) {
            $erros[] = 'A confirmação da senha não confere.';
        }

        return $erros;
    }

    public function normalizarBaseUrl(string $valor): string
    {
        $valor = trim(str_replace('\\', '/', $valor));
        $valor = rtrim($valor, '/');

        return $valor;
    }

    /** @return string[] */
    public function errosBaseUrl(string $baseUrl): array
    {
        if ($baseUrl === '') {
            return [];
        }

        if (!preg_match('#^(/[A-Za-z0-9._~-]+)+$#', $baseUrl) || preg_match('#/\.{1,2}(/|$)#', $baseUrl)) {
            return ['O endereço base deve começar com "/" e usar só letras, números, ".", "_", "-" e "~" (ex.: /portal-si/public). Deixe vazio se o servidor já aponta para a pasta public.'];
        }

        return [];
    }

    /** Descobre o BASE_URL pelo endereço em que o instalador está rodando */
    public function detectarBaseUrl(): string
    {
        // No terminal não há URL: SCRIPT_NAME seria o caminho do arquivo no disco
        if (PHP_SAPI === 'cli') {
            return '';
        }

        $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        $base   = rtrim(dirname(dirname($script)), '/');

        return $base === '.' ? '' : $base;
    }

    /** BASE_URL já gravado em config/app.php (ou o detectado, se não houver) */
    public function baseUrlAtual(): string
    {
        $arquivo = $this->caminho('config/app.php');

        if (is_file($arquivo)
            && preg_match("/define\(\s*'BASE_URL'\s*,\s*'([^']*)'\s*\)/", (string) file_get_contents($arquivo), $m)
        ) {
            return $m[1];
        }

        return $this->detectarBaseUrl();
    }

    // ======================================================================
    // 4. Banco de dados
    // ======================================================================

    /** @param array{host:string, port:string, name:string, user:string, pass:string} $c */
    public function conectar(array $c, ?string $banco = null, int $timeout = 5): PDO
    {
        $erros = $this->errosBanco($c + ['criar' => false]);

        if ($erros !== []) {
            throw new InstalacaoException($erros[0]);
        }

        $dsn = sprintf(
            'pgsql:host=%s;port=%d;dbname=%s;connect_timeout=%d;sslmode=%s',
            $c['host'],
            (int) $c['port'],
            $banco ?? $c['name'],
            $timeout,
            $c['sslmode'] ?? 'prefer' // já validado por errosBanco()
        );

        $pdo = new PDO($dsn, $c['user'], $c['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $pdo->exec("SET client_encoding TO 'UTF8'");

        return $pdo;
    }

    /** Lê o que há em config/database.php sem executá-lo (ele chama die() se falhar) */
    public function lerConfigBanco(): ?array
    {
        $arquivo = $this->caminho('config/database.php');

        if (!is_file($arquivo) || !is_readable($arquivo)) {
            return null;
        }

        $texto = (string) file_get_contents($arquivo);
        $mapa  = ['host' => 'host', 'dbname' => 'name', 'username' => 'user', 'password' => 'pass', 'port' => 'port'];
        $cfg   = [];

        foreach ($mapa as $variavel => $chave) {
            $padrao = '/^\s*\$' . $variavel . '\s*=\s*\'((?:[^\'\\\\]|\\\\.)*)\'\s*;/m';

            if (!preg_match($padrao, $texto, $m)) {
                return null;
            }

            $cfg[$chave] = strtr($m[1], ['\\\\' => '\\', "\\'" => "'"]);
        }

        // $sslmode é opcional: config/database.php gerado por versões antigas não o tem
        if (preg_match('/^\s*\$sslmode\s*=\s*\'(disable|allow|prefer|require|verify-ca|verify-full)\'\s*;/m', $texto, $m)) {
            $cfg['sslmode'] = $m[1];
        }

        return $cfg;
    }

    public function temAdministrador(PDO $pdo): bool
    {
        if (!$pdo->query("SELECT to_regclass('public.users')")->fetchColumn()) {
            return false;
        }

        $coluna = $this->colunaPerfil($pdo);

        if ($coluna === null) {
            return false;
        }

        // $coluna vem de uma lista fixa (profile_id ou profile_code), nunca da entrada.
        // Só conta Administrador ativo: um inativo não consegue entrar nem aprovar contas.
        return (bool) $pdo->query(
            'SELECT 1 FROM users WHERE ' . $coluna . ' = ' . self::PERFIL_ADMIN . ' AND status = TRUE LIMIT 1'
        )->fetchColumn();
    }

    /**
     * Nome da coluna de perfil em users. A coluna se chamava profile_code e passou a
     * se chamar profile_id (migração DB_v011). Bancos instalados antes do renome ainda
     * têm o nome antigo, e é preciso reconhecê-los: é assim que o instalador sabe que
     * um banco antigo já tem Administrador e não deve ser reinstalado por cima.
     */
    private function colunaPerfil(PDO $pdo): ?string
    {
        $colunas = $pdo->query(
            "SELECT column_name FROM information_schema.columns
             WHERE table_schema = 'public' AND table_name = 'users'
               AND column_name IN ('profile_id', 'profile_code')"
        )->fetchAll(PDO::FETCH_COLUMN);

        foreach (['profile_id', 'profile_code'] as $nome) {
            if (in_array($nome, $colunas, true)) {
                return $nome;
            }
        }

        return null;
    }

    /** @return string[] nomes dos arquivos .sql, em ordem */
    public function migracoesDisponiveis(): array
    {
        $arquivos = glob($this->caminho('database/versions') . '/*.sql') ?: [];
        $nomes    = array_map('basename', $arquivos);

        sort($nomes, SORT_NATURAL | SORT_FLAG_CASE);

        return $nomes;
    }

    /**
     * Examina o servidor e o banco sem alterar nada.
     *
     * @return array{existe:bool, pode_criar:bool, pendentes:string[], aplicadas:string[], tem_admin:bool, inconsistente:bool}
     * @throws InstalacaoException se não houver como conectar
     */
    public function diagnosticar(array $d): array
    {
        $info = [
            'existe'        => false,
            'pode_criar'    => false,
            'pendentes'     => [],
            'aplicadas'     => [],
            'tem_admin'     => false,
            'inconsistente' => false,
        ];

        $todas = $this->migracoesDisponiveis();

        try {
            $pdo = $this->conectar($d);
        } catch (PDOException $primeiro) {
            // O banco pode simplesmente não existir. Tenta o banco de manutenção
            // (a mensagem do servidor muda conforme o idioma, então não dá para
            // depender do texto do erro).
            try {
                $manutencao = $this->conectar($d, 'postgres');
            } catch (PDOException $segundo) {
                throw new InstalacaoException(
                    'Não foi possível conectar ao PostgreSQL: ' . $this->mensagemLimpa($primeiro)
                    . ' — confira servidor, porta, usuário e senha, e se o serviço do PostgreSQL está em execução.'
                );
            }

            $busca = $manutencao->prepare('SELECT 1 FROM pg_database WHERE datname = :nome');
            $busca->execute([':nome' => $d['name']]);

            if ($busca->fetchColumn()) {
                throw new InstalacaoException(
                    'O banco "' . $d['name'] . '" existe, mas o usuário "' . $d['user']
                    . '" não consegue acessá-lo: ' . $this->mensagemLimpa($primeiro)
                );
            }

            $permissao = $manutencao->query(
                'SELECT CASE WHEN rolcreatedb OR rolsuper THEN 1 ELSE 0 END FROM pg_roles WHERE rolname = current_user'
            )->fetchColumn();

            $info['pode_criar'] = (int) $permissao === 1;
            $info['pendentes']  = $todas;

            return $info;
        }

        $info['existe'] = true;

        $temSchema = (bool) $pdo->query("SELECT to_regclass('public.schema_version')")->fetchColumn();

        if ($temSchema) {
            $info['aplicadas'] = $pdo->query('SELECT filename FROM schema_version ORDER BY filename')
                ->fetchAll(PDO::FETCH_COLUMN);
        }

        $tabelas = (int) $pdo->query(
            "SELECT COUNT(*) FROM information_schema.tables
             WHERE table_schema = 'public' AND table_type = 'BASE TABLE' AND table_name <> 'schema_version'"
        )->fetchColumn();

        $info['inconsistente'] = $tabelas > 0 && $info['aplicadas'] === [];
        $info['tem_admin']     = $this->temAdministrador($pdo);
        $info['pendentes']     = array_values(array_diff($todas, $info['aplicadas']));

        if ($info['pendentes'] !== []) {
            // PostgreSQL 15+ não deixa mais usuários comuns criarem tabelas no schema public
            $criar = (int) $pdo->query(
                "SELECT CASE WHEN has_schema_privilege('public', 'CREATE') THEN 1 ELSE 0 END"
            )->fetchColumn();

            if ($criar !== 1) {
                throw new InstalacaoException(
                    'O usuário "' . $d['user'] . '" não tem permissão para criar tabelas no banco "' . $d['name']
                    . '". Use um usuário que seja dono do banco (ou o "postgres"), ou rode '
                    . 'GRANT ALL ON SCHEMA public TO ' . $d['user'] . '; no banco.'
                );
            }
        }

        return $info;
    }

    /** Motivo que impede de seguir com esse banco, ou null se está tudo certo */
    public function impedimento(array $d, array $diag): ?string
    {
        $soAtualizar = !empty($d['atualizar']);

        // Perfil "somente atualizar": o banco já ter Administrador é o esperado, não um impedimento
        if ($diag['tem_admin'] && !$soAtualizar) {
            return 'O banco "' . $d['name'] . '" já possui um Administrador, então o instalador não vai mexer nele. '
                . 'Escolha outro nome de banco. Para recuperar o acesso, use database/create_admin.php; para aplicar '
                . 'atualizações nesse banco, rode no terminal: php database/instalar.php --atualizar';
        }

        if ($diag['inconsistente']) {
            return 'O banco "' . $d['name'] . '" já tem tabelas, mas sem histórico de versões (tabela schema_version). '
                . 'Para não sobrescrever dados, o instalador não continua. Use um banco vazio ou um novo nome.';
        }

        if (!$diag['existe']) {
            if ($soAtualizar) {
                return 'O banco "' . $d['name'] . '" não foi encontrado neste servidor. Este perfil só atualiza um banco '
                    . 'que já existe: confira o nome em config/bancos.php.';
            }

            if (empty($d['criar'])) {
                return 'O banco "' . $d['name'] . '" não existe e o instalador não cria este banco. Crie o banco antes de continuar.';
            }

            if (!$diag['pode_criar']) {
                return 'O banco "' . $d['name'] . '" não existe e o usuário "' . $d['user']
                    . '" não tem permissão para criar bancos. Crie o banco à mão ou use o usuário "postgres".';
            }
        }

        return null;
    }

    private function criarBanco(array $d): void
    {
        $pdo = $this->conectar($d, 'postgres');
        $id  = '"' . str_replace('"', '""', $d['name']) . '"';

        try {
            $pdo->exec("CREATE DATABASE $id ENCODING 'UTF8' TEMPLATE template0");
        } catch (PDOException $e) {
            // Em alguns Windows o locale do servidor não aceita UTF8 explícito;
            // cai no CREATE DATABASE simples (o mesmo que o Create Database.bat faz).
            try {
                $pdo->exec("CREATE DATABASE $id");
            } catch (PDOException $e2) {
                throw new InstalacaoException(
                    'Não foi possível criar o banco "' . $d['name'] . '": ' . $this->mensagemLimpa($e2)
                );
            }
        }
    }

    /**
     * Aplica, uma a uma e cada uma numa transação, as migrações que faltam.
     * Usa a mesma tabela schema_version dos arquivos .bat.
     *
     * @return int quantas migrações foram aplicadas
     */
    public function aplicarMigracoes(PDO $pdo, ?callable $log = null): int
    {
        $log = $log ?? static function (string $mensagem): void {
        };

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS schema_version (
                id SERIAL PRIMARY KEY,
                filename VARCHAR(255) NOT NULL UNIQUE,
                applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            )'
        );

        $aplicadas = $pdo->query('SELECT filename FROM schema_version')->fetchAll(PDO::FETCH_COLUMN);
        $total     = 0;

        foreach ($this->migracoesDisponiveis() as $arquivo) {
            if (in_array($arquivo, $aplicadas, true)) {
                continue;
            }

            [$sql, $controlaTransacao] = $this->prepararSql($this->lerSql($arquivo));

            try {
                if ($controlaTransacao) {
                    // O próprio arquivo abre e fecha transações no meio do script, então não
                    // dá para embrulhá-lo em outra: ele roda como está e é registrado depois.
                    $pdo->exec($sql);
                    $this->registrarVersao($pdo, $arquivo);
                } else {
                    $pdo->beginTransaction();
                    $pdo->exec($sql);
                    $this->registrarVersao($pdo, $arquivo);
                    $pdo->commit();
                }
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                throw new InstalacaoException(
                    'Falha ao aplicar ' . $arquivo . ': ' . $this->mensagemLimpa($e)
                    . ' (a alteração foi desfeita; corrija o problema e rode o instalador de novo).',
                    0,
                    $e
                );
            }

            $log('Migração aplicada: ' . $arquivo);
            $total++;
        }

        return $total;
    }

    private function registrarVersao(PDO $pdo, string $arquivo): void
    {
        $registro = $pdo->prepare('INSERT INTO schema_version (filename) VALUES (:arquivo)');
        $registro->execute([':arquivo' => $arquivo]);
    }

    /**
     * O instalador executa cada migração dentro de uma transação própria. Muitos
     * arquivos .sql começam com BEGIN; e terminam com COMMIT; — se isso ficasse no
     * script, o COMMIT fecharia a transação do instalador antes da hora. Por isso o
     * BEGIN do início e o COMMIT do fim são retirados (o resultado é o mesmo).
     *
     * @return array{0:string, 1:bool} o SQL pronto e se ele controla transações por conta própria
     *         (BEGIN/COMMIT/ROLLBACK no meio do script), caso em que é executado sem alterações
     */
    private function prepararSql(string $sql): array
    {
        $abre  = '/\A(?:\s|--[^\n]*(?:\n|\z))*(?:BEGIN|START\s+TRANSACTION)(?:\s+(?:TRANSACTION|WORK))?\s*;/i';
        $fecha = '/COMMIT(?:\s+(?:TRANSACTION|WORK))?\s*;(?:\s|--[^\n]*(?:\n|\z))*\z/i';

        $limpo = $sql;

        if (preg_match($abre, $limpo) === 1) {
            $limpo = (string) preg_replace($abre, '', $limpo, 1);
        }

        if (preg_match($fecha, $limpo) === 1) {
            $limpo = (string) preg_replace($fecha, '', $limpo, 1);
        }

        $sobrou = preg_match(
            '/^\s*(?:BEGIN|COMMIT|ROLLBACK|START\s+TRANSACTION)(?:\s+(?:TRANSACTION|WORK))?\s*;/im',
            $limpo
        ) === 1;

        return $sobrou ? [$sql, true] : [$limpo, false];
    }

    private function lerSql(string $arquivo): string
    {
        $sql = (string) file_get_contents($this->caminho('database/versions/' . $arquivo));

        if (strncmp($sql, "\xEF\xBB\xBF", 3) === 0) {
            $sql = substr($sql, 3);
        }

        if (!mb_check_encoding($sql, 'UTF-8')) {
            $sql = mb_convert_encoding($sql, 'UTF-8', 'Windows-1252');
        }

        return $sql;
    }

    /**
     * Cria o Administrador (ou promove a conta, se o e-mail já existir).
     * Mesma regra do database/create_admin.php.
     *
     * @return array{id:int, promovido:bool}
     */
    public function criarAdministrador(PDO $pdo, array $a): array
    {
        $nome  = trim((string) $a['nome']);
        $email = trim((string) $a['email']);

        $existe = $pdo->prepare('SELECT 1 FROM users WHERE email = :email');
        $existe->execute([':email' => $email]);
        $promovido = (bool) $existe->fetchColumn();

        $stmt = $pdo->prepare(
            'INSERT INTO users (name, email, password_hash, profile_id, status)
             VALUES (:name, :email, :password_hash, :profile_id, TRUE)
             ON CONFLICT (email) DO UPDATE
                SET password_hash = EXCLUDED.password_hash,
                    profile_id    = EXCLUDED.profile_id,
                    status        = TRUE,
                    updated_at    = CURRENT_TIMESTAMP
             RETURNING id'
        );

        $stmt->execute([
            ':name'          => $nome,
            ':email'         => $email,
            ':password_hash' => password_hash((string) $a['senha'], PASSWORD_DEFAULT),
            ':profile_id'    => self::PERFIL_ADMIN,
        ]);

        return ['id' => (int) $stmt->fetchColumn(), 'promovido' => $promovido];
    }

    // ======================================================================
    // 5. Instalação completa
    // ======================================================================

    /**
     * Executa a instalação inteira.
     *
     * @param array $banco  host, port, name, user, pass, criar
     * @param array $admin  nome, email, senha
     * @return array{log:string[], manual:array<string,string>, lock:bool, email:string, banco:string}
     * @throws InstalacaoException
     */
    public function instalar(array $banco, array $admin, string $baseUrl, ?callable $log = null): array
    {
        // Não deixa o navegador fechado interromper a instalação no meio
        ignore_user_abort(true);
        @set_time_limit(180);

        $idInstalacao = bin2hex(random_bytes(8));
        $saida = [
            'log'    => [],
            'manual' => [],
            'lock'   => false,
            'email'  => trim((string) $admin['email']),
            'banco'  => $banco['name'],
            'id'     => $idInstalacao,
            'em'     => time(),
        ];
        $log   = $log ?? static function (string $mensagem): void {
        };
        $reg   = function (string $mensagem) use (&$saida, $log): void {
            $saida['log'][] = $mensagem;
            $log($mensagem);
        };

        if (($motivo = $this->bloqueio()) !== null) {
            throw new InstalacaoException($motivo);
        }

        $erros = array_merge($this->errosBanco($banco), $this->errosAdmin($admin), $this->errosBaseUrl($baseUrl));

        if ($erros !== []) {
            throw new InstalacaoException(implode(' ', $erros));
        }

        // --- banco ---
        $diag = $this->diagnosticar($banco);

        if (($motivo = $this->impedimento($banco, $diag)) !== null) {
            throw new InstalacaoException($motivo);
        }

        if (!$diag['existe']) {
            $this->criarBanco($banco);
            $reg('Banco "' . $banco['name'] . '" criado.');
        } else {
            $reg('Banco "' . $banco['name'] . '" encontrado.');
        }

        $pdo = $this->conectar($banco);

        $aplicadas = $this->aplicarMigracoes($pdo, $reg);
        $reg($aplicadas === 0 ? 'A estrutura do banco já estava atualizada.' : $aplicadas . ' migração(ões) aplicada(s).');

        // --- administrador ---
        $resultado = $this->criarAdministrador($pdo, $admin);
        $reg(
            $resultado['promovido']
                ? 'Conta existente promovida a Administrador: ' . $saida['email']
                : 'Administrador criado: ' . $saida['email']
        );

        // --- arquivos de configuração (só depois de o banco estar pronto) ---
        $arquivos = [
            'config/database.php' => $this->gerarConfigBanco($banco),
            'config/app.php'      => $this->gerarConfigApp($baseUrl),
        ];

        $this->gravarConfiguracao($arquivos, $saida, $reg);

        return $saida;
    }

    /**
     * O resumo final ("Instalação concluída") só vale enquanto a instalação a que ele se
     * refere ainda existe. Se o config/install.lock foi apagado para reinstalar, o resumo
     * antigo guardado na sessão do navegador deve ser descartado, senão o assistente
     * ficaria preso na tela final.
     */
    public function resultadoValido(array $resultado): bool
    {
        if (!empty($resultado['lock'])) {
            $lock = $this->caminho('config/install.lock');

            return isset($resultado['id'])
                && is_file($lock)
                && strpos((string) @file_get_contents($lock), 'id=' . $resultado['id']) !== false;
        }

        // Não foi possível criar o lock: o resumo vale por 30 minutos
        return !empty($resultado['em']) && (time() - (int) $resultado['em']) < 1800;
    }

    /**
     * Aplica só as migrações pendentes no banco de config/database.php.
     * Uso: php database/instalar.php --atualizar (não depende do assistente web).
     */
    public function atualizarExistente(?callable $log = null): int
    {
        $cfg = $this->lerConfigBanco();

        if ($cfg === null) {
            throw new InstalacaoException('Não consegui ler as credenciais em config/database.php.');
        }

        try {
            $pdo = $this->conectar($cfg);
        } catch (PDOException $e) {
            throw new InstalacaoException('Não foi possível conectar ao banco: ' . $this->mensagemLimpa($e));
        }

        return $this->aplicarMigracoes($pdo, $log);
    }

    /**
     * Modo "somente atualizar": usado pelos perfis de config/bancos.php com
     * 'somente_atualizar' => true (ex.: o banco na nuvem). O banco já existe e já tem
     * Administrador, então aqui NÃO se cria banco nem Administrador: só aplica as
     * migrações pendentes e grava a conexão em config/database.php (e config/app.php,
     * se ainda não existir). Depois tranca o instalador, como na instalação normal.
     *
     * @param array $banco host, port, name, user, pass, sslmode (de normalizarBanco)
     * @return array mesmo formato de instalar(), com 'modo' => 'atualizacao'
     * @throws InstalacaoException
     */
    public function configurarEAtualizar(array $banco, string $baseUrl, ?callable $log = null): array
    {
        // Não deixa o navegador fechado interromper a atualização no meio
        ignore_user_abort(true);
        @set_time_limit(180);

        $saida = [
            'log'       => [],
            'manual'    => [],
            'lock'      => false,
            'email'     => '',
            'banco'     => $banco['name'],
            'id'        => bin2hex(random_bytes(8)),
            'em'        => time(),
            'modo'      => 'atualizacao',
            'sem_admin' => false,
        ];
        $log = $log ?? static function (string $mensagem): void {
        };
        $reg = function (string $mensagem) use (&$saida, $log): void {
            $saida['log'][] = $mensagem;
            $log($mensagem);
        };

        if (($motivo = $this->bloqueio()) !== null) {
            throw new InstalacaoException($motivo);
        }

        $erros = array_merge($this->errosBanco($banco), $this->errosBaseUrl($baseUrl));

        if ($erros !== []) {
            throw new InstalacaoException(implode(' ', $erros));
        }

        $diag = $this->diagnosticar($banco);

        if (($motivo = $this->impedimento($banco, $diag)) !== null) {
            throw new InstalacaoException($motivo);
        }

        try {
            $pdo = $this->conectar($banco);
        } catch (PDOException $e) {
            throw new InstalacaoException('Não foi possível conectar ao banco: ' . $this->mensagemLimpa($e));
        }

        $reg('Conexão com o banco "' . $banco['name'] . '" em ' . $banco['host'] . ' estabelecida.');

        $aplicadas = $this->aplicarMigracoes($pdo, $reg);
        $reg($aplicadas === 0 ? 'A estrutura do banco já estava atualizada.' : $aplicadas . ' migração(ões) aplicada(s).');

        if (!$this->temAdministrador($pdo)) {
            $saida['sem_admin'] = true;
            $reg('Atenção: este banco não tem Administrador. Crie um com: php database/create_admin.php');
        }

        // --- arquivos de configuração (só depois de o banco estar atualizado) ---
        $arquivos = ['config/database.php' => $this->gerarConfigBanco($banco)];

        // config/app.php (BASE_URL) só é criado se ainda não existir: não sobrescreve o que já funciona
        if (!is_file($this->caminho('config/app.php'))) {
            $arquivos['config/app.php'] = $this->gerarConfigApp($baseUrl);
        }

        $this->gravarConfiguracao($arquivos, $saida, $reg);

        return $saida;
    }

    /**
     * Grava os arquivos de config/ (guardando um .bak do que já existia), cria
     * config/install.lock e apaga config/install.key. O que não puder ser gravado
     * vai para $saida['manual'] para a pessoa criar à mão.
     *
     * @param array<string,string> $arquivos caminho relativo => conteúdo
     */
    private function gravarConfiguracao(array $arquivos, array &$saida, callable $reg): void
    {
        foreach ($arquivos as $relativo => $conteudo) {
            $destino = $this->caminho($relativo);

            if (is_file($destino) && !is_file($destino . '.bak')) {
                @copy($destino, $destino . '.bak');
            }

            if ($this->gravarAtomico($destino, $conteudo)) {
                $reg('Arquivo gravado: ' . $relativo);
            } else {
                $saida['manual'][$relativo] = $conteudo;
                $reg('Sem permissão para gravar ' . $relativo . ' (veja o conteúdo no final da página).');
            }
        }

        // --- tranca o instalador ---
        $saida['lock'] = $this->gravarAtomico(
            $this->caminho('config/install.lock'),
            'Instalado em ' . date('Y-m-d H:i:s') . ' (instalador ' . self::VERSAO . ') id=' . $saida['id'] . PHP_EOL
        );

        if ($saida['lock']) {
            $reg('Instalador trancado (config/install.lock criado).');
        }

        @unlink($this->caminho('config/install.key'));
    }

    // ======================================================================
    // 6. Arquivos
    // ======================================================================

    private function literal(string $valor): string
    {
        return "'" . strtr($valor, ['\\' => '\\\\', "'" => "\\'"]) . "'";
    }

    public function gerarConfigBanco(array $d): string
    {
        $modelo = <<<'PHP'
<?php

// config/database.php
// Gerado pelo instalador em {DATA}. Conexão com o PostgreSQL via PDO.
// Este arquivo define a variável $pdo (usada por App\Core\Database e por
// database/create_admin.php). Pode ser editado à mão.

$host     = {HOST};
$dbname   = {DBNAME};
$username = {USER};
$password = {PASS};
$port     = {PORT};
$sslmode  = {SSLMODE};

try {
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname;sslmode=$sslmode", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec("SET client_encoding TO 'UTF8'");
} catch (PDOException $e) {
    // O detalhe vai para o log do servidor; a tela não expõe usuário nem servidor.
    error_log('Portal SI - erro de conexão com o banco: ' . $e->getMessage());
    http_response_code(500);
    exit('Não foi possível conectar ao banco de dados.');
}

PHP;

        return strtr($modelo, [
            '{DATA}'   => date('d/m/Y H:i'),
            '{HOST}'   => $this->literal($d['host']),
            '{DBNAME}' => $this->literal($d['name']),
            '{USER}'   => $this->literal($d['user']),
            '{PASS}'   => $this->literal($d['pass']),
            '{PORT}'   => $this->literal((string) $d['port']),
            '{SSLMODE}' => $this->literal((string) ($d['sslmode'] ?? 'prefer')),
        ]);
    }

    public function gerarConfigApp(string $baseUrl): string
    {
        return "<?php\n\n"
            . "// config/app.php\n"
            . "// BASE_URL: caminho da URL até a pasta public/ (sem barra no final;\n"
            . "// vazio quando o servidor já aponta direto para public/).\n"
            . "define('BASE_URL', " . $this->literal($baseUrl) . ");\n";
    }

    /** Grava num arquivo temporário e renomeia, para nunca deixar o arquivo pela metade */
    private function gravarAtomico(string $arquivo, string $conteudo): bool
    {
        $pasta = dirname($arquivo);

        if (!is_dir($pasta) || !is_writable($pasta)) {
            return false;
        }

        $temporario = $arquivo . '.tmp' . bin2hex(random_bytes(4));

        if (@file_put_contents($temporario, $conteudo, LOCK_EX) === false) {
            @unlink($temporario);

            return false;
        }

        @chmod($temporario, 0640);

        if (!@rename($temporario, $arquivo)) {
            @unlink($temporario);

            return false;
        }

        return true;
    }

    /**
     * Apaga os arquivos de public/install (opcional, depois de instalar).
     *
     * Devolve true quando não sobrou nenhum arquivo. A pasta vazia que eventualmente
     * não puder ser removida é inofensiva (sem index.php ela não abre nada).
     *
     * A ordem importa: o index.php (o script que está rodando) vai primeiro, porque é
     * o que mais pode falhar no Windows. Se ele não sair, nada mais é apagado e a tela
     * do instalador continua inteira, com o CSS. O CSS é o último a ser apagado.
     */
    public function removerInstalador(): bool
    {
        $pasta = $this->caminho('public/install');

        if (!is_dir($pasta)) {
            return true;
        }

        // No Windows não dá para apagar a pasta que é o diretório atual do processo
        @chdir(dirname($pasta));

        $nomes = array_values(array_filter(
            scandir($pasta) ?: [],
            static fn (string $nome): bool => $nome !== '.' && $nome !== '..'
        ));

        usort($nomes, static function (string $a, string $b): int {
            $peso = static fn (string $nome): int => $nome === 'index.php' ? 0 : ($nome === 'install.css' ? 2 : 1);

            return $peso($a) <=> $peso($b);
        });

        foreach ($nomes as $nome) {
            if (!@unlink($pasta . '/' . $nome)) {
                return false;
            }
        }

        @rmdir($pasta);

        return true;
    }

    /** @return string[] arquivos que ainda estão em public/install */
    public function restosDoInstalador(): array
    {
        $pasta = $this->caminho('public/install');

        clearstatcache();

        if (!is_dir($pasta)) {
            return [];
        }

        return array_values(array_filter(
            scandir($pasta) ?: [],
            static fn (string $nome): bool => $nome !== '.' && $nome !== '..'
        ));
    }

    /** Mensagem do erro sem o prefixo técnico e sempre em UTF-8 */
    public function mensagemLimpa(Throwable $e): string
    {
        $mensagem = $e->getMessage();

        if (!mb_check_encoding($mensagem, 'UTF-8')) {
            // servidor PostgreSQL em português no Windows costuma responder em Windows-1252
            $mensagem = mb_convert_encoding($mensagem, 'UTF-8', 'Windows-1252');
        }

        $mensagem = preg_replace('/^SQLSTATE\[[^\]]*\]\s*(\[\d+\]\s*)?/', '', $mensagem);
        $mensagem = trim((string) preg_replace('/\s+/', ' ', (string) $mensagem));

        return $mensagem;
    }
}