<?php
namespace App\Controllers;

use App\Services\LicitacaoService;

class LicitacaoController
{
    public function index(): string
    {
        $pagina = isset($_GET['pagina']) && is_numeric($_GET['pagina']) ? (int) $_GET['pagina'] : null;

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

    public function findByUasg(array $params): string
    {
        $codigoUasg = trim($params['codigo'] ?? '');

        if ($codigoUasg === '') {
            http_response_code(400);
            return json_encode(
                ['error' => 'É necessário informar o número da UASG.'],
                JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
            );
        }

        $service = new LicitacaoService();
        $itens = $service->listarPorUasg($codigoUasg);

        if (empty($itens)) {
            http_response_code(404);
            return json_encode(
                ['error' => "Nenhuma licitação encontrada para UASG {$codigoUasg}."],
                JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
            );
        }

        return json_encode($itens, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    public function findByPregao(): string
    {
        $numeroPregao = isset($_GET['numero']) ? trim($_GET['numero']) : '';

        if ($numeroPregao === '') {
            http_response_code(400);
            return json_encode(
                ['error' => 'É necessário informar o número do pregão.'],
                JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
            );
        }

        $service = new LicitacaoService();
        $itens   = $service->listarPorNumeroPregao($numeroPregao);

        if (empty($itens)) {
            http_response_code(404);
            return json_encode(
                ['error' => "Nenhuma licitação encontrada para o pregão {$numeroPregao}."],
                JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
            );
        }

        return json_encode($itens, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}
