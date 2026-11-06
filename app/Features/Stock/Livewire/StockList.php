<?php

namespace App\Features\Stock\Livewire;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Ai\DTO\GeneratedStockItem;
use App\Features\Stock\Contracts\StockRepositoryInterface;
use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use App\Features\Stock\Services\StockExcelService;
use App\Features\Stock\Services\StockService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Throwable;

#[Layout('components.layouts.app')]
#[Title('Daftar Stok')]
class StockList extends Component
{
    use WithFileUploads, WithPagination;

    public string $search = '';

    public string $status = '';

    // Form CRUD
    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $sku = '';

    public string $description = '';

    public string $quantity = '0';

    public string $unit = 'pcs';

    public string $price = '';

    public string $min_stock = '5';

    // Import Excel
    public bool $showImport = false;

    public $importFile = null;

    // Generator AI
    public bool $showAi = false;

    public string $aiPrompt = '';

    public string $aiCount = '5';

    /** @var array<int, array<string, mixed>> */
    public array $aiItems = [];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        Gate::authorize('create', Stock::class);

        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id, StockService $service): void
    {
        $stock = $service->find($id);
        Gate::authorize('update', $stock);

        $this->resetValidation();
        $this->editingId = $stock->id;
        $this->name = $stock->name;
        $this->sku = (string) $stock->sku;
        $this->description = (string) $stock->description;
        $this->quantity = (string) $stock->quantity;
        $this->unit = $stock->unit;
        $this->price = (string) $stock->price;
        $this->min_stock = (string) $stock->min_stock;
        $this->showForm = true;
    }

    public function save(StockService $service): void
    {
        $data = $this->validate(StockService::rules());

        if ($this->editingId) {
            $stock = $service->find($this->editingId);
            Gate::authorize('update', $stock);
            $service->update($stock, $data);
            $message = 'Stok berhasil diperbarui.';
        } else {
            Gate::authorize('create', Stock::class);
            $service->create(Auth::user(), $data);
            $message = 'Stok berhasil ditambahkan.';
        }

        $this->closeForm();
        session()->flash('status', $message);
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    public function delete(int $id, StockService $service): void
    {
        $stock = $service->find($id);
        Gate::authorize('delete', $stock);

        $service->delete($stock);
        session()->flash('status', 'Stok berhasil dihapus.');
    }

    public function adjust(int $id, int $delta, StockService $service): void
    {
        $stock = $service->find($id);
        Gate::authorize('adjust', $stock);

        $service->adjustQuantity($stock, $delta);
    }

    public function exportExcel(StockExcelService $excel, StockRepositoryInterface $repository)
    {
        $content = $excel->export($repository->allByUser(Auth::user()->id));

        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, 'stocks-'.now()->format('Ymd-His').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function importExcel(StockExcelService $excel, StockService $service): void
    {
        $this->validate([
            'importFile' => ['required', 'file', 'extensions:xlsx', 'max:2048'],
        ]);

        try {
            $rows = $excel->import($this->importFile->getRealPath());
        } catch (Throwable) {
            $this->addError('importFile', 'File Excel tidak dapat dibaca.');

            return;
        }

        $user = Auth::user();
        foreach ($rows as $row) {
            $service->create($user, $row);
        }

        $this->importFile = null;
        $this->showImport = false;
        session()->flash('status', count($rows).' stok berhasil diimpor.');
    }

    public function openAi(): void
    {
        $this->resetValidation();
        $this->aiItems = [];
        $this->showAi = true;
    }

    public function closeAi(): void
    {
        $this->showAi = false;
        $this->aiItems = [];
    }

    public function generateAi(AiClientInterface $ai): void
    {
        $this->validate([
            'aiPrompt' => ['required', 'string', 'max:500'],
            'aiCount' => ['required', 'integer', 'between:1,10'],
        ]);

        try {
            $items = $ai->generate($this->aiPrompt, (int) $this->aiCount);
        } catch (Throwable $e) {
            $this->addError('aiPrompt', $e->getMessage());

            return;
        }

        if ($items === []) {
            $this->addError('aiPrompt', 'AI tidak menghasilkan item. Coba prompt lain.');

            return;
        }

        $this->aiItems = array_map(
            fn (GeneratedStockItem $item) => ['selected' => true] + $item->toArray(),
            $items,
        );
    }

    public function saveAiItems(StockService $service): void
    {
        $selected = array_filter($this->aiItems, fn (array $item) => ! empty($item['selected']));

        if ($selected === []) {
            $this->addError('aiItems', 'Pilih minimal satu item untuk disimpan.');

            return;
        }

        $rules = [];
        foreach (StockService::rules() as $field => $rule) {
            $rules["aiItems.*.{$field}"] = $rule;
        }

        Validator::make(['aiItems' => $selected], $rules)->validate();

        $user = Auth::user();
        foreach ($selected as $item) {
            $service->create($user, $item);
        }

        $this->closeAi();
        session()->flash('status', count($selected).' item hasil AI berhasil disimpan.');
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'sku', 'description', 'quantity', 'unit', 'price', 'min_stock']);
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.stock.stock-list', [
            'stocks' => app(StockService::class)->paginate(
                Auth::user(),
                $this->search !== '' ? $this->search : null,
                $this->status !== '' ? $this->status : null,
            ),
            'statuses' => StockStatus::cases(),
        ]);
    }
}
