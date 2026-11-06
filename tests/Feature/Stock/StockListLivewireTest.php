<?php

namespace Tests\Feature\Stock;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Ai\DTO\GeneratedStockItem;
use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Livewire\StockList;
use App\Features\Stock\Models\Stock;
use App\Features\Stock\Services\StockExcelService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;
use Tests\TestCase;

class StockListLivewireTest extends TestCase
{
    private function xlsxContent(array $rows): string
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray($rows, null, 'A1');

        $path = tempnam(sys_get_temp_dir(), 'xl_');
        (new Xlsx($spreadsheet))->save($path);
        $content = file_get_contents($path);
        unlink($path);

        return $content;
    }

    private function bindAi(array $items = [], ?\Throwable $exception = null): void
    {
        $stub = $this->createStub(AiClientInterface::class);

        if ($exception) {
            $stub->method('generate')->willThrowException($exception);
        } else {
            $stub->method('generate')->willReturn($items);
        }

        $this->app->instance(AiClientInterface::class, $stub);
    }

    public function test_lists_only_own_stocks(): void
    {
        $user = $this->makeMember();
        $this->makeStock($user, ['name' => 'Milik Saya']);
        $this->makeStock($this->makeMember(), ['name' => 'Milik Orang']);

        Livewire::actingAs($user)->test(StockList::class)
            ->assertSee('Milik Saya')
            ->assertDontSee('Milik Orang');
    }

    public function test_shows_empty_state(): void
    {
        Livewire::actingAs($this->makeMember())->test(StockList::class)
            ->assertSee('Belum ada data stok.');
    }

    public function test_shows_price_and_cover(): void
    {
        Storage::fake('public');
        $user = $this->makeMember();
        $this->makeStock($user, ['name' => 'Bercover', 'price' => 1500000, 'cover_image_path' => 'stock-covers/x.jpg']);
        $this->makeStock($user, ['name' => 'Tanpa Harga', 'price' => null]);

        Livewire::actingAs($user)->test(StockList::class)
            ->assertSee('Rp 1.500.000')
            ->assertSeeHtml('stock-covers/x.jpg');
    }

    public function test_search_and_status_filter(): void
    {
        $user = $this->makeMember();
        $this->makeStock($user, ['name' => 'Kabel USB', 'sku' => 'KBL-1', 'status' => StockStatus::Available]);
        $this->makeStock($user, ['name' => 'Mouse Gaming', 'sku' => 'MSE-1', 'status' => StockStatus::LowStock]);

        Livewire::actingAs($user)->test(StockList::class)
            ->set('search', 'Kabel')
            ->assertSee('Kabel USB')
            ->assertDontSee('Mouse Gaming')
            ->set('search', 'MSE-1')
            ->assertSee('Mouse Gaming')
            ->set('search', '')
            ->set('status', 'low_stock')
            ->assertSee('Mouse Gaming')
            ->assertDontSee('Kabel USB');
    }

    public function test_pagination_shows_ten_per_page(): void
    {
        $user = $this->makeMember();
        Stock::factory()->count(12)->for($user)->create();

        Livewire::actingAs($user)->test(StockList::class)
            ->assertViewHas('stocks', fn ($stocks) => $stocks->count() === 10 && $stocks->total() === 12);
    }

    public function test_open_create_form_and_close(): void
    {
        Livewire::actingAs($this->makeMember())->test(StockList::class)
            ->call('openCreate')
            ->assertSet('showForm', true)
            ->assertSee('Tambah Stok')
            ->call('closeForm')
            ->assertSet('showForm', false);
    }

    public function test_create_stock(): void
    {
        $user = $this->makeMember();

        Livewire::actingAs($user)->test(StockList::class)
            ->call('openCreate')
            ->set('name', 'Pulpen')
            ->set('sku', 'PLP-1')
            ->set('description', '**bagus**')
            ->set('quantity', '3')
            ->set('unit', 'box')
            ->set('price', '2500')
            ->set('min_stock', '5')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showForm', false);

        $stock = Stock::query()->where('name', 'Pulpen')->firstOrFail();
        $this->assertSame($user->id, $stock->user_id);
        $this->assertSame(StockStatus::LowStock, $stock->status);
        $this->assertSame(3, $stock->quantity);
    }

    public function test_create_validation_errors(): void
    {
        Livewire::actingAs($this->makeMember())->test(StockList::class)
            ->call('openCreate')
            ->set('name', '')
            ->set('quantity', 'abc')
            ->set('min_stock', '-1')
            ->set('price', '-5')
            ->call('save')
            ->assertHasErrors(['name', 'quantity', 'min_stock', 'price']);

        $this->assertSame(0, Stock::query()->count());
    }

    public function test_edit_and_update_stock(): void
    {
        $user = $this->makeMember();
        $stock = $this->makeStock($user, [
            'name' => 'Lama', 'sku' => null, 'description' => null, 'price' => null,
            'quantity' => 2, 'min_stock' => 5, 'status' => StockStatus::LowStock,
        ]);

        Livewire::actingAs($user)->test(StockList::class)
            ->call('edit', $stock->id)
            ->assertSet('showForm', true)
            ->assertSet('editingId', $stock->id)
            ->assertSet('name', 'Lama')
            ->assertSet('quantity', '2')
            ->set('name', 'Baru')
            ->set('quantity', '40')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showForm', false)
            ->assertSet('editingId', null);

        $fresh = $stock->fresh();
        $this->assertSame('Baru', $fresh->name);
        $this->assertSame(StockStatus::Available, $fresh->status);
    }

    public function test_edit_forbidden_for_other_users_stock(): void
    {
        $stock = $this->makeStock($this->makeMember(), ['name' => 'Bukan Milikmu']);

        Livewire::actingAs($this->makeMember())->test(StockList::class)
            ->call('edit', $stock->id)
            ->assertForbidden();
    }

    public function test_delete_stock(): void
    {
        $user = $this->makeMember();
        $stock = $this->makeStock($user);

        Livewire::actingAs($user)->test(StockList::class)->call('delete', $stock->id);

        $this->assertDatabaseMissing('stocks', ['id' => $stock->id]);
    }

    public function test_delete_forbidden_for_other_users_stock(): void
    {
        $stock = $this->makeStock($this->makeMember());

        Livewire::actingAs($this->makeMember())->test(StockList::class)
            ->call('delete', $stock->id)
            ->assertForbidden();

        $this->assertDatabaseHas('stocks', ['id' => $stock->id]);
    }

    public function test_adjust_quantity_updates_status(): void
    {
        $user = $this->makeMember();
        $stock = $this->makeStock($user, ['quantity' => 6, 'min_stock' => 5, 'status' => StockStatus::Available]);

        $component = Livewire::actingAs($user)->test(StockList::class);

        $component->call('adjust', $stock->id, -1);
        $this->assertSame(5, $stock->fresh()->quantity);
        $this->assertSame(StockStatus::LowStock, $stock->fresh()->status);

        $component->call('adjust', $stock->id, -10);
        $this->assertSame(0, $stock->fresh()->quantity);
        $this->assertSame(StockStatus::OutOfStock, $stock->fresh()->status);

        $component->call('adjust', $stock->id, 20);
        $this->assertSame(StockStatus::Available, $stock->fresh()->status);
    }

    public function test_adjust_forbidden_for_other_users_stock(): void
    {
        $stock = $this->makeStock($this->makeMember(), ['quantity' => 10]);

        Livewire::actingAs($this->makeMember())->test(StockList::class)
            ->call('adjust', $stock->id, 1)
            ->assertForbidden();

        $this->assertSame(10, $stock->fresh()->quantity);
    }

    public function test_export_excel_downloads_file(): void
    {
        $user = $this->makeMember();
        $this->makeStock($user);

        Livewire::actingAs($user)->test(StockList::class)
            ->call('exportExcel')
            ->assertFileDownloaded();
    }

    public function test_import_excel_creates_stocks_for_current_user(): void
    {
        $user = $this->makeMember();
        $content = $this->xlsxContent([
            StockExcelService::HEADERS,
            ['Pulpen', 'PLP-1', 10, 'box', 2500, 3],
            ['Buku', null, 2, 'pcs', null, 5],
        ]);

        Livewire::actingAs($user)->test(StockList::class)
            ->set('showImport', true)
            ->set('importFile', UploadedFile::fake()->createWithContent('stocks.xlsx', $content))
            ->call('importExcel')
            ->assertHasNoErrors()
            ->assertSet('showImport', false);

        $this->assertSame(2, $user->stocks()->count());
        $this->assertSame(StockStatus::LowStock, $user->stocks()->where('name', 'Buku')->firstOrFail()->status);
        $this->assertSame(StockStatus::Available, $user->stocks()->where('name', 'Pulpen')->firstOrFail()->status);
    }

    public function test_import_requires_file_with_xlsx_extension(): void
    {
        $component = Livewire::actingAs($this->makeMember())->test(StockList::class);

        $component->call('importExcel')->assertHasErrors(['importFile' => 'required']);

        $component
            ->set('importFile', UploadedFile::fake()->createWithContent('data.txt', 'abc'))
            ->call('importExcel')
            ->assertHasErrors(['importFile']);
    }

    public function test_import_unreadable_file_shows_error(): void
    {
        $user = $this->makeMember();

        Livewire::actingAs($user)->test(StockList::class)
            ->set('importFile', UploadedFile::fake()->createWithContent('rusak.xlsx', 'bukan file excel'))
            ->call('importExcel')
            ->assertHasErrors(['importFile']);

        $this->assertSame(0, $user->stocks()->count());
    }

    public function test_ai_generate_preview_and_save_selected_items(): void
    {
        $user = $this->makeMember();
        $this->bindAi([
            new GeneratedStockItem('Pulpen', 'PLP', 'Pulpen **biru**', 24, 'pcs', 3500.0, 6),
            new GeneratedStockItem('Buku Tulis', null, null, 3, 'pack', null, 5),
            new GeneratedStockItem('Penghapus', 'PNG', null, 10, 'pcs', 1000.0, 2),
        ]);

        Livewire::actingAs($user)->test(StockList::class)
            ->call('openAi')
            ->assertSet('showAi', true)
            ->set('aiPrompt', 'stok toko alat tulis')
            ->set('aiCount', '3')
            ->call('generateAi')
            ->assertHasNoErrors()
            ->assertSet('aiItems.0.name', 'Pulpen')
            ->assertSet('aiItems.0.selected', true)
            ->set('aiItems.1.name', 'Buku Tulis Edit')
            ->set('aiItems.2.selected', false)
            ->call('saveAiItems')
            ->assertHasNoErrors()
            ->assertSet('showAi', false)
            ->assertSet('aiItems', []);

        $this->assertSame(2, $user->stocks()->count());
        $this->assertDatabaseHas('stocks', ['name' => 'Buku Tulis Edit', 'user_id' => $user->id]);
        $this->assertDatabaseMissing('stocks', ['name' => 'Penghapus']);
    }

    public function test_ai_generate_requires_prompt_and_valid_count(): void
    {
        $this->bindAi([new GeneratedStockItem('X')]);

        Livewire::actingAs($this->makeMember())->test(StockList::class)
            ->set('aiPrompt', '')
            ->set('aiCount', '99')
            ->call('generateAi')
            ->assertHasErrors(['aiPrompt', 'aiCount']);
    }

    public function test_ai_generate_shows_service_error(): void
    {
        $this->bindAi([], new RuntimeException('OPENAI_API_KEY belum dikonfigurasi.'));

        Livewire::actingAs($this->makeMember())->test(StockList::class)
            ->call('openAi')
            ->set('aiPrompt', 'apa saja')
            ->call('generateAi')
            ->assertHasErrors(['aiPrompt'])
            ->assertSee('OPENAI_API_KEY belum dikonfigurasi.');
    }

    public function test_ai_generate_with_empty_result_shows_error(): void
    {
        $this->bindAi([]);

        Livewire::actingAs($this->makeMember())->test(StockList::class)
            ->set('aiPrompt', 'apa saja')
            ->call('generateAi')
            ->assertHasErrors(['aiPrompt'])
            ->assertSet('aiItems', []);
    }

    public function test_ai_save_requires_at_least_one_selected_item(): void
    {
        $user = $this->makeMember();
        $this->bindAi([new GeneratedStockItem('Pulpen', null, null, 5)]);

        Livewire::actingAs($user)->test(StockList::class)
            ->set('aiPrompt', 'apa saja')
            ->call('generateAi')
            ->set('aiItems.0.selected', false)
            ->call('saveAiItems')
            ->assertHasErrors(['aiItems']);

        $this->assertSame(0, $user->stocks()->count());
    }

    public function test_ai_save_validates_edited_items(): void
    {
        $user = $this->makeMember();
        $this->bindAi([new GeneratedStockItem('Pulpen', null, null, 5)]);

        Livewire::actingAs($user)->test(StockList::class)
            ->set('aiPrompt', 'apa saja')
            ->call('generateAi')
            ->set('aiItems.0.name', '')
            ->call('saveAiItems')
            ->assertHasErrors(['aiItems.0.name']);

        $this->assertSame(0, $user->stocks()->count());
    }

    public function test_close_ai_resets_state(): void
    {
        $this->bindAi([new GeneratedStockItem('Pulpen')]);

        Livewire::actingAs($this->makeMember())->test(StockList::class)
            ->set('aiPrompt', 'apa saja')
            ->call('generateAi')
            ->call('closeAi')
            ->assertSet('showAi', false)
            ->assertSet('aiItems', []);
    }
}
