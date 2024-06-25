<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\ExchangeRateService;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class ExchangeRateServiceTest extends TestCase
{
    public function testGetExchangeRate()
    {
        Http::fake([
            'https://olinda.bcb.gov.br/olinda/servico/PTAX/versao/v1/odata/*' => Http::response([
                'value' => [
                    [
                        'cotacaoCompra' => 5.452,
                        'cotacaoVenda' => 5.4526,
                        'dataHoraCotacao' => '2024-06-21 13:05:36.446',
                        'tipoBoletim' => 'Fechamento'
                    ]
                ]
            ], 200)
        ]);

        $service = new ExchangeRateService();
        $result = $service->getExchangeRate('USD', Carbon::create(2024, 6, 21));

        $this->assertEquals(5.452, $result['buy']);
        $this->assertEquals(5.4526, $result['sell']);
        $this->assertEquals('06-21-2024', $result['date']);
    }

    public function testGetExchangeRateFallback()
    {
        Http::fake([
            'https://olinda.bcb.gov.br/olinda/servico/PTAX/versao/v1/odata/*' => Http::sequence()
                ->push(['value' => []], 200)
                ->push([
                    'value' => [
                        [
                            'cotacaoCompra' => 5.452,
                            'cotacaoVenda' => 5.4526,
                            'dataHoraCotacao' => '2024-06-20 13:05:36.446',
                            'tipoBoletim' => 'Fechamento'
                        ]
                    ]
                ], 200)
        ]);

        $service = new ExchangeRateService();
        $result = $service->getExchangeRate('USD', Carbon::create(2024, 6, 21));

        $this->assertEquals(5.452, $result['buy']);
        $this->assertEquals(5.4526, $result['sell']);
        $this->assertEquals('06-20-2024', $result['date']);
    }

    public function testGetExchangeRateFailure()
    {
        Http::fake([
            'https://olinda.bcb.gov.br/olinda/servico/PTAX/versao/v1/odata/*' => Http::response(null, 500)
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Falha ao buscar taxas de câmbio');

        $service = new ExchangeRateService();
        $service->getExchangeRate('USD', Carbon::create(2024, 6, 21));
    }

    public function testGetExchangeRateNoData()
    {
        Http::fake([
            'https://olinda.bcb.gov.br/olinda/servico/PTAX/versao/v1/odata/*' => Http::response(['value' => []], 200)
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Nenhuma cotação encontrada após múltiplas tentativas.');

        $service = new ExchangeRateService();
        $service->getExchangeRate('USD', Carbon::create(2024, 6, 21));
    }
}
