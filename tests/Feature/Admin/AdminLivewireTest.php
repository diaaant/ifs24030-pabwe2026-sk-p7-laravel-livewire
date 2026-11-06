<?php

namespace Tests\Feature\Admin;

use App\Features\Admin\Livewire\AdminUserStockList;
use App\Features\Admin\Livewire\UserList;
use Livewire\Livewire;
use Tests\TestCase;

class AdminLivewireTest extends TestCase
{
    public function test_user_list_shows_users_with_stock_counts(): void
    {
        $admin = $this->makeAdmin();
        $member = $this->makeMember(['name' => 'Siti Member', 'username' => 'siti']);
        $this->makeStock($member);
        $this->makeStock($member);

        Livewire::actingAs($admin)
            ->test(UserList::class)
            ->assertSee('Siti Member')
            ->assertSee('siti')
            ->assertViewHas('users', fn ($users) => $users->firstWhere('username', 'siti')->stocks_count === 2);
    }

    public function test_user_list_search(): void
    {
        $admin = $this->makeAdmin(['name' => 'Root']);
        $this->makeMember(['name' => 'Cari Aku', 'username' => 'cariaku']);
        $this->makeMember(['name' => 'Orang Lain', 'username' => 'lain']);

        Livewire::actingAs($admin)
            ->test(UserList::class)
            ->set('search', 'cariaku')
            ->assertSee('Cari Aku')
            ->assertDontSee('Orang Lain');
    }

    public function test_user_stock_list_is_read_only_and_scoped(): void
    {
        $admin = $this->makeAdmin();
        $member = $this->makeMember(['name' => 'Pemilik']);
        $this->makeStock($member, ['name' => 'Barang Milik Pemilik']);
        $this->makeStock($this->makeMember(), ['name' => 'Barang Orang Lain']);

        Livewire::actingAs($admin)
            ->test(AdminUserStockList::class, ['user' => $member])
            ->assertSee('Barang Milik Pemilik')
            ->assertDontSee('Barang Orang Lain')
            ->assertDontSee('Hapus')
            ->assertDontSee('Edit')
            ->assertDontSee('Tambah');
    }

    public function test_user_stock_list_search_and_status_filter(): void
    {
        $admin = $this->makeAdmin();
        $member = $this->makeMember();
        $this->makeStock($member, ['name' => 'Kabel USB', 'status' => 'available']);
        $this->makeStock($member, ['name' => 'Mouse Gaming', 'status' => 'low_stock']);

        Livewire::actingAs($admin)
            ->test(AdminUserStockList::class, ['user' => $member])
            ->set('search', 'Kabel')
            ->assertSee('Kabel USB')
            ->assertDontSee('Mouse Gaming')
            ->set('search', '')
            ->set('status', 'low_stock')
            ->assertSee('Mouse Gaming')
            ->assertDontSee('Kabel USB');
    }
}
