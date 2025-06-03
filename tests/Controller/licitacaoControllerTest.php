<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Controllers\LicitacaoController;
use App\Services\LicitacaoService;

class LicitacaoControllerTest extends TestCase
{
    /**
     * Testa findByUasg() sem passar o parâmetro 'codigo':
     * - Deve retornar HTTP 400 e JSON com erro.
     */
    public function testFindByUasgWithoutParameterReturns400(): void
    {
        /** @var LicitacaoService|\PHPUnit\Framework\MockObject\MockObject $serviceMock */
        $serviceMock = $this->getMockBuilder(LicitacaoService::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $controller = new class($serviceMock) extends LicitacaoController {
            private $serviceOverride;
            public function __construct($svc)
            {
                $this->serviceOverride = $svc;
            }
            protected function createService(): LicitacaoService
            {
                return $this->serviceOverride;
            }
        };

        $json = $controller->findByUasg([]);
        $data = json_decode($json, true);

        $this->assertArrayHasKey('error', $data);
        $this->assertStringContainsString('É necessário informar o número da UASG', $data['error']);
    }

    /**
     * Testa findByUasg() quando o service retorna array vazio:
     * - Deve retornar HTTP 404 e JSON com erro.
     */
    public function testFindByUasgNotFoundReturns404(): void
    {
        /** @var LicitacaoService|\PHPUnit\Framework\MockObject\MockObject $serviceMock */
        $serviceMock = $this->getMockBuilder(LicitacaoService::class)
                            ->onlyMethods(['listarPorUasg'])
                            ->getMock();
        $serviceMock->method('listarPorUasg')
                    ->with('250005')
                    ->willReturn([]);

        $controller = new class($serviceMock) extends LicitacaoController {
            private $serviceOverride;
            public function __construct($svc)
            {
                $this->serviceOverride = $svc;
            }
            protected function createService(): LicitacaoService
            {
                return $this->serviceOverride;
            }
        };

        $params = ['codigo' => '250005'];
        $json   = $controller->findByUasg($params);
        $data   = json_decode($json, true);

        $this->assertArrayHasKey('error', $data);
        $this->assertStringContainsString('Nenhuma licitação encontrada para UASG 250005', $data['error']);
    }

    /**
     * Testa findByUasg() quando o service encontra exatamente um item:
     * - Espera que o JSON contenha aquele item e HTTP 200.
     */
    public function testFindByUasgReturnsItem(): void
    {
        $fakeItem = [
            'ordem' => '1',
            'orgao' => 'MINISTÉRIO DA SAÚDE',
            'uasg'  => '250005'
        ];

        /** @var LicitacaoService|\PHPUnit\Framework\MockObject\MockObject $serviceMock */
        $serviceMock = $this->getMockBuilder(LicitacaoService::class)
                            ->onlyMethods(['listarPorUasg'])
                            ->getMock();
        $serviceMock->method('listarPorUasg')
                    ->with('250005')
                    ->willReturn([$fakeItem]);

        $controller = new class($serviceMock) extends LicitacaoController {
            private $serviceOverride;
            public function __construct($svc)
            {
                $this->serviceOverride = $svc;
            }
            protected function createService(): LicitacaoService
            {
                return $this->serviceOverride;
            }
        };

        $params = ['codigo' => '250005'];
        $json   = $controller->findByUasg($params);
        $data   = json_decode($json, true);

        $this->assertIsArray($data);
        $this->assertCount(1, $data);
        $this->assertSame($fakeItem, $data[0]);
    }

    /**
     * Testa findByPregao() sem passar parâmetro 'numero':
     * - Deve retornar HTTP 400 e JSON com erro.
     */
    public function testFindByPregaoWithoutParameterReturns400(): void
    {
        /** @var LicitacaoService|\PHPUnit\Framework\MockObject\MockObject $serviceMock */
        $serviceMock = $this->getMockBuilder(LicitacaoService::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $controller = new class($serviceMock) extends LicitacaoController {
            private $serviceOverride;
            public function __construct($svc)
            {
                $this->serviceOverride = $svc;
            }
            protected function createService(): LicitacaoService
            {
                return $this->serviceOverride;
            }
        };

        unset($_GET['numero']);

        $json = $controller->findByPregao();
        $data = json_decode($json, true);

        $this->assertArrayHasKey('error', $data);
        $this->assertStringContainsString('É necessário informar o número do pregão', $data['error']);
    }

    /**
     * Testa findByPregao() quando o service retorna array vazio:
     * - Deve retornar HTTP 404 e JSON com erro.
     */
    public function testFindByPregaoNotFoundReturns404(): void
    {
        /** @var LicitacaoService|\PHPUnit\Framework\MockObject\MockObject $serviceMock */
        $serviceMock = $this->getMockBuilder(LicitacaoService::class)
                            ->onlyMethods(['listarPorNumeroPregao'])
                            ->getMock();
        $serviceMock->method('listarPorNumeroPregao')
                    ->with('90000')
                    ->willReturn([]);

        $controller = new class($serviceMock) extends LicitacaoController {
            private $serviceOverride;
            public function __construct($svc)
            {
                $this->serviceOverride = $svc;
            }
            protected function createService(): LicitacaoService
            {
                return $this->serviceOverride;
            }
        };

        $_GET['numero'] = '90000';
        $json = $controller->findByPregao();
        $data = json_decode($json, true);

        $this->assertArrayHasKey('error', $data);
        $this->assertStringContainsString('Nenhuma licitação encontrada para o pregão 90000', $data['error']);
    }

    /**
     * Testa findByPregao() quando o service encontra um ou mais itens:
     * - Espera um array com os itens retornados.
     */
    public function testFindByPregaoReturnsItems(): void
    {
        $_GET = [];

        $fakeItens = [
            ['ordem' => '1', 'modalidade_numero' => 'Pregão Eletrônico Nº 90007/2025'],
            ['ordem' => '2', 'modalidade_numero' => 'Pregão Eletrônico Nº 90007/2025'],
        ];

        /** @var LicitacaoService|\PHPUnit\Framework\MockObject\MockObject $serviceMock */
        $serviceMock = $this->getMockBuilder(\App\Services\LicitacaoService::class)
                            ->onlyMethods(['listarPorNumeroPregao'])
                            ->getMock();

        $serviceMock->expects($this->once()) 
                    ->method('listarPorNumeroPregao')
                    ->with('90007')
                    ->willReturn($fakeItens);

        $controller = new class($serviceMock) extends \App\Controllers\LicitacaoController {
            private $serviceOverride;
            public function __construct($svc)
            {
                $this->serviceOverride = $svc;
            }
            protected function createService(): \App\Services\LicitacaoService
            {
                return $this->serviceOverride;
            }
        };

        $_GET['numero'] = '90007';

        $json = $controller->findByPregao();
        $data = json_decode($json, true);

        $this->assertIsArray($data);
        $this->assertCount(2, $data);
        $this->assertSame($fakeItens, $data);
    }
}
