# Portal SI — estrutura MVC

Portal de comunicação do curso de Sistemas de Informação, feito em PHP (orientado a
objetos, padrão MVC) com banco PostgreSQL.

## Requisitos

- PHP 8 ou superior, com a extensão `pdo_pgsql` habilitada.
- Apache com `mod_rewrite` habilitado (ex.: XAMPP).
- PostgreSQL (testado na versão 18).

## Como rodar (passo a passo)

O servidor deve apontar para a pasta `public/`, que é o front controller de toda a
aplicação (todas as páginas passam por `public/index.php`).

1. **Copie o projeto** para dentro do `htdocs` e, de preferência, crie um Virtual Host
   com o `DocumentRoot` apontando para a pasta `public/` do projeto (não para a raiz).
   O `mod_rewrite` precisa estar habilitado, porque o `public/.htaccess` reescreve as
   URLs para o `index.php`.
2. **Abra o instalador** no navegador e siga os 3 passos (veja [Instalador](#instalador)):

   ```
   http://localhost/portal_si/public/install/
   ```

   (ajuste o endereço conforme a pasta onde você copiou o projeto).
3. **Pronto.** Ao terminar, clique em "Remover o instalador e entrar no portal" (no servidor
   de produção) ou em "Entrar no portal" (se quiser manter o instalador, por exemplo ao testar).

Se você abrir o portal antes de instalar, ele redireciona sozinho para o instalador, porque
`config/app.php` e `config/database.php` não vêm no repositório (veja [Git](#git-o-que-é-versionado)).

Prefere fazer tudo à mão? Veja [Instalação manual](#instalação-manual-alternativa).

As pastas `app/`, `config/` e `database/` têm um `.htaccess` bloqueando o acesso
direto por URL, mas isso só funciona se o Apache estiver processando essas pastas —
por isso apontar o `DocumentRoot` para `public/` é o que garante a proteção.

## Instalador

O instalador substitui, em um fluxo só, os passos manuais de configurar o banco, rodar
os `.bat` e criar o Administrador. Ele não depende de `psql.exe` nem de caminhos fixos
(como `C:\xampp\php\php.exe`): usa o próprio PHP (extensão `pdo_pgsql`) para falar com
o PostgreSQL, então funciona em Windows, Linux e macOS.

### Assistente no navegador (`/install/`)

1. **Requisitos:** confere versão do PHP, extensões (`pdo_pgsql`, `mbstring`), sessões,
   arquivos de versão do banco e permissão de escrita em `config/`.
2. **Banco de dados:** mostra os bancos de `config/bancos.php` (banco local e banco na
   nuvem), cada um com servidor, porta e nome. Escolha um e informe o usuário e a senha.
   O instalador testa a conexão. No banco local, ele cria o banco se ele não existir. No
   banco na nuvem, o banco já deve existir. O usuário precisa de permissão para criar
   tabelas e, no banco local, para criar bancos (o `postgres` serve).
3. **Administrador:** pede nome, e-mail e senha do primeiro Administrador e o endereço
   base do portal (`BASE_URL`, já detectado automaticamente). Ao clicar em "Instalar
   agora" ele:
   - cria o banco (se preciso) e aplica as versões do banco de `database/versions`, cada uma
     numa transação (se uma falhar, ela é desfeita e nada fica pela metade);
   - cria o Administrador;
   - grava `config/database.php` e `config/app.php` (guarda cópia do anterior em
     `*.bak`);
   - cria `config/install.lock`, que tranca o instalador.

Se o PHP não tiver permissão para gravar em `config/`, o instalador mostra o conteúdo
dos arquivos na tela para você colar à mão.

### Segurança do instalador

- **Trancado depois de usar:** com `config/install.lock` presente, ou se o banco
  configurado já tiver um Administrador (isso cobre instalações feitas antes pelos `.bat`),
  a página só mostra um aviso. Apagar o banco **não** destranca o instalador. Para reinstalar de propósito, apague `config/install.lock`
  (e o banco não pode ter Administrador).
- **Fora do `localhost`, exige uma chave:** ao acessar de outra máquina, o instalador cria
  `config/install.key` e pede o código que está nesse arquivo. Isso prova que quem está
  instalando tem acesso ao servidor. O arquivo é apagado ao final.
- **Nunca sobrescreve dados:** recusa bancos que já têm tabelas sem histórico de versões
  (`schema_version`) e bancos que já têm Administrador.
- Todo formulário usa token CSRF; a sessão do instalador é separada da do portal;
  as páginas não são indexadas nem guardadas em cache.
- Sem HTTPS fora do `localhost`, ele avisa que as senhas trafegam sem criptografia.
- **Remova o instalador depois de instalar:** o botão final apaga a pasta `public/install`.
  Se preferir, apague-a manualmente. Os arquivos `config/install.lock` e `config/install.key`
  ficam em `config/`, que não é acessível pelo navegador.

### Instalador no terminal

Mesma lógica do assistente, para quem não quer abrir o navegador (ou para automatizar):

```
php database/instalar.php
```

Ele pergunta os dados, ou aceita tudo por opções:

```
php database/instalar.php --host=localhost --porta=5432 --banco=portal_si --usuario=postgres \
    --senha-banco=*** --nome="Seu Nome" --email=voce@exemplo.com --senha=*** --base-url=/portal_si/public
```

Use `--nao-criar-banco` se o banco já existir e você não quer que ele tente criá-lo.

### Atualizando o banco depois

Quando uma nova versão do projeto trouxer arquivos novos em `database/versions`, aplique
só os que faltam, sem abrir o instalador (que fica trancado):

```
php database/instalar.php --atualizar
```

O `Update Database.bat` continua funcionando: o instalador e os `.bat` usam a mesma tabela
`schema_version`, então dá para alternar entre eles.

### Escrevendo novas versões do banco

- Para mudar o banco, crie um arquivo novo `DB_vNNN.sql` na pasta `database/versions`
  (numeração seguinte, com três dígitos).
- **Não edite uma versão que já foi aplicada em algum banco:** o controle é pelo nome do
  arquivo em `schema_version`, então bancos existentes nunca rodam o arquivo de novo e ficam
  diferentes dos bancos novos. Se um arquivo antigo precisar mudar para instalações novas,
  crie também uma versão nova, que possa rodar mais de uma vez sem erro, para levar os
  bancos existentes ao mesmo resultado.
- Um `BEGIN;` no início e um `COMMIT;` no fim do arquivo são aceitos: o instalador já executa
  cada versão dentro de uma transação e retira esses dois comandos.

## Estrutura

```
portal_si
├── .gitignore            # mantém fora do Git a configuração local e os arquivos do instalador
├── app
│   ├── Controllers       # uma classe por área (Auth, Categoria, Conteudo, Dashboard,
│   │                     #   Erro, Evento, Home, Usuario)
│   ├── Core              # Router, Controller e Model base, Database (PDO),
│   │                     #   Auth (sessão/permissões), Csrf
│   ├── Install           # Instalador.php (lógica) e Views/ do assistente
│   ├── helpers
│   │   └── functions.php # url(), asset(), e(), flash(), formatar_data() etc.
│   ├── Models            # Usuario, Perfil, Categoria, Conteudo, Evento, Midia
│   ├── Views
│   │   ├── layouts       # admin.php, auth.php, publico.php, vazio.php
│   │   ├── partials      # header.php (menu público), footer.php
│   │   ├── shared        # telas reaproveitadas (confirmar-exclusao.php)
│   │   └── auth, categorias, conteudos, dashboard, errors, eventos, home, usuarios
│   ├── bootstrap.php     # autoload, sessão, constantes
│   └── routes.php        # tabela de rotas
├── config
│   ├── bancos.php            # bancos que o instalador oferece, sem usuário e senha (vai para o Git)
│   ├── app.example.php       # modelo do BASE_URL (vai para o Git)
│   ├── database.example.php  # modelo da conexão com o PostgreSQL (vai para o Git)
│   ├── app.php               # BASE_URL real, criado pelo instalador (fora do Git)
│   ├── database.php          # conexão real, criada pelo instalador (fora do Git)
│   └── install.lock          # criado pelo instalador, tranca o /install (fora do Git)
├── database
│   ├── versions          # versões do banco, numeradas (uma por arquivo)
│   ├── Create Database.bat
│   ├── Update Database.bat
│   ├── Create Admin User.bat
│   ├── create_admin.php  # usado pelo Create Admin User.bat (e para recuperar acesso)
│   └── instalar.php      # instalador/atualizador pelo terminal
└── public
    ├── index.php         # front controller
    ├── .htaccess         # reescreve tudo para index.php
    ├── install           # assistente de instalação (pode ser removido depois)
    ├── uploads           # imagens de capa enviadas (só o .htaccess vai para o Git)
    └── assets/css        # base, components, publico, dashboard, auth
```

## Rotas principais

| Método   | Caminho                          | Controller@método               |
| -------- | -------------------------------- | ------------------------------- |
| GET      | /                                | HomeController@index            |
| GET      | /categoria/{slug}                | CategoriaController@show        |
| GET/POST | /login, /cadastroUsuario, /logout | AuthController                  |
| GET      | /admin                           | DashboardController@index       |
| GET/POST | /admin/conteudos[...]            | ConteudoController              |
| GET/POST | /admin/eventos[...]              | EventoController                |
| GET/POST | /admin/usuarios[...]             | UsuarioController               |

Veja a lista completa em `app/routes.php`.

Nos caminhos, `{id}` aceita somente números e `{slug}` aceita letras minúsculas,
números e hífen (ex.: `/categoria/acoes-sociais`). O valor é repassado ao método do
controller.

## Telas públicas das categorias

Cada categoria cadastrada na tabela `publication_types` tem a sua própria tela em
`/categoria/{slug}`, mostrando as publicações com situação **Publicado** daquela seção.
Elas aparecem no menu **Categorias** do cabeçalho e nos cartões "Explore por categoria"
da página inicial.

- A categoria **Home** (`slug = home`) não tem tela própria: ela leva à página inicial.
- A categoria **Eventos** (`slug = eventos`) mostra também o calendário (próximos
  eventos e eventos anteriores).
- Os ícones usam o Bootstrap Icons, carregado por CDN no `layouts/publico.php`;
  sem internet os ícones não aparecem, mas o restante da tela funciona.

Para criar uma nova categoria, basta inserir uma linha no banco; menu, cartão e tela
aparecem automaticamente:

```sql
INSERT INTO publication_types (name, slug, description, icon, order_position, active)
VALUES ('Estágios', 'estagios', 'Vagas e orientações de estágio', 'bi-briefcase', 7, TRUE);
```

O `slug` deve ser único, em minúsculas, sem acentos e sem espaços (use hífen).

## Imagens de capa

O formulário de publicação tem o campo **Imagem de capa**. Ele aceita JPG, PNG e WebP, com até
2 MB. O arquivo vai para `public/uploads/` com um nome aleatório. O portal registra cada
arquivo na tabela `media` (nome original, caminho, tipo, tamanho e quem enviou). A coluna
`publications.media_id` aponta para esse registro.

Requisitos no servidor:

- O PHP precisa de permissão de escrita em `public/uploads/`.
- `upload_max_filesize` e `post_max_size` do `php.ini` precisam ser de pelo menos 2 MB
  (o padrão do PHP é 2 MB e 8 MB).
- Um envio maior que `post_max_size` mostra a tela "Arquivo muito grande". O usuário perde o
  texto digitado no formulário.

### Exclusão de imagens

O portal não apaga o arquivo do disco. Ao excluir uma publicação ou trocar a capa, ele
marca a mídia antiga como excluída: `media.status` vira `FALSE` e `media.deleted_at` recebe
a data. O arquivo continua em `public/uploads/`.

## Perfis de acesso (users.profile_id)

Quem se cadastra pela tela pública (`/cadastroUsuario`) recebe o perfil
**Visualização** (código 2), definido em `Auth::PERFIL_CADASTRO_PADRAO`, dentro de
`App\Core\Auth`. A conta nasce **inativa** e só consegue entrar depois que um
Administrador a aprovar na tela `/admin/usuarios`.

| Código | Perfil        | Publicações/Eventos  | Usuários     |
| ------ | ------------- | -------------------- | ------------ |
| 1      | Acesso Total  | Só visualiza         | Só visualiza |
| 2      | Visualização  | Só visualiza         | Sem acesso   |
| 3      | Divulgação    | Cria e edita         | Sem acesso   |
| 4      | Administrador | Cria, edita e exclui | Cria e edita |

As regras estão centralizadas em `App\Core\Auth` (métodos `podeGerenciarConteudo()`,
`podeExcluirConteudo()`, `podeVerUsuarios()`, `podeGerenciarUsuarios()`) — é o único
lugar que precisa mudar se as regras de permissão mudarem.

## Git: o que é versionado

Cada instalação tem os seus próprios dados (senha do banco, endereço), por isso o
`.gitignore` deixa **fora do Git**:

| Arquivo                                          | Motivo                                              |
| ------------------------------------------------ | --------------------------------------------------- |
| `config/app.php`, `config/database.php`          | Criados pelo instalador; têm a senha real do banco  |
| `config/install.lock`, `config/install.key`      | Controle do instalador; um lock commitado trancaria todo clone novo |
| `config/*.bak`, `config/*.tmp*`                  | Cópias e temporários do instalador                  |
| `public/uploads/*` (menos o `.htaccess`)         | Imagens enviadas pelos usuários                     |

Vão para o Git os modelos `config/app.example.php` e `config/database.example.php`, e o
arquivo `config/bancos.php` (ele não tem usuário nem senha).

**Versione também o instalador** (`public/install`, `app/Install`, `database/instalar.php`).
Enquanto esses arquivos não forem commitados, o Git não os conhece: eles não chegam aos clones
e, se alguém apagar a pasta `public/install`, nem o `git restore` consegue trazê-la de volta.

**Fluxo de quem clona o projeto:** `git clone`, abrir `/install/` no navegador e pronto. Ao
atualizar o código depois: `git pull` e `php database/instalar.php --atualizar` (aplica só as
versões novas do banco).

**Se o repositório já tinha `config/app.php` e `config/database.php` versionados**, o
`.gitignore` sozinho não basta (o Git continua acompanhando arquivos já versionados). Pare de
acompanhá-los sem apagá-los do seu disco:

```
git rm --cached config/app.php config/database.php
git add .gitignore config/app.example.php config/database.example.php
git commit -m "Configuração local fora do Git; modelos de configuração"
```

Se você tinha usado `git update-index --skip-worktree` nesses arquivos, desfaça antes com
`git update-index --no-skip-worktree config/app.php config/database.php`.

Se algum dia uma senha **real** do banco foi commitada, ela continua no histórico do
repositório: troque a senha no PostgreSQL.

## Instalação manual (alternativa)

As seções a seguir (até "Segurança já incluída") descrevem o caminho manual: editar os
arquivos de `config/`, rodar os `.bat` do banco e criar o Administrador à mão. Só é
preciso se você não quiser usar o [instalador](#instalador).

Primeiro crie os dois arquivos de configuração a partir dos modelos e depois ajuste os
valores, conforme as seções seguintes:

```
copy config\app.example.php config\app.php
copy config\database.example.php config\database.php
```

(no Linux/macOS, use `cp` com as barras normais).

## Preparação do Banco de Dados

### Pré-requisitos

- PostgreSQL instalado (testado na versão 18).
- Usuário `postgres` configurado com senha.
- Caminhos dos `.bat` conferidos (veja a seção seguinte).

### Passo a passo

Os arquivos abaixo ficam na pasta `portal_si/database`. Execute-os com duplo clique
(ou pelo Prompt de Comando, de dentro dessa pasta), nesta ordem:

1. **`Create Database.bat`** — pergunta porta, usuário e senha do PostgreSQL, cria o
   banco `portal_si` (caso não exista) e a tabela `schema_version`, responsável por
   controlar as versões já aplicadas.
2. **`Update Database.bat`** — verifica os arquivos `.sql` da pasta `database/versions`
   e executa apenas os que ainda não foram aplicados, registrando cada um na tabela
   `schema_version`. Rode de novo sempre que receber uma nova versão do banco.

Depois confira se os dados em `config/database.php` (host, banco `portal_si`, usuário,
senha e porta) são os mesmos que você usou nos `.bat`.

> Os arquivos `.bat` só funcionam no Windows. Em Linux/macOS, execute os arquivos de
> `database/versions` em ordem com o `psql`.

## Ajustando os caminhos dos arquivos .bat

Os `.bat` têm caminhos fixos que dependem de como as ferramentas foram instaladas.
Abra o arquivo com o Bloco de Notas e ajuste a linha indicada.

| Arquivo                    | Linha a ajustar                                              | O que mudar                                              |
| -------------------------- | ------------------------------------------------------------ | -------------------------------------------------------- |
| `Create Database.bat`      | `SET PG_PATH="C:\Program Files\PostgreSQL\18\bin\psql.exe"` | Número da versão (`18`) ou a pasta de instalação         |
| `Update Database.bat`      | `SET PG_PATH="C:\Program Files\PostgreSQL\18\bin\psql.exe"` | Igual ao anterior                                        |
| `Create Admin User.bat`    | `C:\xampp\php\php.exe create_admin.php ...`                  | Pasta onde o PHP está instalado                          |

**PostgreSQL em outra versão ou pasta.** Mantenha as aspas. Exemplo para a versão 16:

```bat
SET PG_PATH="C:\Program Files\PostgreSQL\16\bin\psql.exe"
```

Para descobrir o caminho certo, procure o `psql.exe` dentro da pasta `bin` do PostgreSQL.

**PHP fora do `C:\xampp`.** Troque o início da linha pelo caminho do seu `php.exe`:

```bat
D:\xampp\php\php.exe create_admin.php "%NOME%" "%EMAIL%" "%SENHA%"
```

**Alternativa sem caminho fixo.** Se a pasta `bin` do PostgreSQL e a pasta do PHP
estiverem no `PATH` do Windows, dá para usar só o nome do programa:

```bat
SET PG_PATH="psql"
```

```bat
php create_admin.php "%NOME%" "%EMAIL%" "%SENHA%"
```

Para testar, abra o Prompt de Comando e digite `psql --version` e `php -v`. Se os dois
mostrarem a versão, o `PATH` está certo.

## Criando o primeiro Administrador

Como ninguém nasce Administrador, e só um Administrador consegue promover outra conta
pela tela `/admin/usuarios`, execute o arquivo abaixo (na pasta `portal_si/database`)
para criar o primeiro usuário com perfil Administrador. Ele também serve para
recuperar o acesso se a senha do administrador se perder:

`Create Admin User.bat`

O arquivo pede nome, e-mail e senha. Se o e-mail informado já existir, a conta é
promovida a Administrador e a senha é atualizada; se não existir, uma conta nova já é
criada como Administrador. Depois de usar, o arquivo pode ser apagado — o
`create_admin.php` só funciona pela linha de comando, nunca pelo navegador.

Esse `.bat` usa o PHP do computador (veja a seção sobre os caminhos acima).

## Alterando caminho

No arquivo `config/app.php` (criado pelo instalador ou copiado de `config/app.example.php`)
você define o caminho da pasta pública do portal na
constante `BASE_URL`. Ela é usada por `url()` e `asset()` para montar todos os links e
os caminhos de CSS/JS da aplicação, e também pelo roteador para descobrir qual página
foi pedida.

O valor é o caminho da URL (a parte depois de `http://localhost`) até a pasta
`public`, sem barra no final:

| Onde o projeto está                              | Valor de `BASE_URL`                  |
| ------------------------------------------------ | ------------------------------------ |
| `htdocs/portal_si`                               | `/portal_si/public`                  |
| `htdocs/teste/portal_si`                         | `/teste/portal_si/public`            |
| `htdocs/proj_exten_IV/portal_si`                 | `/proj_exten_IV/portal_si/public`    |
| Virtual Host com `DocumentRoot` em `public/`     | `''` (vazio)                         |

Exemplo:

```php
define('BASE_URL', '/teste/portal_si/public');
```

## Segurança já incluída

- Senhas com `password_hash`/`password_verify`.
- Todas as consultas usam PDO com parâmetros nomeados (sem concatenar SQL).
- Token CSRF obrigatório em todo formulário POST (`csrf_campo()` no formulário,
  conferido pelo `Router` antes de chamar o controller).
- Erros de banco não são exibidos ao usuário; vão para `error_log()`.
- `session.cookie_httponly` ativado e `session_regenerate_id()` no login.
- Contas criadas pelo cadastro público ficam inativas até a aprovação de um
  Administrador.
- O instalador se tranca sozinho depois de usado (veja [Segurança do instalador](#segurança-do-instalador)).
- Envio de imagem de capa: o tipo é conferido pelo conteúdo do arquivo, o limite é 2 MB, o
  nome salvo no disco é aleatório e o `public/uploads/.htaccess` bloqueia tudo, menos imagens.
