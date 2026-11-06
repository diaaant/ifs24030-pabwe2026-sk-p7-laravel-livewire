<div class="space-y-4">
    <div>
        <a href="{{ route('admin.users') }}" class="text-sm text-indigo-600 hover:underline">&larr; Kembali ke daftar user</a>
        <h1 class="mt-1 text-2xl font-bold">Stok milik {{ $user->name }} <span class="text-base font-normal text-slate-500">(&#64;{{ $user->username }})</span></h1>
        <p class="text-sm text-slate-500">Mode baca saja (read-only).</p>
    </div>

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
            </tr>
            </thead>
            <tbody>
            @forelse ($stocks as $stock)
                <tr wire:key="stock-{{ $stock->id }}" class="border-t">
                    <td class="px-3 py-2 font-medium">{{ $stock->name }}</td>
                    <td class="px-3 py-2">{{ $stock->sku ?? '-' }}</td>
                    <td class="px-3 py-2">{{ $stock->quantity }} {{ $stock->unit }}</td>
                    <td class="px-3 py-2">{{ $stock->price !== null ? 'Rp '.number_format((float) $stock->price, 0, ',', '.') : '-' }}</td>
                    <td class="px-3 py-2">
                        <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $stock->status->badgeClass() }}">{{ $stock->status->label() }}</span>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-3 py-8 text-center text-slate-500">Belum ada stok.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $stocks->links() }}</div>
</div>
