<?php

namespace App\Features\Admin\Livewire;

use App\Features\Admin\Services\AdminUserService;
use App\Features\Stock\Enums\StockStatus;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Stok User')]
class AdminUserStockList extends Component
{
    use WithPagination;

    public User $user;

    public string $search = '';

    public string $status = '';

    public function mount(User $user): void
    {
        $this->user = $user;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.admin.admin-user-stock-list', [
            'stocks' => app(AdminUserService::class)->paginateUserStocks(
                $this->user,
                $this->search !== '' ? $this->search : null,
                $this->status !== '' ? $this->status : null,
            ),
            'statuses' => StockStatus::cases(),
        ]);
    }
}
