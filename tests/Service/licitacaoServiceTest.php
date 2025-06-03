<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Services\LicitacaoService;

/**
 * @covers \App\Services\LicitacaoService
 */
class LicitacaoServiceTest extends TestCase
{
    /**
     * Testa que, se fetchHtml retornar falso logo na página 1,
     * listarPorUasg() devolve array vazio.
     */
    public function testFindByUasgReturnsEmptyWhenFetchHtmlFails(): void
    {
        /** @var \App\Services\LicitacaoService|\PHPUnit\Framework\MockObject\MockObject $service */
        $service = $this->getMockBuilder(LicitacaoService::class)
                        ->onlyMethods(['fetchHtml', 'extractFormsFromHtml'])
                        ->getMock();
        $service->method('fetchHtml')->willReturn(false);

        $result = $service->listarPorUasg('12345');
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Testa que, se o item não estiver na página 1,
     * mas estiver na página 2, retornará aquele item e interrompe a busca.
     */
    public function testFindByUasgFindsItemOnPageTwoAndStops(): void
    {
        $fakeItemPage1 = [
            'ordem' => '1',
            'orgao' => 'ÓRGÃO QUALQUER',
            'uasg' => '11111',
            'modalidade_numero' => '',
            'objeto' => '',
            'edital_inicio' => '',
            'endereco' => '',
            'telefone' => '',
            'fax' => '',
            'entrega_proposta' => '',
            'itens_download_url' => ''
        ];
        $fakeItemPage2 = [
            'ordem' => '2',
            'orgao' => 'ÓRGÃO QUALQUER 2',
            'uasg' => '22222',
            'modalidade_numero' => '',
            'objeto' => '',
            'edital_inicio' => '',
            'endereco' => '',
            'telefone' => '',
            'fax' => '',
            'entrega_proposta' => '',
            'itens_download_url' => ''
        ];

        /** @var \App\Services\LicitacaoService|\PHPUnit\Framework\MockObject\MockObject $service */
        $service = $this->getMockBuilder(LicitacaoService::class)
                        ->onlyMethods(['fetchHtml', 'extractFormsFromHtml'])
                        ->getMock();

        $service->expects($this->exactly(2))
                ->method('fetchHtml')
                ->withConsecutive(
                    [$this->stringContains('?Pagina=1')],
                    [$this->stringContains('?Pagina=2')]
                )
                ->willReturnOnConsecutiveCalls('<html1>', '<html2>');

        $service->expects($this->exactly(2))
                ->method('extractFormsFromHtml')
                ->withConsecutive(
                    [$this->equalTo('<html1>')],
                    [$this->equalTo('<html2>')]
                )
                ->willReturnOnConsecutiveCalls(
                    [$fakeItemPage1],
                    [$fakeItemPage2]
                );

        $result = $service->listarPorUasg('22222');

        $this->assertCount(1, $result);
        $this->assertSame($fakeItemPage2, $result[0]);
    }

    /**
     * Testa listarPorNumeroPregao() retornando múltiplos itens quando a regex bater em várias páginas.
     */
    public function testFindByNumeroPregaoFindsMultipleMatches(): void
    {
        $fakeItemA = [
            'ordem' => '1',
            'orgao' => 'ORG A',
            'uasg' => '11111',
            'modalidade_numero' => 'Pregão Eletrônico Nº 90007/2025 - (Lei Nº 14.133/2021)',
            'objeto' => '',
            'edital_inicio' => '',
            'endereco' => '',
            'telefone' => '',
            'fax' => '',
            'entrega_proposta' => '',
            'itens_download_url' => ''
        ];
        $fakeItemB = [
            'ordem' => '2',
            'orgao' => 'ORG B',
            'uasg' => '22222',
            'modalidade_numero' => 'Pregão Eletrônico Nº 90007/2025 - (Lei Nº 14.133/2021)',
            'objeto' => '',
            'edital_inicio' => '',
            'endereco' => '',
            'telefone' => '',
            'fax' => '',
            'entrega_proposta' => '',
            'itens_download_url' => ''
        ];
        $fakeItemC = [
            'ordem' => '3',
            'orgao' => 'ORG C',
            'uasg' => '33333',
            'modalidade_numero' => 'Pregão Eletrônico Nº 90008/2025 - (Lei Nº 14.133/2021)',
            'objeto' => '',
            'edital_inicio' => '',
            'endereco' => '',
            'telefone' => '',
            'fax' => '',
            'entrega_proposta' => '',
            'itens_download_url' => ''
        ];

        /** @var \App\Services\LicitacaoService|\PHPUnit\Framework\MockObject\MockObject $service */
        $service = $this->getMockBuilder(LicitacaoService::class)
                        ->onlyMethods(['fetchHtml', 'extractFormsFromHtml'])
                        ->getMock();

        $service->expects($this->exactly(3))
                ->method('fetchHtml')
                ->withConsecutive(
                    [$this->stringContains('?Pagina=1')],
                    [$this->stringContains('?Pagina=2')],
                    [$this->stringContains('?Pagina=3')]
                )
                ->willReturnOnConsecutiveCalls(
                    '<html1>',
                    '<html2>',
                    false
                );

        $service->expects($this->exactly(2))
                ->method('extractFormsFromHtml')
                ->withConsecutive(
                    [$this->equalTo('<html1>')], 
                    [$this->equalTo('<html2>')]  
                )
                ->willReturnOnConsecutiveCalls(
                    [$fakeItemA, $fakeItemC], 
                    [$fakeItemB] 
                );

        $result = $service->listarPorNumeroPregao('90007');

        $this->assertCount(2, $result);
        $this->assertContains($fakeItemA, $result);
        $this->assertContains($fakeItemB, $result);
    }

    /**
     * Testa que, se não encontrar nenhum pregão, retorna array vazio.
     */
    public function testFindByNumeroPregaoReturnsEmptyWhenNoneMatch(): void
    {
        $fakeItem = [
            'ordem'    => '1',
            'orgao'    => 'ORG X',
            'uasg'     => '99999',
            'modalidade_numero' => 'Pregão Eletrônico Nº 88888/2025',
            'objeto'   => '',
            'edital_inicio' => '',
            'endereco' => '',
            'telefone' => '',
            'fax'      => '',
            'entrega_proposta' => '',
            'itens_download_url' => ''
        ];

        /** @var \App\Services\LicitacaoService|\PHPUnit\Framework\MockObject\MockObject $service */
        $service = $this->getMockBuilder(LicitacaoService::class)
                        ->onlyMethods(['fetchHtml', 'extractFormsFromHtml'])
                        ->getMock();

        $service->method('fetchHtml')->willReturn('<html>');
        $service->method('extractFormsFromHtml')->willReturn([$fakeItem]);

        $result = $service->listarPorNumeroPregao('90007');
        $this->assertEmpty($result);
    }
}
