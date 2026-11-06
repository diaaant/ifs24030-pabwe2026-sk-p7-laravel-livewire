<?php

namespace App\Features\Stock\Contracts;

use App\Features\Stock\Models\Stock;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface StockRepositoryInterface
{
    /** @return LengthAwarePaginator<int, Stock> */
    public function paginateByUser(int $userId, ?string $search = null, ?string $status = null, int $perPage = 10): LengthAwarePaginator;

    /** @return Collection<int, Stock> */
    public function allByUser(int $userId): Collection;

    public function findOrFail(int $id): Stock;

    /** @param array<string, mixed> $data */
    public function create(array $data): Stock;

    /** @param array<string, mixed> $data */
    public function update(Stock $stock, array $data): Stock;

    public function delete(Stock $stock): void;
}
