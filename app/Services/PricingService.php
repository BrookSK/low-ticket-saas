<?php

namespace App\Services;

/**
 * Calculadora de precificacao de servicos.
 *
 * Recebe custos e parametros e calcula custo total, custo/hora, margem,
 * lucro estimado e faixas de preco (minimo, recomendado, agressivo, conservador).
 * Logica pura (sem I/O) para facilitar testes.
 */
class PricingService
{
    /**
     * @param array{
     *   material_cost?: float, labor_cost?: float, hours?: float, travel_cost?: float,
     *   fixed_cost?: float, taxes_percent?: float, fees_percent?: float,
     *   margin_percent?: float, other_costs?: float
     * } $input
     */
    public function calculate(array $input): array
    {
        $material = max(0, (float) ($input['material_cost'] ?? 0));
        $labor = max(0, (float) ($input['labor_cost'] ?? 0));
        $hours = max(0, (float) ($input['hours'] ?? 0));
        $travel = max(0, (float) ($input['travel_cost'] ?? 0));
        $fixed = max(0, (float) ($input['fixed_cost'] ?? 0));
        $taxes = max(0, (float) ($input['taxes_percent'] ?? 0));
        $fees = max(0, (float) ($input['fees_percent'] ?? 0));
        $margin = max(0, (float) ($input['margin_percent'] ?? 0));
        $other = max(0, (float) ($input['other_costs'] ?? 0));

        // Custo direto total do servico.
        $totalCost = round($material + $labor + $travel + $fixed + $other, 2);

        // Custo por hora (se informou horas).
        $costPerHour = $hours > 0 ? round($totalCost / $hours, 2) : 0.0;

        // Preco precisa cobrir impostos+taxas+margem aplicados sobre o preco final.
        // preco = custo / (1 - (impostos + taxas + margem)/100)
        $recommendedPrice = $this->priceForMargin($totalCost, $taxes + $fees + $margin);

        // Faixas de margem alternativas.
        $conservativeMargin = max(0, $margin - 10);
        $aggressiveMargin = $margin + 15;

        $minPrice = $this->priceForMargin($totalCost, $taxes + $fees); // apenas cobre custos e taxas
        $conservativePrice = $this->priceForMargin($totalCost, $taxes + $fees + $conservativeMargin);
        $aggressivePrice = $this->priceForMargin($totalCost, $taxes + $fees + $aggressiveMargin);

        // Lucro estimado no preco recomendado (preco - custo - impostos/taxas em R$).
        $taxAndFeesValue = round($recommendedPrice * (($taxes + $fees) / 100), 2);
        $estimatedProfit = round($recommendedPrice - $totalCost - $taxAndFeesValue, 2);

        return [
            'total_cost' => $totalCost,
            'cost_per_hour' => $costPerHour,
            'margin_percent' => $margin,
            'estimated_profit' => $estimatedProfit,
            'min_price' => $minPrice,
            'recommended_price' => $recommendedPrice,
            'conservative_price' => $conservativePrice,
            'aggressive_price' => $aggressivePrice,
            'breakdown' => [
                'material' => $material, 'labor' => $labor, 'travel' => $travel,
                'fixed' => $fixed, 'other' => $other,
                'taxes_percent' => $taxes, 'fees_percent' => $fees,
            ],
        ];
    }

    /**
     * Preco necessario para atingir determinado percentual total sobre o preco
     * (impostos + taxas + margem). Usa markup divisor para nao "comer" a margem.
     */
    protected function priceForMargin(float $cost, float $percentOfPrice): float
    {
        $percentOfPrice = min($percentOfPrice, 95); // trava de seguranca
        $divisor = 1 - ($percentOfPrice / 100);
        if ($divisor <= 0) {
            return round($cost * 20, 2);
        }
        return round($cost / $divisor, 2);
    }
}
