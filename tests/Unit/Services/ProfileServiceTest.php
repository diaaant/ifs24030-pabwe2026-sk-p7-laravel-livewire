<?php

namespace Tests\Unit\Services;

use App\Features\Profile\Services\ProfileService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileServiceTest extends TestCase
{
    private ProfileService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->service = new ProfileService;
    }

    public function test_update_profile(): void
    {
        $user = $this->makeMember();

        $this->service->updateProfile($user, ['name' => 'Nama Baru', 'username' => 'baru', 'email' => 'baru@example.com']);
        $this->assertSame('Nama Baru', $user->fresh()->name);
        $this->assertSame('baru', $user->fresh()->username);
        $this->assertSame('baru@example.com', $user->fresh()->email);

        $this->service->updateProfile($user, ['name' => 'Nama Baru', 'username' => 'baru', 'email' => '']);
        $this->assertNull($user->fresh()->email);
    }

    public function test_update_password(): void
    {
        $user = $this->makeMember(['password' => 'lama123']);

        $this->assertFalse($this->service->updatePassword($user, 'salah', 'baru1234'));
        $this->assertTrue(Hash::check('lama123', $user->fresh()->password));

        $this->assertTrue($this->service->updatePassword($user, 'lama123', 'baru1234'));
        $this->assertTrue(Hash::check('baru1234', $user->fresh()->password));
    }

    public function test_update_photo_and_replace(): void
    {
        $user = $this->makeMember();

        $this->service->updatePhoto($user, UploadedFile::fake()->image('a.jpg'));
        $first = $user->fresh()->profile_photo_path;
        Storage::disk('public')->assertExists($first);

        $this->service->updatePhoto($user, UploadedFile::fake()->image('b.jpg'));
        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($user->fresh()->profile_photo_path);
    }
}
