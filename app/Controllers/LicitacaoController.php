<?php
namespace App\Controllers;

use App\Services\LicitacaoService;

class LicitacaoController
{
    public function index(): string
    {
        $pagina = isset($_GET['pagina']) && is_numeric($_GET['pagina'])
                    ? (int) $_GET['pagina']
                    : null;

        $service = new LicitacaoService();

        if ($pagina !== null) {
            $itens = $service->listarPorPagina($pagina);

            if (empty($itens)) {
                return json_encode(
                    ['error' => "Nenhuma licitação encontrada na página $pagina."],
                    JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
                );
            }
            return json_encode($itens, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }

        $dados = $service->listasDoDia();
        return json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}
