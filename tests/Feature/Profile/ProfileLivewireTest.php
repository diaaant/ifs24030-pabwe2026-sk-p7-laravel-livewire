<?php

namespace Tests\Feature\Profile;

use App\Features\Profile\Livewire\Profile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileLivewireTest extends TestCase
{
    public function test_mount_fills_current_data(): void
    {
        $user = $this->makeMember(['name' => 'Rina', 'username' => 'rina', 'email' => 'rina@example.com']);

        Livewire::actingAs($user)->test(Profile::class)
            ->assertSet('name', 'Rina')
            ->assertSet('username', 'rina')
            ->assertSet('email', 'rina@example.com');
    }

    public function test_update_profile(): void
    {
        $user = $this->makeMember(['username' => 'lama']);

        Livewire::actingAs($user)->test(Profile::class)
            ->set('name', 'Nama Baru')
            ->set('username', 'baru')
            ->set('email', '')
            ->call('updateProfile')
            ->assertHasNoErrors();

        $this->assertSame('Nama Baru', $user->fresh()->name);
        $this->assertSame('baru', $user->fresh()->username);
        $this->assertNull($user->fresh()->email);
    }

    public function test_update_profile_validation_and_unique_username(): void
    {
        $this->makeMember(['username' => 'dipakai']);
        $user = $this->makeMember(['username' => 'saya']);

        Livewire::actingAs($user)->test(Profile::class)
            ->set('name', '')
            ->set('username', 'dipakai')
            ->call('updateProfile')
            ->assertHasErrors(['name', 'username']);
    }

    public function test_keeping_own_username_is_allowed(): void
    {
        $user = $this->makeMember(['username' => 'saya']);

        Livewire::actingAs($user)->test(Profile::class)
            ->call('updateProfile')
            ->assertHasNoErrors();
    }

    public function test_update_password_success(): void
    {
        $user = $this->makeMember(['password' => 'lama123']);

        Livewire::actingAs($user)->test(Profile::class)
            ->set('current_password', 'lama123')
            ->set('password', 'baru1234')
            ->set('password_confirmation', 'baru1234')
            ->call('updatePassword')
            ->assertHasNoErrors()
            ->assertSet('current_password', '')
            ->assertSet('password', '');

        $this->assertTrue(Hash::check('baru1234', $user->fresh()->password));
    }

    public function test_update_password_wrong_current_password(): void
    {
        $user = $this->makeMember(['password' => 'lama123']);

        Livewire::actingAs($user)->test(Profile::class)
            ->set('current_password', 'salah')
            ->set('password', 'baru1234')
            ->set('password_confirmation', 'baru1234')
            ->call('updatePassword')
            ->assertHasErrors(['current_password']);

        $this->assertTrue(Hash::check('lama123', $user->fresh()->password));
    }

    public function test_update_password_validation(): void
    {
        Livewire::actingAs($this->makeMember())->test(Profile::class)
            ->set('current_password', 'x')
            ->set('password', 'baru1234')
            ->set('password_confirmation', 'beda')
            ->call('updatePassword')
            ->assertHasErrors(['password' => 'confirmed']);
    }

    public function test_update_photo(): void
    {
        Storage::fake('public');
        $user = $this->makeMember();

        Livewire::actingAs($user)->test(Profile::class)
            ->set('photo', UploadedFile::fake()->image('me.jpg'))
            ->call('updatePhoto')
            ->assertHasNoErrors()
            ->assertSet('photo', null);

        Storage::disk('public')->assertExists($user->fresh()->profile_photo_path);
    }

    public function test_update_photo_rejects_non_image(): void
    {
        Storage::fake('public');

        Livewire::actingAs($this->makeMember())->test(Profile::class)
            ->set('photo', UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'))
            ->call('updatePhoto')
            ->assertHasErrors(['photo']);
    }
}
