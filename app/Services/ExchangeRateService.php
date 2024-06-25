<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

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

        $date = $this->getLastBusinessDay($date);
        $attempts = 0; // Adiciona um contador de tentativas

        while ($attempts < 10) { // Limita a 10 tentativas para evitar loop infinito
            $attempts++; // Incrementa o contador de tentativas
            $formattedDate = $date->format('m-d-Y');
            $url = "{$this->baseUrl}CotacaoMoedaPeriodo(dataInicial=@dataInicial,dataFinalCotacao=@dataFinalCotacao,moeda=@moeda)?@dataInicial='{$formattedDate}'&@dataFinalCotacao='{$formattedDate}'&@moeda='{$currency}'&\$top=100&\$orderby=dataHoraCotacao desc&\$format=json";

            try {
                $response = Http::get($url);

                if ($response->failed()) {
                    throw new \Exception('Falha ao buscar taxas de câmbio');
                }

                $data = $response->json();
                if (!isset($data['value']) || empty($data['value'])) {
                    // Se não encontrou uma cotação válida, retrocede um dia e continua a busca
                    $date = $this->getLastBusinessDay($date->subDay());
                    continue;
                }

                // Filtra o boletim de fechamento PTAX
                $filteredData = array_filter($data['value'], function ($item) {
                    return $item['tipoBoletim'] === 'Fechamento';
                });

                if (!empty($filteredData)) {
                    $filteredData = array_values($filteredData); // Reindexa o array
                    $buyRate = $filteredData[0]['cotacaoCompra'] ?? 0;
                    $sellRate = $filteredData[0]['cotacaoVenda'] ?? 0;

                    return [
                        'buy' => $buyRate,
                        'sell' => $sellRate,
                        'date' => $formattedDate,
                    ];
                } else {
                    // Se não encontrou uma cotação de fechamento, retrocede um dia e continua a busca
                    $date = $this->getLastBusinessDay($date->subDay());
                }
            } catch (\Exception $e) {
                throw new \Exception("Erro ao buscar taxa de câmbio: " . $e->getMessage());
            }
        }

        throw new \Exception("Nenhuma cotação encontrada após múltiplas tentativas.");
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
