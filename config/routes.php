<?php
use App\Controllers\LicitacaoController;

$router->get('/api/licitacoes', [LicitacaoController::class, 'index']);
$router->get('/api/licitacoes/uasg/{codigo}', [LicitacaoController::class, 'findByUasg']);
