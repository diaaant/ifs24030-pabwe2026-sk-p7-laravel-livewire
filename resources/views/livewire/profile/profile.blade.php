<div class="mx-auto max-w-3xl space-y-6">
    <h1 class="text-2xl font-bold">Profil Saya</h1>

    @if (session('status'))
        <div class="rounded border border-green-200 bg-green-50 px-4 py-2 text-sm text-green-800">{{ session('status') }}</div>
    @endif

    <section class="rounded-lg bg-white p-6 shadow">
        <h2 class="mb-4 text-lg font-bold">Foto Profil</h2>
        <div class="flex flex-wrap items-center gap-4">
            @if ($user->profilePhotoUrl())
                <img src="{{ $user->profilePhotoUrl() }}" alt="Foto profil" class="h-20 w-20 rounded-full object-cover">
            @else
                <div class="flex h-20 w-20 items-center justify-center rounded-full bg-indigo-500 text-2xl font-semibold text-white">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
            @endif
            <form wire:submit="updatePhoto" class="space-y-2">
                <input type="file" wire:model="photo" accept="image/*" class="text-sm">
                @error('photo') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                <button type="submit" class="rounded bg-slate-800 px-3 py-1 text-sm font-semibold text-white">Upload Foto</button>
            </form>
        </div>
    </section>

    <section class="rounded-lg bg-white p-6 shadow">
        <h2 class="mb-4 text-lg font-bold">Data Akun</h2>
        <form wire:submit="updateProfile" class="space-y-4">
            <div>
                <label class="mb-1 block text-sm font-medium">Nama</label>
                <input type="text" wire:model="name" class="w-full rounded border border-slate-300 px-3 py-2">
                @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Username</label>
                <input type="text" wire:model="username" class="w-full rounded border border-slate-300 px-3 py-2">
                @error('username') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Email (opsional)</label>
                <input type="email" wire:model="email" class="w-full rounded border border-slate-300 px-3 py-2">
                @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="rounded bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Simpan Profil</button>
        </form>
    </section>

    <section class="rounded-lg bg-white p-6 shadow">
        <h2 class="mb-4 text-lg font-bold">Ubah Password</h2>
        <form wire:submit="updatePassword" class="space-y-4">
            <div>
                <label class="mb-1 block text-sm font-medium">Password Saat Ini</label>
                <input type="password" wire:model="current_password" class="w-full rounded border border-slate-300 px-3 py-2">
                @error('current_password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Password Baru</label>
                <input type="password" wire:model="password" class="w-full rounded border border-slate-300 px-3 py-2">
                @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Konfirmasi Password Baru</label>
                <input type="password" wire:model="password_confirmation" class="w-full rounded border border-slate-300 px-3 py-2">
            </div>
            <button type="submit" class="rounded bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Ubah Password</button>
        </form>
    </section>
</div>
