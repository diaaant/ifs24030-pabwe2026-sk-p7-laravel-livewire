<?php

namespace Tests\Unit\Models;

use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StockModelTest extends TestCase
{
    public function test_factory_and_casts(): void
    {
        $stock = Stock::factory()->for($this->makeMember())->create([
            'quantity' => 3, 'min_stock' => 5, 'price' => 100, 'status' => StockStatus::LowStock,
        ])->fresh();

        $this->assertInstanceOf(Stock::class, $stock);
        $this->assertSame(3, $stock->quantity);
        $this->assertSame(5, $stock->min_stock);
        $this->assertSame('100.00', $stock->price);
        $this->assertSame(StockStatus::LowStock, $stock->status);
    }

    public function test_belongs_to_user(): void
    {
        $user = $this->makeMember();
        $stock = $this->makeStock($user);

        $this->assertTrue($stock->user->is($user));
    }

    public function test_cover_image_url(): void
    {
        Storage::fake('public');
        $stock = $this->makeStock($this->makeMember(), ['cover_image_path' => null]);
        $this->assertNull($stock->coverImageUrl());

        $stock->cover_image_path = 'stock-covers/a.jpg';
        $this->assertStringContainsString('stock-covers/a.jpg', $stock->coverImageUrl());
    }

    public function test_deleting_user_cascades_to_stocks(): void
    {
        $user = $this->makeMember();
        $this->makeStock($user);

        $user->delete();

        $this->assertSame(0, Stock::query()->count());
        $this->assertSame(0, User::query()->whereKey($user->id)->count());
    }
}
