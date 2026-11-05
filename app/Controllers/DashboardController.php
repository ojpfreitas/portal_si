<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;

/**
 * Painel de controle exibido após o login.
 */
class DashboardController extends Controller
{
    public function index(): void
    {
        Auth::exigirLogin();

        $this->view('dashboard/index', [], 'admin');
    }
}
