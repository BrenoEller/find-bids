<?php
use App\Controllers\LicitacaoController;

$router->get('/api/licitacoes', [LicitacaoController::class, 'index']);