<div class="mx-auto max-w-4xl space-y-4">
    @push('head')
        <link rel="stylesheet" href="https://uicdn.toast.com/editor/3.2.2/toastui-editor.min.css">
        <script src="https://uicdn.toast.com/editor/3.2.2/toastui-editor-all.min.js"></script>
    @endpush

    <div class="flex items-center justify-between">
        <!-- Perbaikan: Menambahkan underline permanen & warna lebih gelap -->
        <a href="{{ route('stocks.index') }}" class="text-sm text-indigo-700 underline hover:text-indigo-900">&larr; Kembali ke daftar</a>
        <button type="button" wire:click="deleteStock" wire:confirm="Hapus stok ini?" class="text-sm text-red-700 underline hover:text-red-900">Hapus stok</button>
    </div>

    @if (session('status'))
        <div class="rounded border border-green-200 bg-green-50 px-4 py-2 text-sm text-green-800">{{ session('status') }}</div>
    @endif

    <div class="rounded-lg bg-white p-6 shadow">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold">{{ $stock->name }}</h1>
                <p class="text-sm text-slate-600">SKU: {{ $stock->sku ?? '-' }}</p>
            </div>
            <span class="rounded-full px-3 py-1 text-sm font-semibold {{ $stock->status->badgeClass() }}">{{ $stock->status->label() }}</span>
        </div>

        <div class="mt-6 grid gap-6 md:grid-cols-3">
            <div class="space-y-3">
                @if ($stock->coverImageUrl())
                    <img src="{{ $stock->coverImageUrl() }}" alt="Cover {{ $stock->name }}" class="w-full rounded object-cover">
                @else
                    <!-- Perbaikan Kontras: text-slate-400 -> text-slate-600 -->
                    <div class="flex h-40 items-center justify-center rounded bg-slate-100 text-sm text-slate-600">Belum ada cover</div>
                @endif

                <form wire:submit="saveCover" class="space-y-2">
                    <!-- Perbaikan Aksesibilitas: Menambahkan label tersembunyi -->
                    <label for="cover-upload" class="sr-only">Upload Cover Barang</label>
                    <input id="cover-upload" type="file" wire:model="cover" accept="image/*" class="w-full text-xs">
                    @error('cover') <p class="text-xs text-red-700">{{ $message }}</p> @enderror
                    <button type="submit" class="rounded bg-slate-800 px-3 py-1 text-xs font-semibold text-white">Upload Cover</button>
                </form>
            </div>

            <div class="space-y-4 md:col-span-2">
                <div class="flex items-center gap-3">
                    <button type="button" wire:click="adjust(-1)" class="h-9 w-9 rounded border border-slate-300 text-lg hover:bg-slate-100" aria-label="Kurangi jumlah">−</button>
                    <div class="text-3xl font-bold">{{ $stock->quantity }} <span class="text-base font-normal text-slate-600">{{ $stock->unit }}</span></div>
                    <button type="button" wire:click="adjust(1)" class="h-9 w-9 rounded border border-slate-300 text-lg hover:bg-slate-100" aria-label="Tambah jumlah">+</button>
                </div>

                <dl class="grid grid-cols-2 gap-3 text-sm">
                    <div><dt class="text-slate-600">Harga</dt><dd class="font-medium">{{ $stock->price !== null ? 'Rp '.number_format((float) $stock->price, 0, ',', '.') : '-' }}</dd></div>
                    <div><dt class="text-slate-600">Stok minimum</dt><dd class="font-medium">{{ $stock->min_stock }}</dd></div>
                    <div><dt class="text-slate-600">Dibuat</dt><dd class="font-medium">{{ $stock->created_at?->format('d M Y H:i') }}</dd></div>
                    <div><dt class="text-slate-600">Diperbarui</dt><dd class="font-medium">{{ $stock->updated_at?->format('d M Y H:i') }}</dd></div>
                </dl>
            </div>
        </div>
    </div>

    <div class="rounded-lg bg-white p-6 shadow">
        @if (! $editing)
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-lg font-bold">Deskripsi</h2>
                <button type="button" wire:click="startEdit" class="rounded bg-indigo-700 px-3 py-1 text-sm font-semibold text-white hover:bg-indigo-800">Edit</button>
            </div>
            @if (filled($stock->description))
                <div class="space-y-2 text-sm leading-relaxed [&_a]:text-indigo-700 [&_a]:underline [&_h1]:text-xl [&_h1]:font-bold [&_h2]:text-lg [&_h2]:font-bold [&_ol]:list-decimal [&_ol]:pl-6 [&_ul]:list-disc [&_ul]:pl-6">{!! $descriptionHtml !!}</div>
            @else
                <p class="text-sm text-slate-600">Belum ada deskripsi.</p>
            @endif
        @else
            <h2 class="mb-3 text-lg font-bold">Edit Stok</h2>
            <form wire:submit="save" class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <!-- Perbaikan: Menambahkan for & id -->
                    <label for="edit-name" class="mb-1 block text-sm font-medium">Nama Barang</label>
                    <input id="edit-name" type="text" wire:model="name" class="w-full rounded border border-slate-300 px-3 py-2">
                    @error('name') <p class="mt-1 text-xs text-red-700">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="edit-sku" class="mb-1 block text-sm font-medium">SKU</label>
                    <input id="edit-sku" type="text" wire:model="sku" class="w-full rounded border border-slate-300 px-3 py-2">
                    @error('sku') <p class="mt-1 text-xs text-red-700">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="edit-unit" class="mb-1 block text-sm font-medium">Satuan</label>
                    <input id="edit-unit" type="text" wire:model="unit" class="w-full rounded border border-slate-300 px-3 py-2">
                    @error('unit') <p class="mt-1 text-xs text-red-700">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="edit-quantity" class="mb-1 block text-sm font-medium">Jumlah</label>
                    <input id="edit-quantity" type="number" min="0" wire:model="quantity" class="w-full rounded border border-slate-300 px-3 py-2">
                    @error('quantity') <p class="mt-1 text-xs text-red-700">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="edit-min_stock" class="mb-1 block text-sm font-medium">Stok Minimum</label>
                    <input id="edit-min_stock" type="number" min="0" wire:model="min_stock" class="w-full rounded border border-slate-300 px-3 py-2">
                    @error('min_stock') <p class="mt-1 text-xs text-red-700">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="edit-price" class="mb-1 block text-sm font-medium">Harga (Rp)</label>
                    <input id="edit-price" type="number" min="0" step="0.01" wire:model="price" class="w-full rounded border border-slate-300 px-3 py-2">
                    @error('price') <p class="mt-1 text-xs text-red-700">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium">Deskripsi (Markdown)</label>
                    <div wire:ignore x-data="{ editor: null }"
                         x-init="editor = new toastui.Editor({ el: $refs.editor, height: '320px', initialEditType: 'markdown', previewStyle: 'vertical', initialValue: $wire.description });
                                 editor.on('change', () => { $wire.description = editor.getMarkdown(); });">
                        <div x-ref="editor"></div>
                    </div>
                    @error('description') <p class="mt-1 text-xs text-red-700">{{ $message }}</p> @enderror
                </div>
                <div class="flex justify-end gap-2 sm:col-span-2">
                    <button type="button" wire:click="cancelEdit" class="rounded border border-slate-300 px-4 py-2 text-sm">Batal</button>
                    <button type="submit" class="rounded bg-indigo-700 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-800">Simpan</button>
                </div>
            </form>
        @endif
    </div>
</div>