<?php

namespace Tests\Unit\Repositories;

use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use App\Features\Stock\Repositories\StockRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Tests\TestCase;

class StockRepositoryTest extends TestCase
{
    private StockRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new StockRepository;
    }

    public function test_paginate_only_returns_user_stocks(): void
    {
        $user = $this->makeMember();
        $this->makeStock($user);
        $this->makeStock($user);
        $this->makeStock($this->makeMember());

        $this->assertSame(2, $this->repo->paginateByUser($user->id)->total());
    }

    public function test_paginate_search_and_status_filter(): void
    {
        $user = $this->makeMember();
        $this->makeStock($user, ['name' => 'Pulpen Biru', 'sku' => 'AAA-1', 'status' => StockStatus::Available]);
        $this->makeStock($user, ['name' => 'Buku', 'sku' => 'BBB-2', 'status' => StockStatus::LowStock]);

        $this->assertSame(1, $this->repo->paginateByUser($user->id, 'Pulpen')->total());
        $this->assertSame(1, $this->repo->paginateByUser($user->id, 'BBB')->total());
        $this->assertSame(1, $this->repo->paginateByUser($user->id, null, 'low_stock')->total());
        $this->assertSame(0, $this->repo->paginateByUser($user->id, 'Pulpen', 'low_stock')->total());
    }

    public function test_paginate_uses_per_page(): void
    {
        $user = $this->makeMember();
        Stock::factory()->count(3)->for($user)->create();

        $page = $this->repo->paginateByUser($user->id, null, null, 2);

        $this->assertCount(2, $page->items());
        $this->assertSame(3, $page->total());
    }

    public function test_all_by_user_sorted_by_name(): void
    {
        $user = $this->makeMember();
        $this->makeStock($user, ['name' => 'Zebra']);
        $this->makeStock($user, ['name' => 'Apel']);
        $this->makeStock($this->makeMember(), ['name' => 'Lain']);

        $this->assertSame(['Apel', 'Zebra'], $this->repo->allByUser($user->id)->pluck('name')->all());
    }

    public function test_find_or_fail(): void
    {
        $stock = $this->makeStock($this->makeMember());

        $this->assertTrue($this->repo->findOrFail($stock->id)->is($stock));

        $this->expectException(ModelNotFoundException::class);
        $this->repo->findOrFail(999999);
    }

    public function test_create_update_delete(): void
    {
        $user = $this->makeMember();

        $stock = $this->repo->create([
            'user_id' => $user->id, 'name' => 'Baru', 'quantity' => 1, 'unit' => 'pcs',
            'min_stock' => 5, 'status' => StockStatus::LowStock,
        ]);
        $this->assertDatabaseHas('stocks', ['name' => 'Baru']);

        $updated = $this->repo->update($stock, ['name' => 'Berubah']);
        $this->assertSame('Berubah', $updated->name);
        $this->assertDatabaseHas('stocks', ['name' => 'Berubah']);

        $this->repo->delete($stock);
        $this->assertDatabaseMissing('stocks', ['id' => $stock->id]);
    }
}
