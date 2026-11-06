<?php

namespace App\Features\Stock\Policies;

use App\Features\Stock\Models\Stock;
use App\Models\User;

class StockPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isMember();
    }

    /** Admin boleh melihat (read-only), member hanya miliknya. */
    public function view(User $user, Stock $stock): bool
    {
        return $user->isAdmin() || $this->owns($user, $stock);
    }

    public function create(User $user): bool
    {
        return $user->isMember();
    }

    public function update(User $user, Stock $stock): bool
    {
        return $this->owns($user, $stock);
    }

    public function delete(User $user, Stock $stock): bool
    {
        return $this->owns($user, $stock);
    }

    public function adjust(User $user, Stock $stock): bool
    {
        return $this->owns($user, $stock);
    }

    private function owns(User $user, Stock $stock): bool
    {
        return $user->isMember() && $stock->user_id === $user->id;
    }
}
