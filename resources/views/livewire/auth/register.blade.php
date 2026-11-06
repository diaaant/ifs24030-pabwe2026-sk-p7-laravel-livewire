<div class="mx-auto mt-12 w-full max-w-md rounded-lg bg-white p-8 shadow">
    <h1 class="mb-6 text-2xl font-bold">Daftar Akun</h1>

    <form wire:submit="register" class="space-y-4">
        <div>
            <label for="reg-name" class="mb-1 block text-sm font-medium">Nama</label>
            <input id="reg-name" type="text" wire:model="name" class="w-full rounded border border-slate-300 px-3 py-2">
            @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="reg-username" class="mb-1 block text-sm font-medium">Username</label>
            <input id="reg-username" type="text" wire:model="username" class="w-full rounded border border-slate-300 px-3 py-2">
            @error('username') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="reg-email" class="mb-1 block text-sm font-medium">Email (opsional)</label>
            <input id="reg-email" type="email" wire:model="email" class="w-full rounded border border-slate-300 px-3 py-2">
            @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="reg-password" class="mb-1 block text-sm font-medium">Password</label>
            <input id="reg-password" type="password" wire:model="password" class="w-full rounded border border-slate-300 px-3 py-2">
            @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="reg-password-confirmation" class="mb-1 block text-sm font-medium">Konfirmasi Password</label>
            <input id="reg-password-confirmation" type="password" wire:model="password_confirmation" class="w-full rounded border border-slate-300 px-3 py-2">
        </div>

        <button type="submit" class="w-full rounded bg-indigo-600 px-4 py-2 font-semibold text-white hover:bg-indigo-700">Daftar</button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-500">
        Sudah punya akun? <a href="{{ route('login') }}" class="text-indigo-600 hover:underline">Masuk</a>
    </p>
</div>
