<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExchangeRateService
{
    private $baseUrl = 'https://olinda.bcb.gov.br/olinda/servico/PTAX/versao/v1/odata/';

    public function getExchangeRate($currency, $date = null)
    {
        if (!$date) {
            $date = Carbon::now();
        } else {
            $date = Carbon::parse($date);
        }

        // Encontra o último dia útil se for fim de semana ou feriado
        $date = $this->getLastBusinessDay($date);

        $formattedDate = $date->format('m-d-Y');
        $url = "{$this->baseUrl}CotacaoMoedaPeriodo(dataInicial=@dataInicial,dataFinalCotacao=@dataFinalCotacao,moeda=@moeda)?@dataInicial='{$formattedDate}'&@dataFinalCotacao='{$formattedDate}'&@moeda='{$currency}'&\$format=json";

        // Log da URL gerada
        Log::info('URL gerada para API do Banco Central: ' . $url);

        try {
            $response = Http::get($url);

            // Log da resposta da API
            Log::info('Resposta da API do Banco Central: ' . $response->body());

            if ($response->failed()) {
                return [
                    'error' => true,
                    'message' => $response->body()
                ];
            }

            $data = $response->json()['value'];

            if (empty($data)) {
                return [
                    'error' => true,
                    'message' => 'Nenhuma cotação encontrada para a data fornecida.'
                ];
            }

            $buyRate = $data[0]['cotacaoCompra'] ?? 0;
            $sellRate = $data[0]['cotacaoVenda'] ?? 0;

            // Log dos valores de compra e venda
            Log::info('Taxa de compra: ' . $buyRate);
            Log::info('Taxa de venda: ' . $sellRate);

            return [
                'buy' => $buyRate,
                'sell' => $sellRate
            ];
        } catch (\Exception $e) {
            Log::error('Erro ao fazer requisição para API do Banco Central: ' . $e->getMessage());
            return [
                'error' => true,
                'message' => 'Erro desconhecido'
            ];
        }
    }

    private function getLastBusinessDay(Carbon $date)
    {
        while ($this->isWeekend($date) || $this->isHoliday($date)) {
            $date->subDay();
        }
        return $date;
    }

    private function isWeekend(Carbon $date)
    {
        return $date->isWeekend();
    }

    private function isHoliday(Carbon $date)
    {
        $holidays = [
            '01-01', '04-21', '05-01', '09-07', '10-12', '11-02', '11-15', '12-25'
        ];

        return in_array($date->format('d-m'), $holidays);
    }
}
