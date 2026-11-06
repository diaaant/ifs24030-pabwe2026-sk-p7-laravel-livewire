<?php

namespace Tests\Unit\Models;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    public function test_roles(): void
    {
        $admin = $this->makeAdmin();
        $member = $this->makeMember();

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isMember());
        $this->assertTrue($member->isMember());
        $this->assertFalse($member->isAdmin());
    }

    public function test_home_route(): void
    {
        $this->assertSame('admin.users', $this->makeAdmin()->homeRoute());
        $this->assertSame('stocks.index', $this->makeMember()->homeRoute());
    }

    public function test_stocks_relation(): void
    {
        $user = $this->makeMember();
        $this->makeStock($user);
        $this->makeStock($user);

        $this->assertCount(2, $user->stocks);
    }

    public function test_profile_photo_url(): void
    {
        Storage::fake('public');
        $user = $this->makeMember();
        $this->assertNull($user->profilePhotoUrl());

        $user->profile_photo_path = 'profile-photos/me.jpg';
        $this->assertStringContainsString('profile-photos/me.jpg', $user->profilePhotoUrl());
    }

    public function test_password_is_hashed_and_hidden(): void
    {
        $user = $this->makeMember(['password' => 'secret123']);

        $this->assertTrue(Hash::check('secret123', $user->password));
        $this->assertArrayNotHasKey('password', $user->toArray());
    }
}
