<?php

namespace Tests\Unit;

use App\Core\Database;
use App\Services\QuoteService;
use PHPUnit\Framework\TestCase;

class QuoteServiceTest extends TestCase
{
    private QuoteService $quotes;

    protected function setUp(): void
    {
        // Database com config vazia: nao conecta enquanto pdo() nao for chamado.
        // calculate() e logica pura e nao acessa o banco.
        $this->quotes = new QuoteService(new Database([]));
    }

    public function testCalculatesSubtotalAndTotal(): void
    {
        $items = [
            ['description' => 'Item A', 'quantity' => 2, 'unit_price' => 100],
            ['description' => 'Item B', 'quantity' => 1, 'unit_price' => 50],
        ];
        $r = $this->quotes->calculate($items, 'value', 0, 0);
        $this->assertSame(250.0, $r['subtotal']);
        $this->assertSame(250.0, $r['total']);
    }

    public function testAppliesFixedDiscount(): void
    {
        $items = [['description' => 'X', 'quantity' => 1, 'unit_price' => 500]];
        $r = $this->quotes->calculate($items, 'value', 50, 0);
        $this->assertSame(50.0, $r['discount']);
        $this->assertSame(450.0, $r['total']);
    }

    public function testAppliesPercentDiscount(): void
    {
        $items = [['description' => 'X', 'quantity' => 1, 'unit_price' => 500]];
        $r = $this->quotes->calculate($items, 'percent', 10, 0);
        $this->assertSame(50.0, $r['discount']);
        $this->assertSame(450.0, $r['total']);
    }

    public function testDiscountNeverExceedsSubtotal(): void
    {
        $items = [['description' => 'X', 'quantity' => 1, 'unit_price' => 100]];
        $r = $this->quotes->calculate($items, 'value', 500, 0);
        $this->assertSame(100.0, $r['discount']);
        $this->assertSame(0.0, $r['total']);
    }

    public function testAppliesSurcharge(): void
    {
        $items = [['description' => 'X', 'quantity' => 1, 'unit_price' => 100]];
        $r = $this->quotes->calculate($items, 'value', 0, 30);
        $this->assertSame(130.0, $r['total']);
    }

    public function testNegativeSurchargeIsClamped(): void
    {
        $items = [['description' => 'X', 'quantity' => 1, 'unit_price' => 100]];
        $r = $this->quotes->calculate($items, 'value', 0, -50);
        $this->assertSame(0.0, $r['surcharge']);
        $this->assertSame(100.0, $r['total']);
    }
}
