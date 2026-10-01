<x-app-layout>

    <div class="mb-6 animate-in" style="animation-delay: 0.05s;">
        <h1 class="text-xl font-bold" style="color: var(--text-primary);">Edit Karyawan: {{ $user->name }}</h1>
        <p class="text-sm mt-0.5" style="color: var(--text-muted);">Ubah profil, hak akses, atau nonaktifkan akun.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
        {{-- Form Edit Utama --}}
        <div class="lg:col-span-2 card">
            <form action="{{ route('users.update', $user) }}" method="POST" enctype="multipart/form-data" class="p-6 sm:p-8">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    {{-- Nama Lengkap --}}
                    <div>
                        <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">
                            Nama Lengkap <span style="color: var(--accent-rose);">*</span>
                        </label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" required
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
                        <input type="text" name="username" value="{{ old('username', $user->username) }}" required
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
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                            style="width: 100%; padding: 0.6rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; outline: none; background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary);"
                            onfocus="this.style.borderColor='var(--accent-blue)'"
                            onblur="this.style.borderColor='var(--border-color)'">
                        @error('email') <p class="mt-1 text-xs" style="color: var(--accent-rose);">{{ $message }}</p> @enderror
                    </div>

                    {{-- Password --}}
                    <div>
                        <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">
                            Password <span class="font-normal ml-1" style="color: var(--text-muted); font-size: 0.7rem;">(Kosongkan jika tak diubah)</span>
                        </label>
                        <input type="password" name="password" minlength="8"
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
                        if(auth()->user()->isSuperAdmin() || $user->isSuperAdmin()) $roleOptions['Super Admin'] = 'Super Admin';
                        @endphp
                        <x-custom-select name="role" :value="old('role', $user->role)" placeholder="Pilih Role" :options="$roleOptions" />
                        @error('role') <p class="mt-1 text-xs" style="color: var(--accent-rose);">{{ $message }}</p> @enderror
                    </div>

                    {{-- Jabatan Resmi / Job Title --}}
                    <div>
                        <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">
                            Jabatan Resmi (Title di Dokumen Penawaran)
                        </label>
                        <input type="text" name="job_title" value="{{ old('job_title', $user->job_title) }}"
                            placeholder="Contoh: Account Manager, Sales Engineer"
                            style="width: 100%; padding: 0.6rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; outline: none; background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary);"
                            onfocus="this.style.borderColor='var(--accent-blue)'"
                            onblur="this.style.borderColor='var(--border-color)'">
                        @error('job_title') <p class="mt-1 text-xs" style="color: var(--accent-rose);">{{ $message }}</p> @enderror
                    </div>

                    {{-- Status --}}
                    <div>
                        <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">
                            Status <span style="color: var(--accent-rose);">*</span>
                        </label>
                        <x-custom-select name="status" :value="old('status', $user->status)" placeholder="Pilih Status" :options="[
                            'Active' => 'Active',
                            'Inactive' => 'Inactive / Resign',
                        ]" />
                        @error('status') <p class="mt-1 text-xs" style="color: var(--accent-rose);">{{ $message }}</p> @enderror
                    </div>

                    {{-- Phone --}}
                    <div>
                        <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">No. HP (Opsional)</label>
                        <input type="text" name="phone" value="{{ old('phone', $user->phone) }}"
                            style="width: 100%; padding: 0.6rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; outline: none; background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary);"
                            onfocus="this.style.borderColor='var(--accent-blue)'"
                            onblur="this.style.borderColor='var(--border-color)'">
                        @error('phone') <p class="mt-1 text-xs" style="color: var(--accent-rose);">{{ $message }}</p> @enderror
                    </div>

                    {{-- Monthly Target --}}
                    <div>
                        <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">
                            Target Bulanan (Rp) <span class="font-normal ml-1" style="color: var(--text-muted); font-size: 0.7rem;">(Hanya untuk Sales Marketing)</span>
                        </label>
                        <input type="number" name="monthly_target" value="{{ old('monthly_target', $user->monthly_target ? intval($user->monthly_target) : '') }}" placeholder="Contoh: 100000000"
                            style="width: 100%; padding: 0.6rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; outline: none; background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary);"
                            onfocus="this.style.borderColor='var(--accent-blue)'"
                            onblur="this.style.borderColor='var(--border-color)'">
                        @error('monthly_target') <p class="mt-1 text-xs" style="color: var(--accent-rose);">{{ $message }}</p> @enderror
                    </div>

                    {{-- Tanda Tangan Digital --}}
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">
                            Tanda Tangan Digital (PNG/JPG transparan, Maks 2MB)
                        </label>
                        @if($user->signature_url)
                        <div class="flex items-center gap-4 mb-3 p-3 rounded-xl border border-dashed" style="background: var(--bg-secondary); border-color: var(--border-color);">
                            <div class="p-2 rounded-lg bg-white border border-slate-200">
                                <img src="{{ $user->signature_url }}" alt="Signature {{ $user->name }}" style="max-height: 50px; max-width: 140px; object-fit: contain;">
                            </div>
                            <div class="flex-1">
                                <p class="text-xs font-medium" style="color: var(--text-primary);">Tanda tangan saat ini aktif.</p>
                                <label class="inline-flex items-center gap-2 mt-1 cursor-pointer">
                                    <input type="checkbox" name="remove_signature" value="1" class="rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                                    <span class="text-xs text-rose-600 font-medium">Hapus tanda tangan ini</span>
                                </label>
                            </div>
                        </div>
                        @endif
                        <input type="file" name="signature_file" accept="image/png,image/jpeg,image/jpg"
                            style="width: 100%; padding: 0.5rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; outline: none; background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary);">
                        <p class="text-xs mt-1" style="color: var(--text-muted);">Pilih file baru jika ingin mengganti tanda tangan yang ada.</p>
                        @error('signature_file') <p class="mt-1 text-xs" style="color: var(--accent-rose);">{{ $message }}</p> @enderror
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
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>

        {{-- Panel Migrasi Data (Hanya muncul jika user adalah Sales dan punya customer) --}}
        @if($user->isSales())
        @php $customerCount = $user->customers()->count(); @endphp
        
        <div class="lg:col-span-1 rounded-2xl" style="background: var(--bg-primary); border: 1px solid var(--accent-rose); box-shadow: 0 4px 15px rgba(225, 29, 72, 0.05);">
            <div class="p-6 border-b" style="background: rgba(225,29,72,0.05); border-color: rgba(225,29,72,0.1);">
                <div class="flex items-center gap-3 font-semibold" style="color: var(--accent-rose);">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                    Migrasi Database Klien
                </div>
                <p class="text-sm mt-2 leading-relaxed" style="color: #9f1239;">
                    Fitur ini digunakan apabila <b>{{ $user->name }}</b> resign atau dinonaktifkan. Anda bisa memindahkan seluruh klien miliknya ke Sales lain.
                </p>
            </div>
            
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <span class="text-sm font-medium" style="color: var(--text-secondary);">Total Klien saat ini:</span>
                    <span class="px-3 py-1 font-bold rounded-lg text-sm" style="background: var(--bg-secondary); color: var(--text-primary);">{{ $customerCount }}</span>
                </div>

                @if($customerCount > 0)
                <form action="{{ route('users.migrate-customers', $user) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin memindahkan semua data klien ini? Proses ini tidak bisa dibatalkan.')">
                    @csrf
                    <label class="block text-sm font-semibold mb-2" style="color: var(--text-primary);">Pilih Sales Pengganti:</label>
                    <x-custom-select name="new_sales_id" placeholder="Pilih Sales" min-width="100%" :options="collect(['' => 'Pilih Sales'])->union($activeSales->mapWithKeys(fn($s) => [$s->id => $s->name . ' (' . $s->customers()->count() . ' Klien)']))" />
                    
                    <button type="submit"
                        class="w-full flex items-center justify-center gap-2 py-2.5 mt-4 text-sm font-medium text-white rounded-xl transition-all hover:-translate-y-0.5"
                        style="background: linear-gradient(135deg, var(--accent-rose), #be123c); box-shadow: 0 4px 12px rgba(225,29,72,0.30);">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                        Migrasikan Data
                    </button>
                </form>
                @else
                <div class="text-center py-6 text-sm rounded-xl" style="color: var(--text-muted); background: var(--bg-secondary); border: 1px dashed var(--border-color);">
                    Tidak ada klien yang bisa dimigrasikan.
                </div>
                @endif
            </div>
        </div>
        @endif
    </div>
</x-app-layout>
