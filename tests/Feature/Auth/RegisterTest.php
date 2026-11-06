<?php

namespace Tests\Feature\Auth;

use App\Features\Auth\Livewire\Register;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    public function test_register_page_renders(): void
    {
        $this->get('/register')->assertOk()->assertSee('Daftar Akun');
    }

    public function test_register_creates_member_and_logs_in(): void
    {
        Livewire::test(Register::class)
            ->set('name', 'Budi')
            ->set('username', 'budi_01')
            ->set('email', 'budi@example.com')
            ->set('password', 'rahasia1')
            ->set('password_confirmation', 'rahasia1')
            ->call('register')
            ->assertHasNoErrors()
            ->assertRedirect(route('stocks.index'));

        $user = User::query()->where('username', 'budi_01')->firstOrFail();
        $this->assertSame('member', $user->role);
        $this->assertTrue(Hash::check('rahasia1', $user->password));
        $this->assertAuthenticatedAs($user);
    }

    public function test_email_is_optional(): void
    {
        Livewire::test(Register::class)
            ->set('name', 'Tanpa Email')
            ->set('username', 'noemail')
            ->set('password', 'rahasia1')
            ->set('password_confirmation', 'rahasia1')
            ->call('register')
            ->assertHasNoErrors();

        $this->assertNull(User::query()->where('username', 'noemail')->firstOrFail()->email);
    }

    public function test_validation_errors(): void
    {
        $this->makeMember(['username' => 'dipakai', 'email' => 'dipakai@example.com']);

        Livewire::test(Register::class)
            ->set('name', '')
            ->set('username', 'dipakai')
            ->set('email', 'dipakai@example.com')
            ->set('password', 'abc')
            ->set('password_confirmation', 'beda')
            ->call('register')
            ->assertHasErrors(['name', 'username', 'email', 'password']);

        $this->assertGuest();
    }
}
