<div class="mx-auto mt-16 w-full max-w-md rounded-lg bg-white p-8 shadow">
    <h1 class="mb-1 text-2xl font-bold">Masuk</h1>
    <p class="mb-6 text-sm text-slate-600">Aplikasi Inventaris Stok Barang</p>

    <form wire:submit="authenticate" class="space-y-4">
        <div>
            <label for="login-username" class="mb-1 block text-sm font-medium">Username atau Email</label>
            <input id="login-username" type="text" wire:model="login" autocomplete="username"
                   class="w-full rounded border border-slate-300 px-3 py-2 focus:border-indigo-500 focus:outline-none">
            @error('login') <p class="mt-1 text-xs text-red-700">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="login-password" class="mb-1 block text-sm font-medium">Password</label>
            <input id="login-password" type="password" wire:model="password" autocomplete="current-password"
                   class="w-full rounded border border-slate-300 px-3 py-2 focus:border-indigo-500 focus:outline-none">
            @error('password') <p class="mt-1 text-xs text-red-700">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" wire:model="remember"> Ingat saya
        </label>

        <button id="login-submit" type="submit"
                class="w-full rounded bg-indigo-700 px-4 py-2 font-semibold text-white hover:bg-indigo-800">
            Masuk
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-600">
        <!-- Perbaikan: underline permanen -->
        Belum punya akun? <a href="{{ route('register') }}" class="text-indigo-700 underline hover:text-indigo-900">Daftar</a>
    </p>
</div>