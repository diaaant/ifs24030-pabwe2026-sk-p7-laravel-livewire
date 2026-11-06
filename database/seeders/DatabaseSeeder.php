<?php

namespace Database\Seeders;

use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(['username' => 'admin'], [
            'name' => 'Administrator',
            'email' => 'admin@example.com',
            'password' => 'admin',
            'role' => User::ROLE_ADMIN,
        ]);

        $member = User::query()->updateOrCreate(['username' => 'member'], [
            'name' => 'Member Demo',
            'email' => 'member@example.com',
            'password' => 'member',
            'role' => User::ROLE_MEMBER,
        ]);

        if ($member->stocks()->doesntExist()) {
            Stock::factory()->count(12)->for($member)->create();
        }
    }
}
