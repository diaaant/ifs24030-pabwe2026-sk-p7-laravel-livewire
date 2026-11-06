<?php

namespace Tests\Feature\Auth;

use App\Features\Auth\Livewire\Login;
use Livewire\Livewire;
use Tests\TestCase;

class LoginTest extends TestCase
{
    public function test_login_page_has_required_element_ids(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('id="login-username"', false)
            ->assertSee('id="login-password"', false)
            ->assertSee('id="login-submit"', false);
    }

    public function test_member_logs_in_with_username_and_goes_to_stocks(): void
    {
        $this->makeMember(['username' => 'member', 'password' => 'member']);

        Livewire::test(Login::class)
            ->set('login', 'member')
            ->set('password', 'member')
            ->call('authenticate')
            ->assertHasNoErrors()
            ->assertRedirect(route('stocks.index'));

        $this->assertAuthenticated();
    }

    public function test_admin_logs_in_and_goes_to_admin_users(): void
    {
        $this->makeAdmin(['username' => 'admin', 'password' => 'admin']);

        Livewire::test(Login::class)
            ->set('login', 'admin')
            ->set('password', 'admin')
            ->set('remember', true)
            ->call('authenticate')
            ->assertRedirect(route('admin.users'));
    }

    public function test_login_with_email(): void
    {
        $this->makeMember(['email' => 'andi@example.com', 'password' => 'rahasia1']);

        Livewire::test(Login::class)
            ->set('login', 'andi@example.com')
            ->set('password', 'rahasia1')
            ->call('authenticate')
            ->assertRedirect(route('stocks.index'));
    }

    public function test_wrong_password_shows_error(): void
    {
        $this->makeMember(['username' => 'member', 'password' => 'member']);

        Livewire::test(Login::class)
            ->set('login', 'member')
            ->set('password', 'salah')
            ->call('authenticate')
            ->assertHasErrors(['login'])
            ->assertNoRedirect();

        $this->assertGuest();
    }

    public function test_fields_are_required(): void
    {
        Livewire::test(Login::class)
            ->call('authenticate')
            ->assertHasErrors(['login' => 'required', 'password' => 'required']);
    }

    public function test_logout(): void
    {
        $user = $this->makeMember();

        $this->actingAs($user)->post('/logout')->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
