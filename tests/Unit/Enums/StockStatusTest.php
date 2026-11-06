<?php

namespace Tests\Unit\Enums;

use App\Features\Stock\Enums\StockStatus;
use Tests\TestCase;

class StockStatusTest extends TestCase
{
    public function test_values(): void
    {
        $this->assertSame('available', StockStatus::Available->value);
        $this->assertSame('low_stock', StockStatus::LowStock->value);
        $this->assertSame('out_of_stock', StockStatus::OutOfStock->value);
    }

    public function test_labels(): void
    {
        $this->assertSame('Tersedia', StockStatus::Available->label());
        $this->assertSame('Stok Menipis', StockStatus::LowStock->label());
        $this->assertSame('Stok Habis', StockStatus::OutOfStock->label());
    }

    public function test_badge_classes(): void
    {
        $this->assertStringContainsString('green', StockStatus::Available->badgeClass());
        $this->assertStringContainsString('yellow', StockStatus::LowStock->badgeClass());
        $this->assertStringContainsString('red', StockStatus::OutOfStock->badgeClass());
    }

    public function test_from_quantity(): void
    {
        $this->assertSame(StockStatus::OutOfStock, StockStatus::fromQuantity(0, 5));
        $this->assertSame(StockStatus::OutOfStock, StockStatus::fromQuantity(-3, 5));
        $this->assertSame(StockStatus::LowStock, StockStatus::fromQuantity(1, 5));
        $this->assertSame(StockStatus::LowStock, StockStatus::fromQuantity(5, 5));
        $this->assertSame(StockStatus::Available, StockStatus::fromQuantity(6, 5));
    }
}
