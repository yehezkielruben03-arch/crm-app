<x-app-layout>

    <div class="mb-6 animate-in" style="animation-delay: 0.05s;">
        <h1 class="text-xl font-bold" style="color: var(--text-primary);">Tambah Karyawan (User)</h1>
        <p class="text-sm mt-0.5" style="color: var(--text-muted);">Buat akun akses aplikasi untuk staf atau admin baru.</p>
    </div>

    <div class="card max-w-3xl">
        <form action="{{ route('users.store') }}" method="POST" class="p-6 sm:p-8">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                {{-- Nama Lengkap --}}
                <div>
                    <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">
                        Nama Lengkap <span style="color: var(--accent-rose);">*</span>
                    </label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                        style="width: 100%; padding: 0.6rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; outline: none; background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary);"
                        onfocus="this.style.borderColor='var(--accent-blue)'"
                        onblur="this.style.borderColor='var(--border-color)'">
                    @error('name') <p class="mt-1 text-xs" style="color: var(--accent-rose);">{{ $message }}</p> @enderror
                </div>

                {{-- Username --}}
                <div>
                    <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">
                        Username <span style="color: var(--accent-rose);">*</span>
                    </label>
                    <input type="text" name="username" value="{{ old('username') }}" required
                        style="width: 100%; padding: 0.6rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; outline: none; background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary);"
                        onfocus="this.style.borderColor='var(--accent-blue)'"
                        onblur="this.style.borderColor='var(--border-color)'">
                    @error('username') <p class="mt-1 text-xs" style="color: var(--accent-rose);">{{ $message }}</p> @enderror
                </div>

                {{-- Email --}}
                <div>
                    <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">
                        Email <span style="color: var(--accent-rose);">*</span>
                    </label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                        style="width: 100%; padding: 0.6rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; outline: none; background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary);"
                        onfocus="this.style.borderColor='var(--accent-blue)'"
                        onblur="this.style.borderColor='var(--border-color)'">
                    @error('email') <p class="mt-1 text-xs" style="color: var(--accent-rose);">{{ $message }}</p> @enderror
                </div>

                {{-- Password --}}
                <div>
                    <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">
                        Password <span style="color: var(--accent-rose);">*</span>
                    </label>
                    <input type="password" name="password" required minlength="8"
                        style="width: 100%; padding: 0.6rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; outline: none; background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary);"
                        onfocus="this.style.borderColor='var(--accent-blue)'"
                        onblur="this.style.borderColor='var(--border-color)'">
                    @error('password') <p class="mt-1 text-xs" style="color: var(--accent-rose);">{{ $message }}</p> @enderror
                </div>

                {{-- Role --}}
                <div>
                    <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">
                        Role <span style="color: var(--accent-rose);">*</span>
                    </label>
                    @php
                    $roleOptions = ['Sales Marketing' => 'Sales Marketing', 'Admin Purchase' => 'Admin Purchase', 'Leader' => 'Leader'];
                    if(auth()->user()->isSuperAdmin()) $roleOptions['Super Admin'] = 'Super Admin';
                    @endphp
                    <x-custom-select name="role" :value="old('role', 'Sales Marketing')" placeholder="Pilih Role" :options="$roleOptions" />
                    @error('role') <p class="mt-1 text-xs" style="color: var(--accent-rose);">{{ $message }}</p> @enderror
                </div>

                {{-- Status --}}
                <div>
                    <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">
                        Status <span style="color: var(--accent-rose);">*</span>
                    </label>
                    <x-custom-select name="status" :value="old('status', 'Active')" placeholder="Pilih Status" :options="[
                        'Active' => 'Active',
                        'Inactive' => 'Inactive / Resign',
                    ]" />
                    @error('status') <p class="mt-1 text-xs" style="color: var(--accent-rose);">{{ $message }}</p> @enderror
                </div>

                {{-- Phone --}}
                <div>
                    <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">No. HP (Opsional)</label>
                    <input type="text" name="phone" value="{{ old('phone') }}"
                        placeholder="08xxxxxxxxxx"
                        style="width: 100%; padding: 0.6rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; outline: none; background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary);"
                        onfocus="this.style.borderColor='var(--accent-blue)'"
                        onblur="this.style.borderColor='var(--border-color)'">
                    @error('phone') <p class="mt-1 text-xs" style="color: var(--accent-rose);">{{ $message }}</p> @enderror
                </div>

                {{-- Monthly Target --}}
                <div>
                    <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">
                        Target Bulanan (Rp)
                            <span class="font-normal ml-1" style="color: var(--text-muted); font-size: 0.7rem;">(Hanya untuk Sales Marketing)</span>
                    </label>
                    <input type="number" name="monthly_target" value="{{ old('monthly_target') }}"
                        placeholder="Contoh: 100000000"
                        style="width: 100%; padding: 0.6rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; outline: none; background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary);"
                        onfocus="this.style.borderColor='var(--accent-blue)'"
                        onblur="this.style.borderColor='var(--border-color)'">
                    @error('monthly_target') <p class="mt-1 text-xs" style="color: var(--accent-rose);">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-8 flex items-center justify-end gap-3 pt-6" style="border-top: 1px solid var(--border-color);">
                <a href="{{ route('users.index') }}"
                   class="px-5 py-2 text-sm font-medium rounded-xl transition-colors"
                   style="color: var(--text-secondary); background: var(--bg-secondary); border: 1px solid var(--border-color);">
                    Batal
                </a>
                <button type="submit"
                    class="px-5 py-2 text-sm font-medium text-white rounded-xl transition-all hover:-translate-y-0.5"
                    style="background: linear-gradient(135deg, var(--accent-blue), #1d4ed8); box-shadow: 0 4px 12px rgba(37,99,235,0.30);">
                    Simpan User Baru
                </button>
            </div>
        </form>
    </div>

</x-app-layout>
