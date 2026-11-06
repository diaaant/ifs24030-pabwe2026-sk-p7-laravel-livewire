<?php

namespace App\Features\Stock\Services;

use App\Features\Stock\Contracts\StockRepositoryInterface;
use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class StockService
{
    public function __construct(private readonly StockRepositoryInterface $repository) {}

    /**
     * Aturan validasi form stok (dipakai StockList & StockDetail).
     *
     * @return array<string, array<int, string>>
     */
    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:65000'],
            'quantity' => ['required', 'integer', 'min:0', 'max:4000000000'],
            'unit' => ['required', 'string', 'max:30'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:9999999999999'],
            'min_stock' => ['required', 'integer', 'min:0', 'max:4000000000'],
        ];
    }

    /** @return LengthAwarePaginator<int, Stock> */
    public function paginate(User $user, ?string $search = null, ?string $status = null, int $perPage = 10): LengthAwarePaginator
    {
        return $this->repository->paginateByUser($user->id, $search, $status, $perPage);
    }

    public function find(int $id): Stock
    {
        return $this->repository->findOrFail($id);
    }

    /** @param array<string, mixed> $data */
    public function create(User $user, array $data): Stock
    {
        $payload = $this->normalize($data);
        $payload['user_id'] = $user->id;

        return $this->repository->create($payload);
    }

    /** @param array<string, mixed> $data */
    public function update(Stock $stock, array $data): Stock
    {
        return $this->repository->update($stock, $this->normalize($data));
    }

    public function delete(Stock $stock): void
    {
        if ($stock->cover_image_path) {
            Storage::disk('public')->delete($stock->cover_image_path);
        }

        $this->repository->delete($stock);
    }

    /**
     * Tambah / kurangi quantity, lalu hitung ulang status. Quantity tidak pernah di bawah 0.
     */
    public function adjustQuantity(Stock $stock, int $delta): Stock
    {
        $quantity = max(0, $stock->quantity + $delta);

        return $this->repository->update($stock, [
            'quantity' => $quantity,
            'status' => StockStatus::fromQuantity($quantity, $stock->min_stock),
        ]);
    }

    public function uploadCover(Stock $stock, UploadedFile $file): Stock
    {
        if ($stock->cover_image_path) {
            Storage::disk('public')->delete($stock->cover_image_path);
        }

        $path = $file->store('stock-covers', 'public');

        return $this->repository->update($stock, ['cover_image_path' => $path]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalize(array $data): array
    {
        $quantity = (int) $data['quantity'];
        $minStock = (int) ($data['min_stock'] ?? 5);

        return [
            'name' => trim((string) $data['name']),
            'sku' => filled($data['sku'] ?? null) ? trim((string) $data['sku']) : null,
            'description' => filled($data['description'] ?? null) ? (string) $data['description'] : null,
            'quantity' => $quantity,
            'unit' => filled($data['unit'] ?? null) ? trim((string) $data['unit']) : 'pcs',
            'price' => isset($data['price']) && is_numeric($data['price']) ? (float) $data['price'] : null,
            'min_stock' => $minStock,
            'status' => StockStatus::fromQuantity($quantity, $minStock),
        ];
    }
}
