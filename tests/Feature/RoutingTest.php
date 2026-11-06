<?php

namespace Tests\Feature;

use Tests\TestCase;

class RoutingTest extends TestCase
{
    public function test_root_redirects_by_role(): void
    {
        $this->get('/')->assertRedirect(route('login'));
        $this->actingAs($this->makeMember())->get('/')->assertRedirect(route('stocks.index'));
        $this->actingAs($this->makeAdmin())->get('/')->assertRedirect(route('admin.users'));
    }

    public function test_guest_is_redirected_to_login_from_protected_routes(): void
    {
        foreach (['/stocks', '/profile', '/admin/users'] as $uri) {
            $this->get($uri)->assertRedirect('/login');
        }
    }

    public function test_authenticated_users_are_redirected_away_from_guest_pages(): void
    {
        $this->actingAs($this->makeMember())->get('/login')->assertRedirect(route('stocks.index'));
        $this->actingAs($this->makeAdmin())->get('/register')->assertRedirect(route('admin.users'));
    }

    public function test_member_cannot_access_admin_routes(): void
    {
        $member = $this->makeMember();

        $this->actingAs($member)->get('/admin/users')->assertForbidden();
        $this->actingAs($member)->get('/admin/users/'.$member->id.'/stocks')->assertForbidden();
    }

    public function test_admin_cannot_access_member_stock_routes(): void
    {
        $stock = $this->makeStock($this->makeMember());
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->get('/stocks')->assertForbidden();
        $this->actingAs($admin)->get('/stocks/'.$stock->id)->assertForbidden();
    }

    public function test_member_can_only_open_own_stock_detail(): void
    {
        $owner = $this->makeMember();
        $stock = $this->makeStock($owner, ['name' => 'Barang Saya']);

        $this->actingAs($owner)->get('/stocks/'.$stock->id)->assertOk()->assertSee('Barang Saya');
        $this->actingAs($this->makeMember())->get('/stocks/'.$stock->id)->assertForbidden();
        $this->actingAs($owner)->get('/stocks/999999')->assertNotFound();
    }

    public function test_pages_render_for_each_role(): void
    {
        $member = $this->makeMember();
        $admin = $this->makeAdmin();

        $this->actingAs($member)->get('/stocks')->assertOk()->assertSee('Inventaris Stok Barang');
        $this->actingAs($member)->get('/profile')->assertOk();
        $this->actingAs($admin)->get('/admin/users')->assertOk();
        $this->actingAs($admin)->get('/admin/users/'.$member->id.'/stocks')->assertOk();
        $this->actingAs($admin)->get('/profile')->assertOk();
    }
}
