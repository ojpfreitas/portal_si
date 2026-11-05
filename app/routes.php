<?php

// app/routes.php
// Tabela de rotas. A variável $router é criada em public/index.php.
// {id} aceita apenas números e {slug} aceita texto-com-hifens (ex.: /categoria/acoes-sociais);
// o valor é repassado ao método do controller.

use App\Controllers\AuthController;
use App\Controllers\CategoriaController;
use App\Controllers\ConteudoController;
use App\Controllers\DashboardController;
use App\Controllers\EventoController;
use App\Controllers\HomeController;
use App\Controllers\UsuarioController;

// ---- Site público ----
$router->get('/', [HomeController::class, 'index']);
$router->get('/categoria/{slug}', [CategoriaController::class, 'show']);

// ---- Autenticação ----
$router->get('/login', [AuthController::class, 'loginForm']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/cadastroUsuario', [AuthController::class, 'cadastroForm']);
$router->post('/cadastroUsuario', [AuthController::class, 'cadastro']);
$router->get('/logout', [AuthController::class, 'logout']);

// ---- Painel ----
$router->get('/admin', [DashboardController::class, 'index']);

// ---- Publicações e notícias ----
$router->get('/admin/conteudos', [ConteudoController::class, 'index']);
$router->get('/admin/conteudos/novo', [ConteudoController::class, 'create']);
$router->post('/admin/conteudos', [ConteudoController::class, 'store']);
$router->get('/admin/conteudos/{id}', [ConteudoController::class, 'edit']);
$router->post('/admin/conteudos/{id}', [ConteudoController::class, 'update']);
$router->get('/admin/conteudos/{id}/excluir', [ConteudoController::class, 'confirmarExclusao']);
$router->post('/admin/conteudos/{id}/excluir', [ConteudoController::class, 'destroy']);

// ---- Calendário de eventos ----
$router->get('/admin/eventos', [EventoController::class, 'index']);
$router->get('/admin/eventos/novo', [EventoController::class, 'create']);
$router->post('/admin/eventos', [EventoController::class, 'store']);
$router->get('/admin/eventos/{id}', [EventoController::class, 'edit']);
$router->post('/admin/eventos/{id}', [EventoController::class, 'update']);
$router->get('/admin/eventos/{id}/excluir', [EventoController::class, 'confirmarExclusao']);
$router->post('/admin/eventos/{id}/excluir', [EventoController::class, 'destroy']);

// ---- Usuários ----
$router->get('/admin/usuarios', [UsuarioController::class, 'index']);
$router->get('/admin/usuarios/novo', [UsuarioController::class, 'create']);
$router->post('/admin/usuarios', [UsuarioController::class, 'store']);
$router->get('/admin/usuarios/{id}', [UsuarioController::class, 'edit']);
$router->post('/admin/usuarios/{id}', [UsuarioController::class, 'update']);
