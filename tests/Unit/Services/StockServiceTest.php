<?php

namespace Tests\Unit\Services;

use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Services\StockService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StockServiceTest extends TestCase
{
    private StockService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->service = app(StockService::class);
    }

    public function test_rules_contain_all_fields(): void
    {
        $this->assertSame(
            ['name', 'sku', 'description', 'quantity', 'unit', 'price', 'min_stock'],
            array_keys(StockService::rules()),
        );
    }

    public function test_paginate_and_find(): void
    {
        $user = $this->makeMember();
        $stock = $this->makeStock($user, ['name' => 'Kertas A4']);
        $this->makeStock($this->makeMember());

        $page = $this->service->paginate($user, 'Kertas', null, 5);

        $this->assertSame(1, $page->total());
        $this->assertTrue($this->service->find($stock->id)->is($stock));
    }

    public function test_create_normalizes_data_and_computes_status(): void
    {
        $user = $this->makeMember();

        $stock = $this->service->create($user, [
            'name' => '  Pulpen  ', 'sku' => '', 'description' => '', 'quantity' => '3',
            'unit' => '', 'price' => '', 'min_stock' => '5',
        ]);

        $this->assertSame($user->id, $stock->user_id);
        $this->assertSame('Pulpen', $stock->name);
        $this->assertNull($stock->sku);
        $this->assertNull($stock->description);
        $this->assertSame('pcs', $stock->unit);
        $this->assertNull($stock->price);
        $this->assertSame(StockStatus::LowStock, $stock->status);
    }

    public function test_create_with_full_data_and_default_min_stock(): void
    {
        $stock = $this->service->create($this->makeMember(), [
            'name' => 'Laptop', 'sku' => ' LP-1 ', 'description' => '# Hi', 'quantity' => 20,
            'unit' => ' unit ', 'price' => '9500000',
        ]);

        $this->assertSame('LP-1', $stock->sku);
        $this->assertSame('# Hi', $stock->description);
        $this->assertSame('unit', $stock->unit);
        $this->assertSame('9500000.00', $stock->fresh()->price);
        $this->assertSame(5, $stock->min_stock);
        $this->assertSame(StockStatus::Available, $stock->status);
    }

    public function test_update_recomputes_status(): void
    {
        $stock = $this->makeStock($this->makeMember(), ['quantity' => 50, 'min_stock' => 5, 'status' => StockStatus::Available]);

        $updated = $this->service->update($stock, [
            'name' => 'Ubah', 'sku' => 'X', 'description' => null, 'quantity' => 0,
            'unit' => 'box', 'price' => '100', 'min_stock' => 5,
        ]);

        $this->assertSame('Ubah', $updated->name);
        $this->assertSame(StockStatus::OutOfStock, $updated->status);
    }

    public function test_delete_without_cover(): void
    {
        $stock = $this->makeStock($this->makeMember(), ['cover_image_path' => null]);

        $this->service->delete($stock);

        $this->assertDatabaseMissing('stocks', ['id' => $stock->id]);
    }

    public function test_delete_removes_cover_file(): void
    {
        Storage::disk('public')->put('stock-covers/old.jpg', 'x');
        $stock = $this->makeStock($this->makeMember(), ['cover_image_path' => 'stock-covers/old.jpg']);

        $this->service->delete($stock);

        Storage::disk('public')->assertMissing('stock-covers/old.jpg');
        $this->assertDatabaseMissing('stocks', ['id' => $stock->id]);
    }

    public function test_adjust_quantity_updates_status(): void
    {
        $stock = $this->makeStock($this->makeMember(), ['quantity' => 6, 'min_stock' => 5, 'status' => StockStatus::Available]);

        $stock = $this->service->adjustQuantity($stock, -1);
        $this->assertSame(5, $stock->quantity);
        $this->assertSame(StockStatus::LowStock, $stock->status);

        $stock = $this->service->adjustQuantity($stock, 1);
        $this->assertSame(StockStatus::Available, $stock->status);

        $stock = $this->service->adjustQuantity($stock, -100);
        $this->assertSame(0, $stock->quantity);
        $this->assertSame(StockStatus::OutOfStock, $stock->status);
    }

    public function test_upload_cover_and_replace(): void
    {
        $stock = $this->makeStock($this->makeMember());

        $stock = $this->service->uploadCover($stock, UploadedFile::fake()->image('a.jpg'));
        $first = $stock->cover_image_path;
        $this->assertNotNull($first);
        Storage::disk('public')->assertExists($first);

        $stock = $this->service->uploadCover($stock, UploadedFile::fake()->image('b.jpg'));
        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($stock->cover_image_path);
    }
}
