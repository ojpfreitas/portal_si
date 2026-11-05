<?php

// public/index.php
// Front controller: toda requisição da aplicação entra por aqui.

require __DIR__ . '/../app/bootstrap.php';

use App\Core\Router;

$router = new Router();

require APP_PATH . '/routes.php';

$router->despachar();
