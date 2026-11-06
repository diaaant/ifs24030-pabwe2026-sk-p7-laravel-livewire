<?php

namespace App\Features\Ai\Services;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Ai\DTO\GeneratedStockItem;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAiStockGeneratorService implements AiClientInterface
{
    public function generate(string $prompt, int $count = 5): array
    {
        $apiKey = config('openai.api_key');

        if (blank($apiKey)) {
            throw new RuntimeException('OPENAI_API_KEY belum dikonfigurasi.');
        }

        $response = Http::withToken($apiKey)
            ->timeout((int) config('openai.timeout'))
            ->post(rtrim((string) config('openai.base_url'), '/').'/chat/completions', [
                'model' => config('openai.model'),
                'temperature' => 0.7,
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    ['role' => 'system', 'content' => $this->systemPrompt($count)],
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Gagal menghubungi OpenAI (HTTP '.$response->status().').');
        }

        $decoded = json_decode((string) $response->json('choices.0.message.content'), true);
        $items = is_array($decoded) && is_array($decoded['items'] ?? null) ? $decoded['items'] : [];

        return collect($items)
            ->filter(fn ($item) => is_array($item) && filled($item['name'] ?? null))
            ->map(fn (array $item) => GeneratedStockItem::fromArray($item))
            ->values()
            ->all();
    }

    private function systemPrompt(int $count): string
    {
        return 'Anda adalah asisten manajemen inventaris toko. Berdasarkan permintaan pengguna, '
            ."buat tepat {$count} rekomendasi item stok barang. Balas HANYA dengan JSON valid berbentuk "
            .'{"items":[{"name":string,"sku":string,"description":string (Markdown singkat),'
            .'"quantity":integer,"unit":string,"price":number (Rupiah),"min_stock":integer}]}.';
    }
}
