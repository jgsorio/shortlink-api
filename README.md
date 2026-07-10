# Shortlink API

API REST para encurtamento de URLs construída com [Laravel 10](https://laravel.com) e autenticação via [Laravel Sanctum](https://laravel.com/docs/10.x/sanctum). Usuários autenticados podem criar, listar e desativar shortlinks; qualquer visitante pode acessar uma URL curta e ser redirecionado para o destino original, com contagem automática de cliques.

[![PHP](https://img.shields.io/badge/PHP-%5E8.1-777bb4)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/Laravel-%5E10.10-ff2d20)](https://laravel.com)
[![License](https://img.shields.io/badge/license-MIT-blue)](#licença)

## Sumário

- [Funcionalidades](#funcionalidades)
- [Stack](#stack)
- [Arquitetura e estrutura do projeto](#arquitetura-e-estrutura-do-projeto)
- [Modelo de dados](#modelo-de-dados)
- [Pré-requisitos](#pré-requisitos)
- [Instalação](#instalação)
- [Variáveis de ambiente](#variáveis-de-ambiente)
- [Executando a aplicação](#executando-a-aplicação)
- [Documentação da API](#documentação-da-api)
- [Testes](#testes)
- [Qualidade de código](#qualidade-de-código)
- [Licença](#licença)

## Funcionalidades

- Cadastro e autenticação de usuários via token (Sanctum).
- Criação de shortlinks a partir de uma URL válida, com código curto gerado automaticamente.
- Listagem paginada dos shortlinks do usuário autenticado.
- Desativação (soft-disable) de shortlinks.
- Redirecionamento público (`GET /api/shortlink/{short_url}`) com contagem de cliques e bloqueio de links desativados.

## Stack

| Camada              | Tecnologia                                     |
| ------------------- | ---------------------------------------------- |
| Linguagem           | PHP ^8.1                                       |
| Framework           | Laravel ^10.10                                 |
| Autenticação        | Laravel Sanctum ^3.3                           |
| Banco de dados      | MySQL (SQLite em memória nos testes)           |
| Cache / Filas       | Redis (configurado, ver [nota abaixo](#redis)) |
| Testes              | PHPUnit ^10.1                                  |
| Qualidade de código | Laravel Pint                                   |

### Redis

O projeto já traz uma conexão Redis totalmente configurada em `config/database.php` (bancos `default` e `cache`, via `phpredis`), incluindo opções de contexto TLS para uso com provedores gerenciados. Porém, nos drivers padrão do `.env.example` (`CACHE_DRIVER`, `SESSION_DRIVER`, `QUEUE_CONNECTION`) o Redis **ainda não está ativo** e não há chamadas a `Cache::`/`Redis::` no código da aplicação — a infraestrutura está pronta para uma futura camada de cache (por exemplo, cache do redirecionamento de shortlinks), mas ainda não é usada.

## Arquitetura e estrutura do projeto

Projeto Laravel padrão, organizado por responsabilidade:

```
app/
├── Http/
│   ├── Controllers/Api/
│   │   ├── AuthController.php        # login e registro
│   │   └── ShortlinkController.php   # CRUD e redirecionamento de shortlinks
│   ├── Requests/
│   │   └── ShortlinkRequest.php      # validação de criação de shortlink
│   └── Resources/
│       └── ShortlinkResource.php     # formatação da resposta JSON
├── Models/
│   ├── ShortLink.php
│   └── User.php
database/
├── factories/                        # factories para testes (User, ShortLink)
└── migrations/                       # schema do banco (users, sanctum, short_links)
routes/
└── api.php                           # definição das rotas da API
tests/
└── Feature/Api/
    ├── AuthControllerTest.php
    └── ShortlinkControllerTest.php
```

## Modelo de dados

Tabela `short_links`:

| Coluna                      | Tipo           | Descrição                                    |
| --------------------------- | -------------- | -------------------------------------------- |
| `id`                        | bigint         | Identificador                                |
| `user_id`                   | bigint (FK)    | Dono do shortlink                            |
| `url`                       | string         | URL de destino original                      |
| `short_url`                 | string (único) | Código curto gerado                          |
| `clicks`                    | integer        | Contador de acessos (default `0`)            |
| `is_active`                 | boolean        | Se o link ainda redireciona (default `true`) |
| `created_at` / `updated_at` | timestamp      | Controle de datas                            |

## Pré-requisitos

- PHP ^8.1 com as extensões padrão do Laravel
- Composer
- MySQL (ou outro banco compatível) para o ambiente de desenvolvimento
- Node.js e npm (opcional, apenas para os assets padrão do Laravel — o projeto é API-only e não exige build de frontend)

## Instalação

```bash
# clone o repositório
git clone <url-do-repositorio>
cd shortlink-api

# instale as dependências PHP
composer install

# copie o arquivo de ambiente
cp .env.example .env

# gere a chave da aplicação
php artisan key:generate
```

## Variáveis de ambiente

Configure o `.env` com as credenciais do seu banco de dados. As principais variáveis para este projeto são:

```env
APP_NAME=Shortlink
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=shortlink
DB_USERNAME=root
DB_PASSWORD=

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

> `APP_URL` é usada para montar a URL completa retornada em `short_url` (ex.: `http://localhost/AbCdEfGh`).

## Executando a aplicação

```bash
# rode as migrations
php artisan migrate

# suba o servidor de desenvolvimento
php artisan serve
```

A API ficará disponível em `http://localhost:8000` (ou na `APP_URL` configurada).

## Documentação da API

Todas as rotas são prefixadas com `/api`. Rotas marcadas como **autenticadas** exigem um header `Authorization: Bearer <token>` obtido no login.

### Autenticação

#### `POST /api/auth/register`

Cria um novo usuário.

**Body**

```json
{
    "name": "Fulano",
    "email": "fulano@email.com",
    "password": "senha-segura"
}
```

**Resposta `201`**

```json
{ "message": "Register success" }
```

#### `POST /api/auth/login`

Autentica um usuário e retorna um token de acesso.

**Body**

```json
{
    "email": "fulano@email.com",
    "password": "senha-segura"
}
```

**Resposta `200`**

```json
{ "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx" }
```

**Resposta `401`**

```json
{ "message": "Invalid credentials" }
```

### Shortlinks

#### `GET /api/shortlink` 🔒

Lista, de forma paginada, os shortlinks pertencentes ao usuário autenticado.

**Resposta `200`**

```json
{
    "data": [
        {
            "id": 1,
            "url": "https://google.com",
            "short_url": "http://localhost/AbCdEfGh",
            "clicks": 0,
            "is_active": true,
            "created_at": "2 minutes ago"
        }
    ],
    "links": { "...": "..." },
    "meta": { "...": "..." }
}
```

#### `POST /api/shortlink` 🔒

Cria um novo shortlink a partir de uma URL válida.

**Body**

```json
{ "url": "https://google.com" }
```

**Resposta `201`**

```json
{
    "data": {
        "id": 1,
        "url": "https://google.com",
        "short_url": "http://localhost/AbCdEfGh",
        "clicks": 0,
        "is_active": true,
        "created_at": "a few seconds ago"
    }
}
```

#### `GET /api/shortlink/{short_url}`

Rota pública de redirecionamento. Busca o shortlink pelo código, incrementa o contador de `clicks` e redireciona (`302`) para a URL original.

**Resposta**

- `302` → `Location: <url original>`
- `404` → link inexistente ou desativado

#### `DELETE /api/shortlink/{shortLink}` 🔒

Desativa (soft-disable) um shortlink — o registro não é removido do banco, apenas marcado como `is_active = false` e passa a responder `404` no redirecionamento.

**Resposta**

- `204 No Content`

> 🔒 = requer autenticação via Sanctum.

## Testes

O projeto usa PHPUnit com banco SQLite em memória (`phpunit.xml`), cobrindo os fluxos de autenticação e de shortlinks (criação, listagem paginada, redirecionamento com incremento de cliques, bloqueio de links desativados e desativação).

```bash
php artisan test
# ou
./vendor/bin/phpunit
```

## Qualidade de código

O projeto usa [Laravel Pint](https://laravel.com/docs/10.x/pint) para padronização de estilo:

```bash
./vendor/bin/pint
```

## Licença

Este projeto está licenciado sob os termos da licença MIT (conforme declarado em `composer.json`).
