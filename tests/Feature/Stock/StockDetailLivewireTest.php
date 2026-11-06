<?php

namespace Tests\Feature\Stock;

use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Livewire\StockDetail;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class StockDetailLivewireTest extends TestCase
{
    public function test_mount_renders_stock_and_markdown(): void
    {
        $user = $this->makeMember();
        $stock = $this->makeStock($user, [
            'name' => 'Laptop', 'description' => "## Spesifikasi\n\n- RAM 16GB\n\n<script>alert(1)</script>",
        ]);

        Livewire::actingAs($user)->test(StockDetail::class, ['stock' => $stock])
            ->assertSet('name', 'Laptop')
            ->assertSee('Laptop')
            ->assertSeeHtml('<h2>Spesifikasi</h2>')
            ->assertSeeHtml('<li>RAM 16GB</li>')
            ->assertDontSeeHtml('<script>alert(1)</script>');
    }

    public function test_renders_without_description(): void
    {
        $user = $this->makeMember();
        $stock = $this->makeStock($user, ['description' => null]);

        Livewire::actingAs($user)->test(StockDetail::class, ['stock' => $stock])
            ->assertSee('Belum ada deskripsi.');
    }

    public function test_edit_cycle(): void
    {
        $user = $this->makeMember();
        $stock = $this->makeStock($user, ['name' => 'Asli']);

        Livewire::actingAs($user)->test(StockDetail::class, ['stock' => $stock])
            ->call('startEdit')
            ->assertSet('editing', true)
            ->set('name', 'Diubah')
            ->call('cancelEdit')
            ->assertSet('editing', false)
            ->assertSet('name', 'Asli');
    }

    public function test_save_updates_stock_and_recomputes_status(): void
    {
        $user = $this->makeMember();
        $stock = $this->makeStock($user, ['quantity' => 50, 'status' => StockStatus::Available]);

        Livewire::actingAs($user)->test(StockDetail::class, ['stock' => $stock])
            ->call('startEdit')
            ->set('name', 'Nama Baru')
            ->set('sku', 'SKU-9')
            ->set('description', '**tebal**')
            ->set('quantity', '2')
            ->set('unit', 'box')
            ->set('price', '12500')
            ->set('min_stock', '5')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('editing', false);

        $fresh = $stock->fresh();
        $this->assertSame('Nama Baru', $fresh->name);
        $this->assertSame(2, $fresh->quantity);
        $this->assertSame(StockStatus::LowStock, $fresh->status);
        $this->assertSame('12500.00', $fresh->price);
    }

    public function test_save_validation_errors(): void
    {
        $user = $this->makeMember();
        $stock = $this->makeStock($user);

        Livewire::actingAs($user)->test(StockDetail::class, ['stock' => $stock])
            ->call('startEdit')
            ->set('name', '')
            ->set('quantity', '-1')
            ->call('save')
            ->assertHasErrors(['name', 'quantity']);
    }

    public function test_adjust_quantity(): void
    {
        $user = $this->makeMember();
        $stock = $this->makeStock($user, ['quantity' => 6, 'min_stock' => 5, 'status' => StockStatus::Available]);

        Livewire::actingAs($user)->test(StockDetail::class, ['stock' => $stock])
            ->call('adjust', -1)
            ->assertSet('quantity', '5');

        $this->assertSame(StockStatus::LowStock, $stock->fresh()->status);
    }

    public function test_upload_cover(): void
    {
        Storage::fake('public');
        $user = $this->makeMember();
        $stock = $this->makeStock($user);

        Livewire::actingAs($user)->test(StockDetail::class, ['stock' => $stock])
            ->set('cover', UploadedFile::fake()->image('cover.png'))
            ->call('saveCover')
            ->assertHasNoErrors()
            ->assertSet('cover', null);

        Storage::disk('public')->assertExists($stock->fresh()->cover_image_path);
    }

    public function test_upload_cover_rejects_non_image(): void
    {
        Storage::fake('public');
        $user = $this->makeMember();
        $stock = $this->makeStock($user);

        Livewire::actingAs($user)->test(StockDetail::class, ['stock' => $stock])
            ->set('cover', UploadedFile::fake()->create('file.pdf', 10, 'application/pdf'))
            ->call('saveCover')
            ->assertHasErrors(['cover']);
    }

    public function test_delete_stock_redirects_to_list(): void
    {
        $user = $this->makeMember();
        $stock = $this->makeStock($user);

        Livewire::actingAs($user)->test(StockDetail::class, ['stock' => $stock])
            ->call('deleteStock')
            ->assertRedirect(route('stocks.index'));

        $this->assertDatabaseMissing('stocks', ['id' => $stock->id]);
    }
}
