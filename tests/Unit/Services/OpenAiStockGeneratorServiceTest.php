<?php

namespace Tests\Unit\Services;

use App\Features\Ai\DTO\GeneratedStockItem;
use App\Features\Ai\Services\OpenAiStockGeneratorService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class OpenAiStockGeneratorServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'openai.api_key' => 'test-key',
            'openai.base_url' => 'https://api.openai.com/v1/',
            'openai.model' => 'gpt-4o-mini',
            'openai.timeout' => 5,
        ]);
    }

    private function fakeContent(string $content, int $status = 200): void
    {
        Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => $content]]]], $status)]);
    }

    public function test_throws_when_api_key_missing(): void
    {
        config(['openai.api_key' => null]);
        Http::fake();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('OPENAI_API_KEY');

        (new OpenAiStockGeneratorService)->generate('alat tulis');
    }

    public function test_returns_dto_items_and_sends_expected_request(): void
    {
        $this->fakeContent(json_encode(['items' => [
            ['name' => 'Pulpen', 'sku' => 'PLP', 'description' => 'Pulpen **biru**', 'quantity' => 24, 'unit' => 'pcs', 'price' => 3500, 'min_stock' => 6],
            ['name' => '', 'quantity' => 1],
            'bukan-array',
            ['name' => 'Buku Tulis', 'quantity' => 10],
        ]]));

        $items = (new OpenAiStockGeneratorService)->generate('stok toko alat tulis', 2);

        $this->assertCount(2, $items);
        $this->assertContainsOnlyInstancesOf(GeneratedStockItem::class, $items);
        $this->assertSame('Pulpen', $items[0]->name);
        $this->assertSame(24, $items[0]->quantity);
        $this->assertSame(3500.0, $items[0]->price);
        $this->assertSame('Buku Tulis', $items[1]->name);

        Http::assertSent(function (Request $request) {
            $data = $request->data();

            return $request->url() === 'https://api.openai.com/v1/chat/completions'
                && $request->hasHeader('Authorization', 'Bearer test-key')
                && $data['model'] === 'gpt-4o-mini'
                && $data['messages'][1]['content'] === 'stok toko alat tulis'
                && str_contains($data['messages'][0]['content'], 'tepat 2');
        });
    }

    public function test_throws_on_http_failure(): void
    {
        $this->fakeContent('{}', 500);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('HTTP 500');

        (new OpenAiStockGeneratorService)->generate('apa saja');
    }

    public function test_returns_empty_array_on_invalid_json(): void
    {
        $this->fakeContent('ini bukan json');

        $this->assertSame([], (new OpenAiStockGeneratorService)->generate('apa saja'));
    }

    public function test_returns_empty_array_when_items_missing(): void
    {
        $this->fakeContent(json_encode(['foo' => 'bar']));

        $this->assertSame([], (new OpenAiStockGeneratorService)->generate('apa saja'));
    }
}
