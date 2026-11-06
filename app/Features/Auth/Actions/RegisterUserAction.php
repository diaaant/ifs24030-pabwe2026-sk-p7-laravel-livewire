<?php

namespace App\Features\Auth\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class RegisterUserAction
{
    /**
     * Daftarkan user baru (selalu role member) lalu langsung login.
     *
     * @param  array{name: string, username: string, email?: string|null, password: string}  $data
     */
    public function execute(array $data): User
    {
        $user = User::query()->create([
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => filled($data['email'] ?? null) ? $data['email'] : null,
            'password' => $data['password'],
            'role' => User::ROLE_MEMBER,
        ]);

        Auth::login($user);
        session()->regenerate();

        return $user;
    }
}
