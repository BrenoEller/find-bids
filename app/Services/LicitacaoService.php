<?php
namespace App\Services;

class LicitacaoService
{
    private $cacheDir = __DIR__ . '/../../cache';
    private $cacheFile = 'licitacoes_dia.json';
    private $cacheTTL = 300; // 5 minutos

    public function listasDoDia(): array
    {
        $path = $this->cacheDir . '/' . $this->cacheFile;

        if (file_exists($path) && (time() - filemtime($path) < $this->cacheTTL)) {
            $dados = json_decode(file_get_contents($path), true);
            if (empty($dados)) {
                return ['error' => 'Nenhuma licitação encontrada para o dia de hoje.'];
            }
            return $dados;
        }

        $licitacoes = $this->scrapeComprasNet();

        if (empty($licitacoes)) {
            return ['error' => 'Nenhuma licitação encontrada para o dia de hoje.'];
        }

        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
        file_put_contents($path, json_encode($licitacoes, JSON_UNESCAPED_UNICODE));

        return $licitacoes;
    }

    private function scrapeComprasNet(): array
    {
        $url = 'https://comprasnet.gov.br/ConsultaLicitacoes/ConsLicitacaoDia.asp';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
        $html = curl_exec($ch);
        curl_close($ch);

        if (!$html) {
            return [];
        }

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'ISO-8859-1'));
        libxml_clear_errors();
        $xpath = new \DOMXPath($dom);

        $tableQuery = '//table[tbody/tr/th[1][normalize-space(text())="Órgão"]]';
        $tables = $xpath->query($tableQuery);
        if ($tables->length === 0) {
            return [];
        }

        $licitacoes = [];
        $rows = $xpath->query('tbody/tr', $tables->item(0));

        foreach ($rows as $tr) {
            $cells = $xpath->query('td', $tr);
            if ($cells->length < 6) {
                continue;
            }
            $licitacoes[] = [
                'orgao' => trim($cells->item(0)->textContent),
                'uasg' => trim($cells->item(1)->textContent),
                'numero' => trim($cells->item(2)->textContent),
                'modalidade' => trim($cells->item(3)->textContent),
                'objeto' => trim($cells->item(4)->textContent),
                'dataAbertura' => trim($cells->item(5)->textContent),
            ];
        }

        return $licitacoes;
    }
}