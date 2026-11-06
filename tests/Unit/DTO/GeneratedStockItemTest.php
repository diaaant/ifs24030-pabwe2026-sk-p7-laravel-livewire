<?php

namespace Tests\Unit\DTO;

use App\Features\Ai\DTO\GeneratedStockItem;
use Tests\TestCase;

class GeneratedStockItemTest extends TestCase
{
    public function test_constructor_defaults(): void
    {
        $item = new GeneratedStockItem('Pulpen');

        $this->assertSame('Pulpen', $item->name);
        $this->assertNull($item->sku);
        $this->assertNull($item->description);
        $this->assertSame(0, $item->quantity);
        $this->assertSame('pcs', $item->unit);
        $this->assertNull($item->price);
        $this->assertSame(5, $item->minStock);
    }

    public function test_from_array_full_data(): void
    {
        $item = GeneratedStockItem::fromArray([
            'name' => '  Buku Tulis ',
            'sku' => 'BK-01',
            'description' => '**tebal**',
            'quantity' => '12',
            'unit' => 'pack',
            'price' => '15000.5',
            'min_stock' => 3,
        ]);

        $this->assertSame('Buku Tulis', $item->name);
        $this->assertSame('BK-01', $item->sku);
        $this->assertSame('**tebal**', $item->description);
        $this->assertSame(12, $item->quantity);
        $this->assertSame('pack', $item->unit);
        $this->assertSame(15000.5, $item->price);
        $this->assertSame(3, $item->minStock);
    }

    public function test_from_array_applies_defaults_and_sanitizes(): void
    {
        $item = GeneratedStockItem::fromArray([
            'name' => 'Spidol',
            'sku' => '',
            'description' => '',
            'quantity' => -4,
            'unit' => '  ',
            'price' => 'gratis',
            'min_stock' => -1,
        ]);

        $this->assertNull($item->sku);
        $this->assertNull($item->description);
        $this->assertSame(0, $item->quantity);
        $this->assertSame('pcs', $item->unit);
        $this->assertNull($item->price);
        $this->assertSame(0, $item->minStock);

        $empty = GeneratedStockItem::fromArray([]);
        $this->assertSame('', $empty->name);
        $this->assertSame(5, $empty->minStock);
    }

    public function test_to_array(): void
    {
        $item = new GeneratedStockItem('Pensil', 'PN-1', 'ok', 7, 'box', 1000.0, 2);

        $this->assertSame([
            'name' => 'Pensil',
            'sku' => 'PN-1',
            'description' => 'ok',
            'quantity' => 7,
            'unit' => 'box',
            'price' => 1000.0,
            'min_stock' => 2,
        ], $item->toArray());
    }
}
