<x-app-layout>

    {{-- Header --}}
    <div class="flex items-center gap-3 mb-6 animate-in" style="animation-delay: 0.05s;">
        <a href="{{ route('customers.index') }}"
           class="p-2 rounded-lg transition-colors"
           style="color: var(--text-muted);"
           onmouseenter="this.style.background='var(--bg-secondary)'"
           onmouseleave="this.style.background=''">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
        </a>
        <div>
            <h1 class="text-xl font-bold" style="color: var(--text-primary);">Edit Customer</h1>
            <p class="text-sm mt-0.5" style="color: var(--text-muted);">
                <code class="text-xs px-2 py-0.5 rounded" style="background: var(--bg-secondary); color: var(--text-secondary);">
                    {{ $customer->company_code }}
                </code>
                {{ $customer->company_name }}
            </p>
        </div>
    </div>

    <form method="POST" action="{{ route('customers.update', $customer) }}" class="space-y-5">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">

            {{-- Kolom Kiri --}}
            <div class="lg:col-span-2 space-y-5">

                {{-- Card: Informasi Umum --}}
                <div class="card p-6" style="position: relative; z-index: 10;">
                    <h2 class="text-sm font-semibold mb-4 pb-3" style="color: var(--text-secondary); border-bottom: 1px solid var(--border-color);">
                        Informasi Umum
                    </h2>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

                        {{-- Tipe Customer --}}
                        <div>
                            <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">Tipe Customer</label>
                            <x-custom-select name="customer_type" :value="old('customer_type', $customer->customer_type)" placeholder="Pilih Tipe" :options="[
                                'PT'          => 'PT (Perseroan Terbatas)',
                                'CV'          => 'CV (Comanditaire Vennootschap)',
                                'Perorangan'  => 'Perorangan',
                                'Pemerintah'  => 'Pemerintah',
                            ]" />
                        </div>

                        {{-- Nama Perusahaan --}}
                        <div>
                            <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">
                                Nama Perusahaan <span style="color: var(--accent-rose);">*</span>
                            </label>
                            <input type="text" name="company_name" value="{{ old('company_name', $customer->company_name) }}"
                                style="width: 100%; padding: 0.6rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; outline: none; background: var(--bg-secondary); border: 1px solid {{ $errors->has('company_name') ? 'var(--accent-rose)' : 'var(--border-color)' }}; color: var(--text-primary);"
                                onfocus="this.style.borderColor='var(--accent-blue)'"
                                onblur="this.style.borderColor='var(--border-color)'">
                            @error('company_name')
                            <div class="mt-2 p-3 rounded-xl flex items-start" style="background: rgba(225,29,72,0.06); border: 1px solid rgba(225,29,72,0.20);">
                                <svg class="w-4 h-4 flex-shrink-0 mt-0.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--accent-rose);">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <div class="text-xs leading-relaxed text-left" style="color: var(--accent-rose);">{{ $message }}</div>
                            </div>
                            @enderror
                        </div>

                        {{-- Bidang Usaha (menggantikan Brand/Merek Dagang) --}}
                        <div>
                            <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">Bidang Usaha</label>
                            <input type="text" name="brand_name" value="{{ old('brand_name', $customer->brand_name) }}"
                                placeholder="Contoh: Distribusi Elektronik, Kontraktor"
                                style="width: 100%; padding: 0.6rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; outline: none; background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary);"
                                onfocus="this.style.borderColor='var(--accent-blue)'"
                                onblur="this.style.borderColor='var(--border-color)'">
                        </div>

                        {{-- Industry Sector (dengan autocomplete) --}}
                        <div x-data="autocomplete('industry')">
                            <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">Industry Sector</label>
                            <div class="relative">
                                <input type="text" name="industry" value="{{ old('industry', $customer->industry) }}"
                                    x-model="query" @input.debounce.300ms="fetchSuggestions" @focus="show = true" @click.outside="show = false"
                                    placeholder="Manufaktur"
                                    style="width: 100%; padding: 0.6rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; outline: none; background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary);"
                                    onfocus="this.style.borderColor='var(--accent-blue)'"
                                    onblur="this.style.borderColor='var(--border-color)'" autocomplete="off">
                                <ul x-show="show && suggestions.length > 0" class="absolute z-10 w-full rounded-lg shadow-lg max-h-48 overflow-y-auto mt-1" style="background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary);">
                                    <template x-for="(item, index) in suggestions" :key="index">
                                        <li @click="select(item)" class="px-3 py-2 cursor-pointer transition-colors" onmouseover="this.style.background='var(--bg-primary)'" onmouseout="this.style.background=''" x-text="item"></li>
                                    </template>
                                </ul>
                            </div>
                        </div>

                        {{-- [DIHAPUS] Skala Perusahaan tidak lagi dipakai --}}

                    </div>
                </div>

                {{-- Card: Lokasi & Alamat (Dependent Select) --}}
                {{-- Untuk Edit, nilai lama sudah tersedia di $customer, --}}
                {{-- kita tampilkan sebagai "nilai awal" lalu user bisa ganti --}}
                <div class="card p-6" x-data="regionSelector()" x-init="
                    province   = '{{ old('province',   $customer->province)   }}';
                    city       = '{{ old('city',       $customer->city)       }}';
                    district   = '{{ old('district',   $customer->district)   }}';
                    village    = '{{ old('village',    $customer->village)    }}';
                    postalCode = '{{ old('postal_code',$customer->postal_code) }}';
                ">
                    <h2 class="text-sm font-semibold mb-4 pb-3" style="color: var(--text-secondary); border-bottom: 1px solid var(--border-color);">
                        Lokasi &amp; Alamat
                    </h2>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

                        {{-- Step 1: Provinsi --}}
                        <div>
                            <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">Provinsi</label>
                            <select name="province" x-model="province" @change="onProvinceChange()"
                                style="width: 100%; padding: 0.6rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; outline: none; background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary); cursor: pointer;">
                                <option value="">-- Pilih Provinsi --</option>
                                @foreach(\App\Helpers\IndonesiaRegion::provinces() as $prov)
                                    <option value="{{ $prov }}" {{ old('province', $customer->province) == $prov ? 'selected' : '' }}>{{ $prov }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Step 2: Kota / Kabupaten --}}
                        <div>
                            <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">Kota / Kabupaten</label>
                            <select name="city" x-model="city" @change="onCityChange()"
                                style="width: 100%; padding: 0.6rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; outline: none; background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary); cursor: pointer;">
                                <option value="">-- Pilih Kota/Kabupaten --</option>
                                @if(old('city', $customer->city))
                                    <option value="{{ old('city', $customer->city) }}" selected>{{ old('city', $customer->city) }}</option>
                                @endif
                                <template x-for="c in cities" :key="c">
                                    <option :value="c" x-text="c" :selected="c === city"></option>
                                </template>
                            </select>
                        </div>

                        {{-- Step 3: Kecamatan --}}
                        <div>
                            <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">Kecamatan</label>
                            <select name="district" x-model="district" @change="onDistrictChange()"
                                style="width: 100%; padding: 0.6rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; outline: none; background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary); cursor: pointer;">
                                <option value="">-- Pilih Kecamatan --</option>
                                @if(old('district', $customer->district))
                                    <option value="{{ old('district', $customer->district) }}" selected>{{ old('district', $customer->district) }}</option>
                                @endif
                                <template x-for="d in districts" :key="d">
                                    <option :value="d" x-text="d" :selected="d === district"></option>
                                </template>
                            </select>
                        </div>

                        {{-- Step 4: Kelurahan --}}
                        <div>
                            <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">Kelurahan / Desa</label>
                            <select name="village" x-model="village" @change="onVillageChange()"
                                style="width: 100%; padding: 0.6rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; outline: none; background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary); cursor: pointer;">
                                <option value="">-- Pilih Kelurahan/Desa --</option>
                                @if(old('village', $customer->village))
                                    <option value="{{ old('village', $customer->village) }}" selected>{{ old('village', $customer->village) }}</option>
                                @endif
                                <template x-for="v in villages" :key="v.name">
                                    <option :value="v.name" x-text="v.name" :selected="v.name === village"></option>
                                </template>
                            </select>
                        </div>

                        {{-- Step 5: Kode Pos (auto-fill atau manual) --}}
                        <div>
                            <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">Kode Pos <span class="font-normal italic" style="color: var(--text-muted);">(auto/manual)</span></label>
                            <input type="text" name="postal_code" id="field_postal_code"
                                x-model="postalCode"
                                list="postal_code_options"
                                placeholder="Cth: 17134"
                                style="width: 100%; padding: 0.6rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; outline: none; background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary); cursor: text;">
                            <datalist id="postal_code_options">
                                <template x-for="code in postalCodeOptions" :key="code">
                                    <option :value="code"></option>
                                </template>
                            </datalist>
                        </div>

                        {{-- Step 6: Alamat Lengkap --}}
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">Alamat Lengkap <span class="font-normal" style="color: var(--text-muted);">(Nama Jalan, Gedung, No. Bangunan, RT/RW)</span></label>
                            <input type="text" name="address" value="{{ old('address', $customer->address) }}"
                                placeholder="Contoh: Jl. Delta Silicon II Blok C5 No. 12, RT 001/RW 003"
                                style="width: 100%; padding: 0.6rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; outline: none; background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary);"
                                onfocus="this.style.borderColor='var(--accent-blue)'"
                                onblur="this.style.borderColor='var(--border-color)'">
                        </div>

                        {{-- Nama Kawasan (Opsional) --}}
                        <div>
                            <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">Nama Kawasan <span class="font-normal" style="color: var(--text-muted);">(Opsional)</span></label>
                            <input type="text" name="region" value="{{ old('region', $customer->region) }}"
                                placeholder="Contoh: KIIC, Delta Silicon, Jababeka"
                                style="width: 100%; padding: 0.6rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; outline: none; background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary);"
                                onfocus="this.style.borderColor='var(--accent-blue)'"
                                onblur="this.style.borderColor='var(--border-color)'">
                        </div>

                        {{-- Telepon Perusahaan --}}
                        <div>
                            <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">Telepon Perusahaan</label>
                            <input type="text" name="phone" value="{{ old('phone', $customer->phone) }}"
                                placeholder="021-xxxxxxx"
                                style="width: 100%; padding: 0.6rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; outline: none; background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary);"
                                onfocus="this.style.borderColor='var(--accent-blue)'"
                                onblur="this.style.borderColor='var(--border-color)'">
                        </div>

                        {{-- Email Perusahaan --}}
                        <div>
                            <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">Email Perusahaan</label>
                            <input type="email" name="email" value="{{ old('email', $customer->email) }}"
                                placeholder="info@perusahaan.com"
                                style="width: 100%; padding: 0.6rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; outline: none; background: var(--bg-secondary); border: 1px solid {{ $errors->has('email') ? 'var(--accent-rose)' : 'var(--border-color)' }}; color: var(--text-primary);"
                                onfocus="this.style.borderColor='var(--accent-blue)'"
                                onblur="this.style.borderColor='var(--border-color)'">
                            @error('email')<p class="mt-1 text-xs" style="color: var(--accent-rose);">{{ $message }}</p>@enderror
                        </div>

                        {{-- Website --}}
                        <div>
                            <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">Website</label>
                            <input type="url" name="website" value="{{ old('website', $customer->website) }}"
                                placeholder="https://perusahaan.com"
                                style="width: 100%; padding: 0.6rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; outline: none; background: var(--bg-secondary); border: 1px solid {{ $errors->has('website') ? 'var(--accent-rose)' : 'var(--border-color)' }}; color: var(--text-primary);"
                                onfocus="this.style.borderColor='var(--accent-blue)'"
                                onblur="this.style.borderColor='var(--border-color)'">
                            @error('website')<p class="mt-1 text-xs" style="color: var(--accent-rose);">{{ $message }}</p>@enderror
                        </div>

                        {{-- NPWP (Hanya Admin yang bisa mengubah) --}}
                        <div>
                            <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">
                                NPWP
                                @if(!auth()->user()->isAdminOrAbove())
                                    <span class="ml-1 text-[10px] font-normal px-1.5 py-0.5 rounded-full" style="background: rgba(217,119,6,0.10); color: var(--accent-amber);">🔒 Admin Only</span>
                                @endif
                            </label>
                            <input type="text" name="npwp" value="{{ old('npwp', $customer->npwp) }}"
                                placeholder="00.000.000.0-000.000"
                                {{ !auth()->user()->isAdminOrAbove() ? 'disabled' : '' }}
                                style="width: 100%; padding: 0.6rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; outline: none; background: {{ !auth()->user()->isAdminOrAbove() ? 'var(--bg-tertiary, #f1f5f9)' : 'var(--bg-secondary)' }}; border: 1px solid var(--border-color); color: {{ !auth()->user()->isAdminOrAbove() ? 'var(--text-muted)' : 'var(--text-primary)' }}; {{ !auth()->user()->isAdminOrAbove() ? 'cursor: not-allowed;' : '' }}"
                                onfocus="{{ auth()->user()->isAdminOrAbove() ? "this.style.borderColor='var(--accent-blue)'" : '' }}"
                                onblur="{{ auth()->user()->isAdminOrAbove() ? "this.style.borderColor='var(--border-color)'" : '' }}">
                        </div>

                        {{-- NIB (Hanya Admin yang bisa mengubah) --}}
                        <div>
                            <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">
                                NIB (Nomor Induk Berusaha)
                                @if(!auth()->user()->isAdminOrAbove())
                                    <span class="ml-1 text-[10px] font-normal px-1.5 py-0.5 rounded-full" style="background: rgba(217,119,6,0.10); color: var(--accent-amber);">🔒 Admin Only</span>
                                @endif
                            </label>
                            <input type="text" name="nib" value="{{ old('nib', $customer->nib) }}"
                                placeholder="9123456789012"
                                {{ !auth()->user()->isAdminOrAbove() ? 'disabled' : '' }}
                                style="width: 100%; padding: 0.6rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; outline: none; background: {{ !auth()->user()->isAdminOrAbove() ? 'var(--bg-tertiary, #f1f5f9)' : 'var(--bg-secondary)' }}; border: 1px solid var(--border-color); color: {{ !auth()->user()->isAdminOrAbove() ? 'var(--text-muted)' : 'var(--text-primary)' }}; {{ !auth()->user()->isAdminOrAbove() ? 'cursor: not-allowed;' : '' }}"
                                onfocus="{{ auth()->user()->isAdminOrAbove() ? "this.style.borderColor='var(--accent-blue)'" : '' }}"
                                onblur="{{ auth()->user()->isAdminOrAbove() ? "this.style.borderColor='var(--border-color)'" : '' }}">
                        </div>

                        {{-- Biaya Ongkir dari Pedia (Blueprint 4.1) --}}
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">
                                Biaya Ongkir dari Pedia (Rp)
                                <span class="ml-1 text-[10px] font-normal" style="color: var(--text-muted);">(Tarif standar biaya kirim dari kantor Pedia ke customer ini untuk otomatisasi RFQ)</span>
                            </label>
                            <input type="number" name="ongkir_pedia" value="{{ old('ongkir_pedia', (float)($customer->ongkir_pedia ?? 0)) }}" min="0" step="1000"
                                placeholder="Contoh: 150000"
                                style="width: 100%; padding: 0.6rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; outline: none; background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary);"
                                onfocus="this.style.borderColor='var(--accent-blue)'"
                                onblur="this.style.borderColor='var(--border-color)'">
                            @error('ongkir_pedia')<p class="mt-1 text-xs" style="color: var(--accent-rose);">{{ $message }}</p>@enderror
                        </div>

                        {{-- Catatan Tambahan --}}
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">Catatan Tambahan (Mengenai Perusahaan)</label>
                            <textarea name="notes" rows="2"
                                placeholder="Catatan tentang perusahaan ini..."
                                style="width: 100%; padding: 0.6rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; outline: none; background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary); resize: none;"
                                onfocus="this.style.borderColor='var(--accent-blue)'"
                                onblur="this.style.borderColor='var(--border-color)'">{{ old('notes', $customer->notes) }}</textarea>
                        </div>

                    </div>
                </div>


                {{-- ─────────────────────────────────────────────────────────────────────
                     Gap #9: Card Daftar Kontak Person (Customer Contacts - 1:N)
                     Penjelasan: Setiap Customer bisa punya LEBIH dari 1 kontak.
                     Contoh: 1 orang di bagian Finance, 1 orang di Procurement.
                     Form ini memungkinkan Sales menambah/hapus baris kontak secara dinamis.
                ──────────────────────────────────────────────────────────────────────── --}}
                <div class="card p-6">
                    <div class="flex items-center justify-between mb-4 pb-3" style="border-bottom: 1px solid var(--border-color);">
                        <div>
                            <h2 class="text-sm font-semibold" style="color: var(--text-secondary);">Daftar Kontak Person</h2>
                            <p class="text-xs mt-0.5" style="color: var(--text-muted);">Tambah semua kontak yang perlu dihubungi di perusahaan ini</p>
                        </div>
                        <button type="button" onclick="addContactRow()"
                            class="text-xs font-medium px-3 py-1.5 rounded-lg transition-colors"
                            style="background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--accent-blue);">
                            + Tambah Kontak
                        </button>
                    </div>

                    <div id="contacts-container" class="space-y-4">
                        {{-- Isi dari DB: tampilkan kontak yang sudah ada --}}
                        @forelse($customer->contacts as $ci => $contact)
                        <div class="contact-row p-4 rounded-xl relative" style="background: var(--bg-secondary); border: 1px solid var(--border-color);" id="contact-row-{{ $ci }}">
                            <button type="button" onclick="removeContactRow('contact-row-{{ $ci }}')"
                                class="absolute top-3 right-3 p-1 rounded-lg transition-colors"
                                style="color: var(--accent-rose);" title="Hapus kontak ini">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                            {{-- ID tersembunyi agar Controller tahu ini UPDATE bukan INSERT baru --}}
                            <input type="hidden" name="contacts[{{ $ci }}][id]" value="{{ $contact->id }}">
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Nama <span style="color:var(--accent-rose);">*</span></label>
                                    <input type="text" name="contacts[{{ $ci }}][name]" value="{{ old("contacts.$ci.name", $contact->name) }}" required placeholder="Budi Santoso"
                                        style="width:100%;padding:0.5rem 0.75rem;border-radius:0.6rem;font-size:0.8rem;outline:none;background:var(--bg-primary);border:1px solid var(--border-color);color:var(--text-primary);"
                                        onfocus="this.style.borderColor='var(--accent-blue)'" onblur="this.style.borderColor='var(--border-color)'">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Jabatan</label>
                                    <input type="text" name="contacts[{{ $ci }}][position]" value="{{ old("contacts.$ci.position", $contact->position) }}" placeholder="Purchasing Manager"
                                        style="width:100%;padding:0.5rem 0.75rem;border-radius:0.6rem;font-size:0.8rem;outline:none;background:var(--bg-primary);border:1px solid var(--border-color);color:var(--text-primary);"
                                        onfocus="this.style.borderColor='var(--accent-blue)'" onblur="this.style.borderColor='var(--border-color)'">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">WhatsApp / HP</label>
                                    <input type="text" name="contacts[{{ $ci }}][phone]" value="{{ old("contacts.$ci.phone", $contact->phone) }}" placeholder="0812-xxxx-xxxx"
                                        style="width:100%;padding:0.5rem 0.75rem;border-radius:0.6rem;font-size:0.8rem;outline:none;background:var(--bg-primary);border:1px solid var(--border-color);color:var(--text-primary);"
                                        onfocus="this.style.borderColor='var(--accent-blue)'" onblur="this.style.borderColor='var(--border-color)'">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Email</label>
                                    <input type="email" name="contacts[{{ $ci }}][email]" value="{{ old("contacts.$ci.email", $contact->email) }}" placeholder="budi@perusahaan.com"
                                        style="width:100%;padding:0.5rem 0.75rem;border-radius:0.6rem;font-size:0.8rem;outline:none;background:var(--bg-primary);border:1px solid var(--border-color);color:var(--text-primary);"
                                        onfocus="this.style.borderColor='var(--accent-blue)'" onblur="this.style.borderColor='var(--border-color)'">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Divisi</label>
                                    <input type="text" name="contacts[{{ $ci }}][division]" value="{{ old("contacts.$ci.division", $contact->division) }}" placeholder="Finance / Procurement"
                                        style="width:100%;padding:0.5rem 0.75rem;border-radius:0.6rem;font-size:0.8rem;outline:none;background:var(--bg-primary);border:1px solid var(--border-color);color:var(--text-primary);"
                                        onfocus="this.style.borderColor='var(--accent-blue)'" onblur="this.style.borderColor='var(--border-color)'">
                                </div>
                                <div class="flex items-center gap-3 pt-5">
                                    <label class="flex items-center gap-2 cursor-pointer text-xs font-medium" style="color: var(--text-secondary);">
                                        <input type="checkbox" name="contacts[{{ $ci }}][is_primary]" value="1"
                                            {{ old("contacts.$ci.is_primary", $contact->is_primary) ? 'checked' : '' }}
                                            style="accent-color: var(--accent-blue);">
                                        Kontak Utama
                                    </label>
                                </div>
                            </div>
                        </div>
                        @empty
                        <p class="text-xs py-2" style="color: var(--text-muted);" id="contacts-empty-msg">Belum ada kontak. Klik "+ Tambah Kontak" untuk menambah.</p>
                        @endforelse
                    </div>
                </div>

                {{-- Card: Alamat Penagihan (Billing) --}}
                <div class="card p-6" x-data="addressManager('billing', {{ $customer->billingAddresses->toJson() }})">
                    <div class="flex items-center justify-between mb-4 pb-3" style="border-bottom: 1px solid var(--border-color);">
                        <h2 class="text-sm font-semibold" style="color: var(--text-secondary);">Alamat Penagihan (Billing)</h2>
                        <button type="button" @click="add()"
                            class="text-xs font-medium px-3 py-1.5 rounded-lg transition-colors"
                            style="background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--accent-blue);">
                            + Tambah Alamat
                        </button>
                    </div>
                    <template x-for="(item, index) in items" :key="index">
                        <div class="p-4 mb-3 rounded-xl" style="background: var(--bg-secondary); border: 1px solid var(--border-color);">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-semibold" style="color: var(--text-muted);" x-text="'Alamat Penagihan #' + (index + 1)"></span>
                                <button type="button" @click="remove(index)" class="text-xs" style="color: var(--accent-rose);">Hapus</button>
                            </div>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Label</label>
                                    <input type="text" x-model="item.label" :name="'billing_addresses[' + index + '][label]'"
                                        placeholder="Kantor Pusat / Cabang"
                                        style="width: 100%; padding: 0.5rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; outline: none; background: var(--bg-primary); border: 1px solid var(--border-color); color: var(--text-primary);"
                                        onfocus="this.style.borderColor='var(--accent-blue)'"
                                        onblur="this.style.borderColor='var(--border-color)'">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Kota</label>
                                    <input type="text" x-model="item.city" :name="'billing_addresses[' + index + '][city]'"
                                        placeholder="Jakarta"
                                        style="width: 100%; padding: 0.5rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; outline: none; background: var(--bg-primary); border: 1px solid var(--border-color); color: var(--text-primary);"
                                        onfocus="this.style.borderColor='var(--accent-blue)'"
                                        onblur="this.style.borderColor='var(--border-color)'">
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Alamat</label>
                                    <textarea x-model="item.address" :name="'billing_addresses[' + index + '][address]'" rows="2"
                                        placeholder="Jl. Contoh No. 123"
                                        style="width: 100%; padding: 0.5rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; outline: none; background: var(--bg-primary); border: 1px solid var(--border-color); color: var(--text-primary); resize: none;"
                                        onfocus="this.style.borderColor='var(--accent-blue)'"
                                        onblur="this.style.borderColor='var(--border-color)'"></textarea>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Provinsi</label>
                                    <input type="text" x-model="item.province" :name="'billing_addresses[' + index + '][province]'"
                                        placeholder="DKI Jakarta"
                                        style="width: 100%; padding: 0.5rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; outline: none; background: var(--bg-primary); border: 1px solid var(--border-color); color: var(--text-primary);"
                                        onfocus="this.style.borderColor='var(--accent-blue)'"
                                        onblur="this.style.borderColor='var(--border-color)'">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Kode Pos</label>
                                    <input type="text" x-model="item.postal_code" :name="'billing_addresses[' + index + '][postal_code]'"
                                        placeholder="12345"
                                        style="width: 100%; padding: 0.5rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; outline: none; background: var(--bg-primary); border: 1px solid var(--border-color); color: var(--text-primary);"
                                        onfocus="this.style.borderColor='var(--accent-blue)'"
                                        onblur="this.style.borderColor='var(--border-color)'">
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Negara</label>
                                    <input type="text" x-model="item.country" :name="'billing_addresses[' + index + '][country]'"
                                        placeholder="Indonesia"
                                        style="width: 100%; padding: 0.5rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; outline: none; background: var(--bg-primary); border: 1px solid var(--border-color); color: var(--text-primary);"
                                        onfocus="this.style.borderColor='var(--accent-blue)'"
                                        onblur="this.style.borderColor='var(--border-color)'">
                                </div>
                            </div>
                        </div>
                    </template>
                    <p x-show="items.length === 0" class="text-xs text-center py-4" style="color: var(--text-muted);">Belum ada alamat penagihan. Klik "Tambah Alamat" untuk menambahkan.</p>
                </div>

                {{-- Card: Alamat Pengiriman (Shipping) --}}
                <div class="card p-6" x-data="addressManager('shipping', {{ $customer->shippingAddresses->toJson() }})">
                    <div class="flex items-center justify-between mb-4 pb-3" style="border-bottom: 1px solid var(--border-color);">
                        <h2 class="text-sm font-semibold" style="color: var(--text-secondary);">Alamat Pengiriman (Shipping)</h2>
                        <button type="button" @click="add()"
                            class="text-xs font-medium px-3 py-1.5 rounded-lg transition-colors"
                            style="background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--accent-blue);">
                            + Tambah Alamat
                        </button>
                    </div>
                    <template x-for="(item, index) in items" :key="index">
                        <div class="p-4 mb-3 rounded-xl" style="background: var(--bg-secondary); border: 1px solid var(--border-color);">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-semibold" style="color: var(--text-muted);" x-text="'Alamat Pengiriman #' + (index + 1)"></span>
                                <button type="button" @click="remove(index)" class="text-xs" style="color: var(--accent-rose);">Hapus</button>
                            </div>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Label</label>
                                    <input type="text" x-model="item.label" :name="'shipping_addresses[' + index + '][label]'"
                                        placeholder="Gudang / Pabrik"
                                        style="width: 100%; padding: 0.5rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; outline: none; background: var(--bg-primary); border: 1px solid var(--border-color); color: var(--text-primary);"
                                        onfocus="this.style.borderColor='var(--accent-blue)'"
                                        onblur="this.style.borderColor='var(--border-color)'">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Kota</label>
                                    <input type="text" x-model="item.city" :name="'shipping_addresses[' + index + '][city]'"
                                        placeholder="Jakarta"
                                        style="width: 100%; padding: 0.5rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; outline: none; background: var(--bg-primary); border: 1px solid var(--border-color); color: var(--text-primary);"
                                        onfocus="this.style.borderColor='var(--accent-blue)'"
                                        onblur="this.style.borderColor='var(--border-color)'">
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Alamat</label>
                                    <textarea x-model="item.address" :name="'shipping_addresses[' + index + '][address]'" rows="2"
                                        placeholder="Jl. Contoh No. 123"
                                        style="width: 100%; padding: 0.5rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; outline: none; background: var(--bg-primary); border: 1px solid var(--border-color); color: var(--text-primary); resize: none;"
                                        onfocus="this.style.borderColor='var(--accent-blue)'"
                                        onblur="this.style.borderColor='var(--border-color)'"></textarea>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Provinsi</label>
                                    <input type="text" x-model="item.province" :name="'shipping_addresses[' + index + '][province]'"
                                        placeholder="DKI Jakarta"
                                        style="width: 100%; padding: 0.5rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; outline: none; background: var(--bg-primary); border: 1px solid var(--border-color); color: var(--text-primary);"
                                        onfocus="this.style.borderColor='var(--accent-blue)'"
                                        onblur="this.style.borderColor='var(--border-color)'">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Kode Pos</label>
                                    <input type="text" x-model="item.postal_code" :name="'shipping_addresses[' + index + '][postal_code]'"
                                        placeholder="12345"
                                        style="width: 100%; padding: 0.5rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; outline: none; background: var(--bg-primary); border: 1px solid var(--border-color); color: var(--text-primary);"
                                        onfocus="this.style.borderColor='var(--accent-blue)'"
                                        onblur="this.style.borderColor='var(--border-color)'">
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Negara</label>
                                    <input type="text" x-model="item.country" :name="'shipping_addresses[' + index + '][country]'"
                                        placeholder="Indonesia"
                                        style="width: 100%; padding: 0.5rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; outline: none; background: var(--bg-primary); border: 1px solid var(--border-color); color: var(--text-primary);"
                                        onfocus="this.style.borderColor='var(--accent-blue)'"
                                        onblur="this.style.borderColor='var(--border-color)'">
                                </div>
                            </div>
                        </div>
                    </template>
                    <p x-show="items.length === 0" class="text-xs text-center py-4" style="color: var(--text-muted);">Belum ada alamat pengiriman. Klik "Tambah Alamat" untuk menambahkan.</p>
                </div>

            </div>

            {{-- Kolom Kanan --}}
            <div class="space-y-5">
                <div class="card p-6 relative z-20">
                    <h2 class="text-sm font-semibold mb-4 pb-3" style="color: var(--text-secondary); border-bottom: 1px solid var(--border-color);">
                        Status &amp; Assignment
                    </h2>
                    <div class="space-y-4">
                        @if(auth()->user()->isAdminOrAbove())
                        <div>
                            <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">
                                Status <span style="color: var(--accent-rose);">*</span>
                            </label>
                            <x-custom-select name="status" :value="old('status', $customer->status)" placeholder="Pilih Status" :options="[
                                'Prospect' => 'Prospect',
                                'Active' => 'Active (Aktif)',
                                'Inactive' => 'Inactive',
                                'Blacklist' => 'Blacklist',
                            ]" />
                        </div>
                        @else
                        <div class="p-3 rounded-xl" style="background: rgba(37,99,235,0.06); border: 1px solid rgba(37,99,235,0.15);">
                            <p class="text-xs font-medium mb-1" style="color: var(--accent-blue);">Status Saat Ini:</p>
                            @php
                                $badgeClass = match($customer->status) {
                                    'Active' => 'status-active',
                                    'Prospect' => 'status-pending',
                                    'Inactive' => 'status-inactive',
                                    'Blacklist' => 'status-rejected',
                                    default => 'status-inactive'
                                };
                            @endphp
                            <span class="status-badge {{ $badgeClass }} text-xs">
                                <span class="w-1.5 h-1.5 rounded-full inline-block" style="background: currentColor;"></span>
                                {{ $customer->status }}
                            </span>
                        </div>
                        <input type="hidden" name="status" value="{{ $customer->status }}">
                        @endif

                        @if(auth()->user()->isAdminOrAbove())
                        <div>
                            <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">Assign ke Sales Marketing</label>
                            <x-custom-select name="sales_id" :value="old('sales_id', $customer->sales_id)" placeholder="Belum diassign" :options="collect(['' => 'Belum diassign'])->merge($salesList->pluck('name', 'id')->toArray())" />
                        </div>
                        @endif

                        {{-- Info timestamps --}}
                        <div class="p-3 rounded-xl text-xs space-y-1" style="background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-muted);">
                            <p>Dibuat: {{ $customer->created_at->format('d M Y') }}</p>
                            <p>Diupdate: {{ $customer->updated_at->format('d M Y, H:i') }}</p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col gap-2 animate-in" style="animation-delay: 0.25s;">
                    <button type="submit"
                        class="w-full flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-semibold text-white rounded-xl transition-all active:scale-95 hover:-translate-y-0.5"
                        style="background: linear-gradient(135deg, var(--accent-blue), #1d4ed8); box-shadow: 0 4px 14px rgba(37,99,235,0.35);">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Simpan Perubahan
                    </button>
                    <a href="{{ route('customers.index') }}"
                       class="w-full flex items-center justify-center px-4 py-2.5 text-sm font-medium rounded-xl transition-colors"
                       style="background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-secondary);">
                        Batal
                    </a>
                </div>
            </div>
        </div>
    </form>

</x-app-layout>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('autocomplete', (field) => ({
            query: '',
            suggestions: [],
            show: false,
            init() {
                let input = this.$el.querySelector('input');
                if (input && input.value) {
                    this.query = input.value;
                }
            },
            fetchSuggestions() {
                if (this.query.length < 1) {
                    this.suggestions = [];
                    return;
                }
                
                fetch(`/api/customers/suggestions?field=${field}&q=${encodeURIComponent(this.query)}`)
                    .then(res => res.json())
                    .then(data => {
                        this.suggestions = data;
                        this.show = true;
                    })
                    .catch(err => console.error(err));
            },
            select(item) {
                this.query = item;
                this.show = false;
            }
        }));

        Alpine.data('addressManager', (type, existing) => ({
            items: existing.length ? existing : [],
            add() {
                this.items.push({});
            },
            remove(index) {
                this.items.splice(index, 1);
            }
        }));
    });
</script>

<script>
    /**
     * Gap #9 — Manajemen Kontak Person Dinamis (Vanilla JS)
     *
     * Cara kerja (Bahasa Bayi):
     * Bayangkan form kontak ini seperti lembar kertas kosong.
     * - "Tambah Kontak" = ambil lembar kertas baru dan tempel di bawah
     * - "X" (hapus) = sobek dan buang lembar kertas itu
     *
     * Kita pakai penghitung (counter) supaya nama setiap field
     * unik: contacts[0][name], contacts[1][name], dst.
     * Server akan menerima array ini dan memprosesnya satu-satu.
     */
    let contactCounter = {{ $customer->contacts->count() }};

    function addContactRow() {
        // Sembunyikan pesan "Belum ada kontak" jika masih tampil
        const emptyMsg = document.getElementById('contacts-empty-msg');
        if (emptyMsg) emptyMsg.style.display = 'none';

        const idx = contactCounter++;
        const rowId = 'contact-row-new-' + idx;
        const inputStyle = "width:100%;padding:0.5rem 0.75rem;border-radius:0.6rem;font-size:0.8rem;outline:none;background:var(--bg-primary);border:1px solid var(--border-color);color:var(--text-primary);";
        const focusBlur = `onfocus="this.style.borderColor='var(--accent-blue)'" onblur="this.style.borderColor='var(--border-color)'"`;

        const html = `
        <div class="contact-row p-4 rounded-xl relative" style="background: var(--bg-secondary); border: 1px solid var(--border-color);" id="${rowId}">
            <button type="button" onclick="removeContactRow('${rowId}')"
                class="absolute top-3 right-3 p-1 rounded-lg" style="color: var(--accent-rose);" title="Hapus kontak ini">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Nama <span style="color:var(--accent-rose);">*</span></label>
                    <input type="text" name="contacts[${idx}][name]" required placeholder="Nama Kontak" style="${inputStyle}" ${focusBlur}>
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Jabatan</label>
                    <input type="text" name="contacts[${idx}][position]" placeholder="Purchasing Manager" style="${inputStyle}" ${focusBlur}>
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">WhatsApp / HP</label>
                    <input type="text" name="contacts[${idx}][phone]" placeholder="0812-xxxx-xxxx" style="${inputStyle}" ${focusBlur}>
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Email</label>
                    <input type="email" name="contacts[${idx}][email]" placeholder="budi@perusahaan.com" style="${inputStyle}" ${focusBlur}>
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Divisi</label>
                    <input type="text" name="contacts[${idx}][division]" placeholder="Finance / Procurement" style="${inputStyle}" ${focusBlur}>
                </div>
                <div class="flex items-center gap-3 pt-5">
                    <label class="flex items-center gap-2 cursor-pointer text-xs font-medium" style="color: var(--text-secondary);">
                        <input type="checkbox" name="contacts[${idx}][is_primary]" value="1" style="accent-color: var(--accent-blue);">
                        Kontak Utama
                    </label>
                </div>
            </div>
        </div>`;

        document.getElementById('contacts-container').insertAdjacentHTML('beforeend', html);
    }

    function removeContactRow(rowId) {
        const row = document.getElementById(rowId);
        if (row) row.remove();
    }
</script>
