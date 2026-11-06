<?php

namespace App\Features\Ai\Contracts;

use App\Features\Ai\DTO\GeneratedStockItem;

interface AiClientInterface
{
    /**
     * Hasilkan rekomendasi item stok berdasarkan prompt pengguna.
     *
     * @return array<int, GeneratedStockItem>
     */
    public function generate(string $prompt, int $count = 5): array;
}
