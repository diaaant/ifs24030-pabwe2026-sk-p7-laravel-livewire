<?php

namespace Tests\Unit\Services;

use App\Features\Admin\Services\AdminUserService;
use Tests\TestCase;

class AdminUserServiceTest extends TestCase
{
    public function test_paginate_users_with_stock_count_and_search(): void
    {
        $alice = $this->makeMember(['name' => 'Alice', 'username' => 'alice']);
        $bob = $this->makeMember(['name' => 'Bob', 'username' => 'bobby']);
        $this->makeStock($alice);
        $this->makeStock($alice);

        $service = app(AdminUserService::class);

        $all = $service->paginateUsers();
        $this->assertSame(2, $all->total());
        $this->assertSame(2, $all->items()[0]->stocks_count);
        $this->assertSame(0, $all->items()[1]->stocks_count);

        $this->assertSame(['Bob'], $service->paginateUsers('bobby', 5)->getCollection()->pluck('name')->all());
        $this->assertSame(['Alice'], $service->paginateUsers('Alic')->getCollection()->pluck('name')->all());
        $this->assertTrue($bob->exists);
    }

    public function test_paginate_user_stocks(): void
    {
        $user = $this->makeMember();
        $this->makeStock($user, ['name' => 'Kabel', 'status' => 'available']);
        $this->makeStock($user, ['name' => 'Mouse', 'status' => 'low_stock']);
        $this->makeStock($this->makeMember());

        $service = app(AdminUserService::class);

        $this->assertSame(2, $service->paginateUserStocks($user)->total());
        $this->assertSame(1, $service->paginateUserStocks($user, 'Kabel')->total());
        $this->assertSame(1, $service->paginateUserStocks($user, null, 'low_stock')->total());
    }
}
