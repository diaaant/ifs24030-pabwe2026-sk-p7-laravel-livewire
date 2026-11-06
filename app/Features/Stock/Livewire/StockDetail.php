<?php

namespace App\Features\Stock\Livewire;

use App\Features\Stock\Models\Stock;
use App\Features\Stock\Services\StockService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
#[Title('Detail Stok')]
class StockDetail extends Component
{
    use WithFileUploads;

    public Stock $stock;

    public bool $editing = false;

    public string $name = '';

    public string $sku = '';

    public string $description = '';

    public string $quantity = '0';

    public string $unit = 'pcs';

    public string $price = '';

    public string $min_stock = '5';

    public $cover = null;

    public function mount(Stock $stock): void
    {
        Gate::authorize('view', $stock);

        $this->stock = $stock;
        $this->fillForm();
    }

    public function startEdit(): void
    {
        Gate::authorize('update', $this->stock);

        $this->resetValidation();
        $this->fillForm();
        $this->editing = true;
    }

    public function cancelEdit(): void
    {
        $this->resetValidation();
        $this->fillForm();
        $this->editing = false;
    }

    public function save(StockService $service): void
    {
        Gate::authorize('update', $this->stock);

        $data = $this->validate(StockService::rules());

        $this->stock = $service->update($this->stock, $data);
        $this->fillForm();
        $this->editing = false;
        session()->flash('status', 'Stok berhasil diperbarui.');
    }

    public function adjust(int $delta, StockService $service): void
    {
        Gate::authorize('adjust', $this->stock);

        $this->stock = $service->adjustQuantity($this->stock, $delta);
        $this->fillForm();
    }

    public function saveCover(StockService $service): void
    {
        Gate::authorize('update', $this->stock);

        $this->validate(['cover' => ['required', 'image', 'max:2048']]);

        $this->stock = $service->uploadCover($this->stock, $this->cover);
        $this->cover = null;
        session()->flash('status', 'Cover berhasil diperbarui.');
    }

    public function deleteStock(StockService $service): mixed
    {
        Gate::authorize('delete', $this->stock);

        $service->delete($this->stock);
        session()->flash('status', 'Stok berhasil dihapus.');

        return $this->redirect(route('stocks.index'));
    }

    private function fillForm(): void
    {
        $this->name = $this->stock->name;
        $this->sku = (string) $this->stock->sku;
        $this->description = (string) $this->stock->description;
        $this->quantity = (string) $this->stock->quantity;
        $this->unit = $this->stock->unit;
        $this->price = (string) $this->stock->price;
        $this->min_stock = (string) $this->stock->min_stock;
    }

    public function render()
    {
        return view('livewire.stock.stock-detail', [
            'descriptionHtml' => Str::markdown((string) $this->stock->description, [
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
            ]),
        ]);
    }
}
