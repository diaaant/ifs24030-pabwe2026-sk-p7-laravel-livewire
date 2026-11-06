<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-bold">Inventaris Stok Barang</h1>
        <div class="flex flex-wrap gap-2 text-sm">
            <button type="button" wire:click="openCreate" class="rounded bg-indigo-600 px-3 py-2 font-semibold text-white hover:bg-indigo-700">+ Tambah Stok</button>
            <button type="button" wire:click="openAi" class="rounded bg-purple-600 px-3 py-2 font-semibold text-white hover:bg-purple-700">Generate AI</button>
            <button type="button" wire:click="$set('showImport', true)" class="rounded bg-slate-700 px-3 py-2 font-semibold text-white hover:bg-slate-800">Import Excel</button>
            <button type="button" wire:click="exportExcel" class="rounded bg-emerald-600 px-3 py-2 font-semibold text-white hover:bg-emerald-700">Export Excel</button>
        </div>
    </div>

    @if (session('status'))
        <div class="rounded border border-green-200 bg-green-50 px-4 py-2 text-sm text-green-800">{{ session('status') }}</div>
    @endif

    <div class="flex flex-wrap gap-3">
        <input type="search" wire:model.live.debounce.300ms="search" placeholder="Cari nama / SKU..."
               class="w-full max-w-xs rounded border border-slate-300 px-3 py-2 text-sm">
        <select wire:model.live="status" class="rounded border border-slate-300 px-3 py-2 text-sm">
            <option value="">Semua status</option>
            @foreach ($statuses as $s)
                <option value="{{ $s->value }}">{{ $s->label() }}</option>
            @endforeach
        </select>
    </div>

    <div class="overflow-x-auto rounded-lg bg-white shadow">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
            <tr>
                <th class="px-3 py-3">Barang</th>
                <th class="px-3 py-3">SKU</th>
                <th class="px-3 py-3">Jumlah</th>
                <th class="px-3 py-3">Harga</th>
                <th class="px-3 py-3">Status</th>
                <th class="px-3 py-3 text-right">Aksi</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($stocks as $stock)
                <tr wire:key="stock-{{ $stock->id }}" class="border-t">
                    <td class="px-3 py-2">
                        <div class="flex items-center gap-3">
                            @if ($stock->coverImageUrl())
                                <img src="{{ $stock->coverImageUrl() }}" alt="Cover" class="h-10 w-10 rounded object-cover">
                            @endif
                            <a href="{{ route('stocks.show', $stock) }}" class="font-medium text-indigo-600 hover:underline">{{ $stock->name }}</a>
                        </div>
                    </td>
                    <td class="px-3 py-2">{{ $stock->sku ?? '-' }}</td>
                    <td class="px-3 py-2">
                        <div class="flex items-center gap-2">
                            <button type="button" wire:click="adjust({{ $stock->id }}, -1)" class="h-7 w-7 rounded border border-slate-300 hover:bg-slate-100" title="Kurangi">−</button>
                            <span class="min-w-[2rem] text-center font-semibold">{{ $stock->quantity }}</span>
                            <button type="button" wire:click="adjust({{ $stock->id }}, 1)" class="h-7 w-7 rounded border border-slate-300 hover:bg-slate-100" title="Tambah">+</button>
                            <span class="text-slate-500">{{ $stock->unit }}</span>
                        </div>
                    </td>
                    <td class="px-3 py-2">{{ $stock->price !== null ? 'Rp '.number_format((float) $stock->price, 0, ',', '.') : '-' }}</td>
                    <td class="px-3 py-2">
                        <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $stock->status->badgeClass() }}">{{ $stock->status->label() }}</span>
                    </td>
                    <td class="px-3 py-2 text-right">
                        <button type="button" wire:click="edit({{ $stock->id }})" class="text-indigo-600 hover:underline">Edit</button>
                        <button type="button" wire:click="delete({{ $stock->id }})" wire:confirm="Hapus stok ini?" class="ml-3 text-red-600 hover:underline">Hapus</button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-3 py-8 text-center text-slate-500">Belum ada data stok.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $stocks->links() }}</div>

    {{-- Modal tambah / edit --}}
    @if ($showForm)
        @php($preview = \App\Features\Stock\Enums\StockStatus::fromQuantity((int) $quantity, (int) $min_stock))
        <div class="fixed inset-0 z-40 flex items-center justify-center overflow-y-auto bg-black/50 p-4">
            <div class="w-full max-w-2xl rounded-lg bg-white p-6 shadow-xl">
                <h2 class="mb-4 text-lg font-bold">{{ $editingId ? 'Edit Stok' : 'Tambah Stok' }}</h2>
                <form wire:submit="save" class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-sm font-medium">Nama Barang</label>
                        <input type="text" wire:model="name" class="w-full rounded border border-slate-300 px-3 py-2">
                        @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">SKU</label>
                        <input type="text" wire:model="sku" class="w-full rounded border border-slate-300 px-3 py-2">
                        @error('sku') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Satuan</label>
                        <input type="text" wire:model="unit" class="w-full rounded border border-slate-300 px-3 py-2">
                        @error('unit') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Jumlah</label>
                        <input type="number" min="0" wire:model.live="quantity" class="w-full rounded border border-slate-300 px-3 py-2">
                        @error('quantity') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Stok Minimum</label>
                        <input type="number" min="0" wire:model.live="min_stock" class="w-full rounded border border-slate-300 px-3 py-2">
                        @error('min_stock') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Harga (Rp)</label>
                        <input type="number" min="0" step="0.01" wire:model="price" class="w-full rounded border border-slate-300 px-3 py-2">
                        @error('price') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Status (otomatis)</label>
                        <span class="inline-block rounded-full px-3 py-2 text-xs font-semibold {{ $preview->badgeClass() }}">{{ $preview->label() }}</span>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-sm font-medium">Deskripsi (Markdown)</label>
                        <textarea wire:model="description" rows="4" class="w-full rounded border border-slate-300 px-3 py-2 font-mono text-sm"></textarea>
                        @error('description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex justify-end gap-2 sm:col-span-2">
                        <button type="button" wire:click="closeForm" class="rounded border border-slate-300 px-4 py-2 text-sm">Batal</button>
                        <button type="submit" class="rounded bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Modal import Excel --}}
    @if ($showImport)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
                <h2 class="mb-2 text-lg font-bold">Import dari Excel</h2>
                <p class="mb-4 text-xs text-slate-500">Kolom: Name, SKU, Quantity, Unit, Price, Min Stock (baris pertama = header).</p>
                <input type="file" wire:model="importFile" accept=".xlsx" class="w-full text-sm">
                @error('importFile') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                <div class="mt-4 flex justify-end gap-2">
                    <button type="button" wire:click="$set('showImport', false)" class="rounded border border-slate-300 px-4 py-2 text-sm">Batal</button>
                    <button type="button" wire:click="importExcel" class="rounded bg-slate-800 px-4 py-2 text-sm font-semibold text-white">Import</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal generator AI --}}
    @if ($showAi)
        <div class="fixed inset-0 z-40 flex items-center justify-center overflow-y-auto bg-black/50 p-4">
            <div class="w-full max-w-4xl rounded-lg bg-white p-6 shadow-xl">
                <h2 class="mb-4 text-lg font-bold">Generate Item Stok dengan AI</h2>

                <div class="flex flex-wrap items-end gap-3">
                    <div class="min-w-[16rem] flex-1">
                        <label class="mb-1 block text-sm font-medium">Prompt</label>
                        <input type="text" wire:model="aiPrompt" placeholder="Contoh: stok awal toko alat tulis kampus"
                               class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Jumlah</label>
                        <input type="number" min="1" max="10" wire:model="aiCount" class="w-24 rounded border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <button type="button" wire:click="generateAi" wire:loading.attr="disabled" wire:target="generateAi"
                            class="rounded bg-purple-600 px-4 py-2 text-sm font-semibold text-white">
                        <span wire:loading.remove wire:target="generateAi">Generate</span>
                        <span wire:loading wire:target="generateAi">Memproses...</span>
                    </button>
                </div>
                @error('aiPrompt') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                @error('aiCount') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                @error('aiItems') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror

                @if (count($aiItems) > 0)
                    <div class="mt-4 max-h-96 overflow-auto rounded border">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                            <tr>
                                <th class="px-2 py-2">Pilih</th><th class="px-2 py-2">Nama</th><th class="px-2 py-2">SKU</th>
                                <th class="px-2 py-2">Jumlah</th><th class="px-2 py-2">Satuan</th><th class="px-2 py-2">Harga</th><th class="px-2 py-2">Min</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($aiItems as $i => $item)
                                <tr wire:key="ai-{{ $i }}" class="border-t align-top">
                                    <td class="px-2 py-2"><input type="checkbox" wire:model="aiItems.{{ $i }}.selected"></td>
                                    <td class="px-2 py-2">
                                        <input type="text" wire:model="aiItems.{{ $i }}.name" class="w-48 rounded border border-slate-300 px-2 py-1">
                                        @error("aiItems.$i.name") <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                                    </td>
                                    <td class="px-2 py-2"><input type="text" wire:model="aiItems.{{ $i }}.sku" class="w-28 rounded border border-slate-300 px-2 py-1"></td>
                                    <td class="px-2 py-2"><input type="number" min="0" wire:model="aiItems.{{ $i }}.quantity" class="w-20 rounded border border-slate-300 px-2 py-1"></td>
                                    <td class="px-2 py-2"><input type="text" wire:model="aiItems.{{ $i }}.unit" class="w-20 rounded border border-slate-300 px-2 py-1"></td>
                                    <td class="px-2 py-2"><input type="number" min="0" wire:model="aiItems.{{ $i }}.price" class="w-28 rounded border border-slate-300 px-2 py-1"></td>
                                    <td class="px-2 py-2"><input type="number" min="0" wire:model="aiItems.{{ $i }}.min_stock" class="w-16 rounded border border-slate-300 px-2 py-1"></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <div class="mt-4 flex justify-end gap-2">
                    <button type="button" wire:click="closeAi" class="rounded border border-slate-300 px-4 py-2 text-sm">Tutup</button>
                    @if (count($aiItems) > 0)
                        <button type="button" wire:click="saveAiItems" class="rounded bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Simpan yang dipilih</button>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
