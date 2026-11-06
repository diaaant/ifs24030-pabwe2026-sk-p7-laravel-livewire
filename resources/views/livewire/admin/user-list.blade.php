<div class="space-y-4">
    <h1 class="text-2xl font-bold">Daftar User</h1>

    <input type="search" wire:model.live.debounce.300ms="search" placeholder="Cari nama / username..."
           class="w-full max-w-xs rounded border border-slate-300 px-3 py-2 text-sm">

    <div class="overflow-x-auto rounded-lg bg-white shadow">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
            <tr>
                <th class="px-3 py-3">Nama</th>
                <th class="px-3 py-3">Username</th>
                <th class="px-3 py-3">Role</th>
                <th class="px-3 py-3">Jumlah Stok</th>
                <th class="px-3 py-3 text-right">Aksi</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($users as $user)
                <tr wire:key="user-{{ $user->id }}" class="border-t">
                    <td class="px-3 py-2 font-medium">{{ $user->name }}</td>
                    <td class="px-3 py-2">{{ $user->username }}</td>
                    <td class="px-3 py-2"><span class="rounded bg-slate-100 px-2 py-1 text-xs uppercase">{{ $user->role }}</span></td>
                    <td class="px-3 py-2">{{ $user->stocks_count }}</td>
                    <td class="px-3 py-2 text-right">
                        <a href="{{ route('admin.users.stocks', $user) }}" class="text-indigo-600 hover:underline">Lihat Stok</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-3 py-8 text-center text-slate-500">User tidak ditemukan.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $users->links() }}</div>
</div>
