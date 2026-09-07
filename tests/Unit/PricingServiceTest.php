<?php

namespace Tests\Unit;

use App\Services\PricingService;
use PHPUnit\Framework\TestCase;

class PricingServiceTest extends TestCase
{
    private PricingService $pricing;

    protected function setUp(): void
    {
        $this->pricing = new PricingService();
    }

    public function testRecommendedPriceMatchesSpecExample(): void
    {
        // Spec: custo R$ 350, margem 30% => preco recomendado R$ 500.
        $r = $this->pricing->calculate([
            'material_cost' => 200,
            'labor_cost' => 150,
            'hours' => 5,
            'margin_percent' => 30,
        ]);

        $this->assertSame(350.0, $r['total_cost']);
        $this->assertSame(70.0, $r['cost_per_hour']);
        $this->assertSame(500.0, $r['recommended_price']);
    }

    public function testMinPriceCoversOnlyCostsAndTaxes(): void
    {
        $r = $this->pricing->calculate([
            'material_cost' => 100,
            'taxes_percent' => 0,
            'fees_percent' => 0,
            'margin_percent' => 50,
        ]);
        $this->assertSame(100.0, $r['min_price']);
    }

    public function testTaxesIncreasePrice(): void
    {
        $r = $this->pricing->calculate([
            'labor_cost' => 100,
            'taxes_percent' => 10,
            'margin_percent' => 0,
        ]);
        // 100 / (1 - 0.10) = 111.11
        $this->assertEqualsWithDelta(111.11, $r['recommended_price'], 0.01);
    }

    public function testAggressiveIsHigherThanConservative(): void
    {
        $r = $this->pricing->calculate([
            'labor_cost' => 100,
            'margin_percent' => 30,
        ]);
        $this->assertGreaterThan($r['conservative_price'], $r['aggressive_price']);
    }

    public function testHandlesZeroHours(): void
    {
        $r = $this->pricing->calculate(['material_cost' => 50, 'hours' => 0]);
        $this->assertSame(0.0, $r['cost_per_hour']);
    }
}
