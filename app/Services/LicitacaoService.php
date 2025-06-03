<?php
namespace App\Services;

class LicitacaoService
{
    private string $cacheDir = __DIR__ . '/../../cache';
    private string $cacheFile = 'licitacoes_dia.json';
    private int $cacheTTL = 300;  // 5 minutos
    private int $curlTimeout = 15;   // timeout em segundos para cada cURL

    public function listasDoDia(): array
    {
        $path = $this->cacheDir . '/' . $this->cacheFile;

        if (file_exists($path) && (time() - filemtime($path) < $this->cacheTTL)) {
            $cached = json_decode(file_get_contents($path), true);
            if (!empty($cached)) {
                return $cached;
            }
        }

        $todos = $this->scrapeAllPages();

        if (empty($todos)) {
            $todos = $this->listarPorPagina(1);
        }

        if (!empty($todos)) {
            if (!is_dir($this->cacheDir)) {
                mkdir($this->cacheDir, 0755, true);
            }
            file_put_contents($path, json_encode($todos, JSON_UNESCAPED_UNICODE));
        }

        return $todos;
    }

    /**
     * usca apenas a página específica “?Pagina=$p”
    */
    public function listarPorPagina(int $p): array
    {
        $baseUrl = 'https://comprasnet.gov.br/ConsultaLicitacoes/ConsLicitacaoDia.asp';
        $url = $baseUrl . '?Pagina=' . $p;

        $html = $this->fetchHtml($url);
        if ($html === false) {
            return [];
        }

        return $this->extractFormsFromHtml($html);
    }

    /**
     * (lógica que varre TODAS as páginas, via getLastPageNumber e multiFetch ou fallback iterativo. Serve apenas para quem chamar listasDoDia().)
    */
    private function scrapeAllPages(): array
    {
        return [];
    }

    /**
     * Faz um cURL simples para a URL e retorna o HTML ou false.
     */
    private function fetchHtml(string $url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
        curl_setopt($ch, CURLOPT_REFERER, 'https://comprasnet.gov.br/');
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->curlTimeout);

        $html = curl_exec($ch);
        if ($html === false) {
            curl_close($ch);
            return false;
        }
        curl_close($ch);
        return $html;
    }

    /**
     * Recebe um HTML de página de resultados e extrai cada <form> dentro de <table class="tex3">.
     * Retorna um array de itens associativos com os campos (ordem, orgao, uasg, modalidade_numero, etc.).
     */
    private function extractFormsFromHtml(string $html): array
    {
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'ISO-8859-1'));
        libxml_clear_errors();
        $xpath = new \DOMXPath($dom);

        $forms = $xpath->query("//table[contains(@class,'tex3')]//form");
        if ($forms->length === 0) {
            return [];
        }

        $items = [];
        foreach ($forms as $form) {
            $items[] = $this->extrairItemDeForm($form, $dom);
        }
        return $items;
    }

    /**
     * Extrai cada campo de um <form> específico (ordem, orgao, uasg, modalidade_numero, objeto, etc.).
     */
    private function extrairItemDeForm(\DOMElement $form, \DOMDocument $dom): array
    {
        $innerHtml = '';
        foreach ($form->childNodes as $child) {
            $innerHtml .= $dom->saveHTML($child);
        }

        $linhasHtml = preg_split('/<br\s*\/?>/i', $innerHtml);

        $linhasTexto = [];
        foreach ($linhasHtml as $l) {
            $t = trim(strip_tags($l));
            if ($t !== '') {
                $linhasTexto[] = $t;
            }
        }

        $item = [
            'ordem' => '',
            'orgao' => '',
            'uasg' => '',
            'modalidade_numero' => '',
            'objeto' => '',
            'edital_inicio' => '',
            'endereco' => '',
            'telefone' => '',
            'fax' => '',
            'entrega_proposta' => '',
            'itens_download_url' => '',
        ];

        if (isset($linhasTexto[0])) {
            $raw = $this->normalizeSpaces($linhasTexto[0]);
            if (preg_match('/^(\d+)\s*(.*)$/u', $raw, $m)) {
                $item['ordem'] = $m[1];
                $nomeOrgao = $m[2];
            } else {
                $nomeOrgao = $raw;
            }
            $item['orgao'] = $this->normalizeSpaces($nomeOrgao);
        }

        foreach ($linhasTexto as $linhaOriginal) {
            $linha = $this->normalizeSpaces($linhaOriginal);

            if (stripos($linha, 'Código da UASG:') !== false) {
                $v = preg_replace('/^Código da UASG:\s*/iu', '', $linha, 1);
                $item['uasg'] = $this->normalizeSpaces($v);
                continue;
            }

            if (mb_stripos($linha, 'Nº ') !== false) {
                $item['modalidade_numero'] = $linha;
                continue;
            }

            if (stripos($linha, 'Objeto:') !== false) {
                $v = preg_replace('/^Objeto:\s*/iu', '', $linha, 1);
                $v = preg_replace('/^Objeto:\s*/iu', '', $v, 1); // limpa duplicado
                $item['objeto'] = $this->normalizeSpaces($v);
                continue;
            }

            if (stripos($linha, 'Edital a partir de:') !== false) {
                $v = preg_replace('/^Edital a partir de:\s*/iu', '', $linha, 1);
                $item['edital_inicio'] = $this->normalizeSpaces($v);
                continue;
            }

            if (stripos($linha, 'Endereço:') !== false) {
                $v = preg_replace('/^Endereço:\s*/iu', '', $linha, 1);
                $item['endereco'] = $this->normalizeSpaces($v);
                continue;
            }

            if (stripos($linha, 'Telefone:') !== false) {
                $v = preg_replace('/^Telefone:\s*/iu', '', $linha, 1);
                $item['telefone'] = $this->normalizeSpaces($v);
                continue;
            }

            if (stripos($linha, 'Fax:') !== false) {
                $v = preg_replace('/^Fax:\s*/iu', '', $linha, 1);
                $item['fax'] = $this->normalizeSpaces($v);
                continue;
            }

            if (stripos($linha, 'Entrega da Proposta:') !== false) {
                $v = preg_replace('/^Entrega da Proposta:\s*/iu', '', $linha, 1);
                $item['entrega_proposta'] = $this->normalizeSpaces($v);
                continue;
            }
        }

        $xpath = new \DOMXPath($dom);

        $exprInput = ".//input[
            contains(
                translate(@onclick,
                    'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
                    'abcdefghijklmnopqrstuvwxyz'
                ),
                'visualizaritens'
            )
        ]";
        $inputs = $xpath->query($exprInput, $form);

        $onclick = '';

        if ($inputs->length > 0) {
            $node = $inputs->item(0);
            if ($node instanceof \DOMElement) {
                $onclick = $node->getAttribute('onclick');
            }
        } else {
            $exprLink = ".//a[
                contains(
                    translate(@onclick,
                        'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
                        'abcdefghijklmnopqrstuvwxyz'
                    ),
                    'visualizaritens'
                )
            ]";
            $links = $xpath->query($exprLink, $form);

            if ($links->length > 0) {
                $node = $links->item(0);
                if ($node instanceof \DOMElement) {
                    $onclick = $node->getAttribute('onclick');
                }
            }
        }

        if ($onclick !== '') {
            if (preg_match(
                "/visualizaritens\s*\(\s*document\.([^,]+)\s*,\s*'([^']+)'\s*\)/i",
                $onclick,
                $m
            )) {
                $queryString = $m[2];

                $queryString = str_replace(
                    ['modppr=', 'numppr='],
                    ['modprp=', 'numprp='],
                    $queryString
                );

                $baseUrlItens = 'https://comprasnet.gov.br/ConsultaLicitacoes/download/download_editais_detalhe.asp';
                $item['itens_download_url'] = $baseUrlItens . $queryString;
            }
        }

        return $item;
    }

    /**
     * Normaliza espaços em branco (incluindo NBSP), tabs e quebras de linha
    */
    private function normalizeSpaces(string $str): string
    {
        $str = preg_replace('/[\s\x{00A0}]+/u', ' ', $str);
        return trim($str, " \t\n\r\0\x0B\x{00A0}");
    }

    /**
     * Busca todas as licitações que tenham exatamente o código de UASG passado.
     * Interrompe se detectar que o gov “voltou” para a página 1 em vez de entregar vazio.
    */
    public function listarPorUasg(string $codigoUasg): array
    {
        $codigoUasg = trim($codigoUasg);
        $baseUrl = 'https://comprasnet.gov.br/ConsultaLicitacoes/ConsLicitacaoDia.asp';

        $pagina = 1;
        $html1 = $this->fetchHtml($baseUrl . '?Pagina=1');
        if ($html1 === false) {
            return []; 
        }

        $itensPagina1 = $this->extractFormsFromHtml($html1);
        if (!empty($itensPagina1)) {
            foreach ($itensPagina1 as $item) {
                if (isset($item['uasg']) && trim($item['uasg']) === $codigoUasg) {
                    return [$item];
                }
            }
        }

        $assinaturaPrimeiro = $itensPagina1[0]['ordem'] ?? null;

        $pagina = 2;
        while (true) {
            $url  = $baseUrl . '?Pagina=' . $pagina;
            $html = $this->fetchHtml($url);
            if ($html === false) {
                break;
            }

            $itensDaPagina = $this->extractFormsFromHtml($html);
            if (empty($itensDaPagina)) {
                break;
            }

            $primeiroDaPagina = $itensDaPagina[0]['ordem'] ?? null;
            if ($primeiroDaPagina !== null && $primeiroDaPagina === $assinaturaPrimeiro) {
                break;
            }

            foreach ($itensDaPagina as $item) {
                if (isset($item['uasg']) && trim($item['uasg']) === $codigoUasg) {
                    return [$item];
                }
            }

            $pagina++;
        }

        return []; 
    }

    /**
     * Busca licitações cujo 'modalidade_numero' contenha “Nº <numeroPregao>”.
    */
    public function listarPorNumeroPregao(string $numeroPregao): array
    {
        $numeroPregao = trim($numeroPregao);
        
        $pattern = '/\bNº\s*' . preg_quote($numeroPregao, '/') . '\b/i';

        $baseUrl = 'https://comprasnet.gov.br/ConsultaLicitacoes/ConsLicitacaoDia.asp';
        $resultados = [];

        $pagina = 1;
        $html1 = $this->fetchHtml($baseUrl . '?Pagina=1');
        if ($html1 === false) {
            return [];
        }

        $itensPagina1 = $this->extractFormsFromHtml($html1);
        if (!empty($itensPagina1)) {
            foreach ($itensPagina1 as $item) {
                if (isset($item['modalidade_numero']) &&
                    preg_match($pattern, $item['modalidade_numero'])
                ) {
                    $resultados[] = $item;
                }
            }
        }

        $assinaturaPrimeiro = $itensPagina1[0]['ordem'] ?? null;

        $pagina = 2;
        while (true) {
            $url = $baseUrl . '?Pagina=' . $pagina;
            $html = $this->fetchHtml($url);
            if ($html === false) {
                break;
            }

            $itensDaPagina = $this->extractFormsFromHtml($html);
            if (empty($itensDaPagina)) {
                break;
            }

            $primeiroDaPagina = $itensDaPagina[0]['ordem'] ?? null;
            if ($primeiroDaPagina !== null && $primeiroDaPagina === $assinaturaPrimeiro) {
                break;
            }

            foreach ($itensDaPagina as $item) {
                if (isset($item['modalidade_numero']) && preg_match($pattern, $item['modalidade_numero'])) {
                    $resultados[] = $item;
                }
            }

            $pagina++;
        }

        return $resultados;
    }
}
