<?php
namespace App\Controllers;

use App\Services\LicitacaoService;

class LicitacaoController
{
    public function index(): string
    {
        // Não usamos echo aqui: só retornamos a string JSON
        $service = new LicitacaoService();
        $dados   = $service->listasDoDia();

        return json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}