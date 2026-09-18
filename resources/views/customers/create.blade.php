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
            <h1 class="text-xl font-bold" style="color: var(--text-primary);">Tambah Customer Baru</h1>
            <p class="text-sm mt-0.5" style="color: var(--text-muted);">Isi data lengkap perusahaan klien</p>
        </div>
    </div>

    <form method="POST" action="{{ route('customers.store') }}" class="space-y-5">
        @csrf

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">

            {{-- Kolom Kiri: Informasi Utama --}}
            <div class="lg:col-span-2 space-y-5">

                {{-- Card: Informasi Umum --}}
                <div class="card p-6 animate-in" style="animation-delay: 0.1s; position: relative; z-index: 10;">
                    <h2 class="text-sm font-semibold mb-4 pb-3" style="color: var(--text-secondary); border-bottom: 1px solid var(--border-color);">
                        Informasi Umum
                    </h2>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

                        {{-- Tipe Customer: PT / CV / Perorangan / Pemerintah (default PT) --}}
                        <div>
                            <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">Tipe Customer</label>
                            <x-custom-select name="customer_type" :value="old('customer_type', 'PT')" placeholder="Pilih Tipe" :options="[
                                'PT'          => 'PT (Perseroan Terbatas)',
                                'CV'          => 'CV (Comanditaire Vennootschap)',
                                'Perorangan'  => 'Perorangan',
                                'Pemerintah'  => 'Pemerintah',
                            ]" />
                        </div>

                        {{-- Nama Perusahaan (tanpa prefix PT/CV, karena sudah dipilih di Tipe) --}}
                        <div>
                            <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">
                                Nama Perusahaan <span style="color: var(--accent-rose);">*</span>
                            </label>
                            <input type="text" name="company_name" value="{{ old('company_name') }}"
                                placeholder="Contoh: Karya Bangsa"
                                class="form-input {{ $errors->has('company_name') ? 'error' : '' }}"
                                style="width: 100%; padding: 0.6rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; outline: none; background: var(--bg-secondary); border: 1px solid {{ $errors->has('company_name') ? 'var(--accent-rose)' : 'var(--border-color)' }}; color: var(--text-primary); transition: border-color 0.15s;"
                                onfocus="this.style.borderColor='var(--accent-blue)'; this.style.boxShadow='0 0 0 3px rgba(37,99,235,0.10)'"
                                onblur="this.style.borderColor='{{ $errors->has('company_name') ? 'var(--accent-rose)' : 'var(--border-color)' }}'; this.style.boxShadow='none'">
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
                            <input type="text" name="brand_name" value="{{ old('brand_name') }}"
                                placeholder="Contoh: Distribusi Elektronik, Kontraktor"
                                style="width: 100%; padding: 0.6rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; outline: none; background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary);"
                                onfocus="this.style.borderColor='var(--accent-blue)'"
                                onblur="this.style.borderColor='var(--border-color)'">
                        </div>

                        {{-- Industry Sector (dengan autocomplete) --}}
                        <div x-data="autocomplete('industry')">
                            <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">Industry Sector</label>
                            <div class="relative">
                                <input type="text" name="industry" value="{{ old('industry') }}"
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

                        {{-- [DIHAPUS] Skala Perusahaan (Enterprise/Medium/SME) tidak lagi diperlukan --}}

                    </div>
                </div>

                {{-- Card: Lokasi & Alamat (Dependent Select) --}}
                <div class="card p-6 animate-in" style="animation-delay: 0.15s;" x-data="regionSelector()">
                    <h2 class="text-sm font-semibold mb-4 pb-3" style="color: var(--text-secondary); border-bottom: 1px solid var(--border-color);">
                        Lokasi &amp; Alamat
                    </h2>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

                        {{-- Step 1: Provinsi --}}
                        <div>
                            <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">Provinsi</label>
                            <select name="province" id="sel_province" x-model="province" @change="onProvinceChange()"
                                style="width: 100%; padding: 0.6rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; outline: none; background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary); cursor: pointer;">
                                <option value="">-- Pilih Provinsi --</option>
                                @foreach(\App\Helpers\IndonesiaRegion::provinces() as $prov)
                                    <option value="{{ $prov }}" {{ old('province') == $prov ? 'selected' : '' }}>{{ $prov }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Step 2: Kota / Kabupaten (diisi setelah Provinsi dipilih) --}}
                        <div>
                            <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">Kota / Kabupaten</label>
                            <select name="city" id="sel_city" x-model="city" @change="onCityChange()"
                                :disabled="cities.length === 0"
                                style="width: 100%; padding: 0.6rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; outline: none; background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary); cursor: pointer;">
                                <option value="">-- Pilih Kota/Kabupaten --</option>
                                <template x-for="c in cities" :key="c"><option :value="c" x-text="c"></option></template>
                            </select>
                        </div>

                        {{-- Step 3: Kecamatan (diisi setelah Kota dipilih) --}}
                        <div>
                            <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">Kecamatan</label>
                            <select name="district" id="sel_district" x-model="district" @change="onDistrictChange()"
                                :disabled="districts.length === 0"
                                style="width: 100%; padding: 0.6rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; outline: none; background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary); cursor: pointer;">
                                <option value="">-- Pilih Kecamatan --</option>
                                <template x-for="d in districts" :key="d"><option :value="d" x-text="d"></option></template>
                            </select>
                        </div>

                        {{-- Step 4: Kelurahan (diisi setelah Kecamatan dipilih) --}}
                        <div>
                            <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">Kelurahan / Desa</label>
                            <select name="village" id="sel_village" x-model="village" @change="onVillageChange()"
                                :disabled="villages.length === 0"
                                style="width: 100%; padding: 0.6rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; outline: none; background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary); cursor: pointer;">
                                <option value="">-- Pilih Kelurahan/Desa --</option>
                                <template x-for="v in villages" :key="v.name"><option :value="v.name" x-text="v.name"></option></template>
                            </select>
                        </div>

                        {{-- Step 5: Kode Pos (bisa auto-fill atau manual) --}}
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

                        {{-- Step 6: Alamat Lengkap (nama jalan, gedung, no.) --}}
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">Alamat Lengkap <span class="font-normal" style="color: var(--text-muted);">(Nama Jalan, Gedung, No. Bangunan, RT/RW)</span></label>
                            <input type="text" name="address" value="{{ old('address') }}"
                                placeholder="Contoh: Jl. Delta Silicon II Blok C5 No. 12, RT 001/RW 003"
                                style="width: 100%; padding: 0.6rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; outline: none; background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary);"
                                onfocus="this.style.borderColor='var(--accent-blue)'"
                                onblur="this.style.borderColor='var(--border-color)'">
                        </div>

                        {{-- Nama Kawasan (Opsional) --}}
                        <div>
                            <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">Nama Kawasan <span class="font-normal" style="color: var(--text-muted);">(Opsional)</span></label>
                            <input type="text" name="region" value="{{ old('region') }}"
                                placeholder="Contoh: KIIC, Delta Silicon, Jababeka"
                                style="width: 100%; padding: 0.6rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; outline: none; background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary);"
                                onfocus="this.style.borderColor='var(--accent-blue)'"
                                onblur="this.style.borderColor='var(--border-color)'">
                        </div>

                        {{-- Telepon Perusahaan --}}
                        <div>
                            <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">Telepon Perusahaan</label>
                            <input type="text" name="phone" value="{{ old('phone') }}"
                                placeholder="021-xxxxxxx"
                                style="width: 100%; padding: 0.6rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; outline: none; background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary);"
                                onfocus="this.style.borderColor='var(--accent-blue)'"
                                onblur="this.style.borderColor='var(--border-color)'">
                        </div>

                        {{-- Email Perusahaan --}}
                        <div>
                            <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">Email Perusahaan</label>
                            <input type="email" name="email" value="{{ old('email') }}"
                                placeholder="info@perusahaan.com"
                                style="width: 100%; padding: 0.6rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; outline: none; background: var(--bg-secondary); border: 1px solid {{ $errors->has('email') ? 'var(--accent-rose)' : 'var(--border-color)' }}; color: var(--text-primary);"
                                onfocus="this.style.borderColor='var(--accent-blue)'"
                                onblur="this.style.borderColor='var(--border-color)'">
                            @error('email')<p class="mt-1 text-xs" style="color: var(--accent-rose);">{{ $message }}</p>@enderror
                        </div>

                        {{-- Website --}}
                        <div>
                            <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">Website</label>
                            <input type="url" name="website" value="{{ old('website') }}"
                                placeholder="https://perusahaan.com"
                                style="width: 100%; padding: 0.6rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; outline: none; background: var(--bg-secondary); border: 1px solid {{ $errors->has('website') ? 'var(--accent-rose)' : 'var(--border-color)' }}; color: var(--text-primary);"
                                onfocus="this.style.borderColor='var(--accent-blue)'"
                                onblur="this.style.borderColor='var(--border-color)'">
                            @error('website')<p class="mt-1 text-xs" style="color: var(--accent-rose);">{{ $message }}</p>@enderror
                        </div>

                        {{-- NPWP (Hanya Admin yang bisa mengisi) --}}
                        <div>
                            <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">
                                NPWP
                                @if(!auth()->user()->isAdminOrAbove())
                                    <span class="ml-1 text-[10px] font-normal px-1.5 py-0.5 rounded-full" style="background: rgba(217,119,6,0.10); color: var(--accent-amber);">🔒 Admin Only</span>
                                @endif
                            </label>
                            <input type="text" name="npwp" value="{{ old('npwp') }}"
                                placeholder="00.000.000.0-000.000"
                                {{ !auth()->user()->isAdminOrAbove() ? 'disabled' : '' }}
                                style="width: 100%; padding: 0.6rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; outline: none; background: {{ !auth()->user()->isAdminOrAbove() ? 'var(--bg-tertiary, #f1f5f9)' : 'var(--bg-secondary)' }}; border: 1px solid var(--border-color); color: {{ !auth()->user()->isAdminOrAbove() ? 'var(--text-muted)' : 'var(--text-primary)' }}; {{ !auth()->user()->isAdminOrAbove() ? 'cursor: not-allowed;' : '' }}"
                                onfocus="{{ auth()->user()->isAdminOrAbove() ? "this.style.borderColor='var(--accent-blue)'" : '' }}"
                                onblur="{{ auth()->user()->isAdminOrAbove() ? "this.style.borderColor='var(--border-color)'" : '' }}">
                        </div>

                        {{-- NIB (Hanya Admin yang bisa mengisi) --}}
                        <div>
                            <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">
                                NIB (Nomor Induk Berusaha)
                                @if(!auth()->user()->isAdminOrAbove())
                                    <span class="ml-1 text-[10px] font-normal px-1.5 py-0.5 rounded-full" style="background: rgba(217,119,6,0.10); color: var(--accent-amber);">🔒 Admin Only</span>
                                @endif
                            </label>
                            <input type="text" name="nib" value="{{ old('nib') }}"
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
                            <input type="number" name="ongkir_pedia" value="{{ old('ongkir_pedia', 0) }}" min="0" step="1000"
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
                                onblur="this.style.borderColor='var(--border-color)'">{{ old('notes') }}</textarea>
                        </div>

                    </div>
                </div>


                {{-- Card: Alamat Penagihan (Billing) --}}
                <div class="card p-6" x-data="{ billingAddresses: [] }">
                    <div class="flex items-center justify-between mb-4 pb-3" style="border-bottom: 1px solid var(--border-color);">
                        <div class="flex flex-col">
                            <h2 class="text-sm font-semibold" style="color: var(--text-secondary);">Alamat Penagihan (Billing)</h2>
                            <label class="flex items-center gap-2 mt-1">
                                <input type="checkbox" id="same_as_company_billing" onchange="toggleSameAddress('billing')" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                <span class="text-xs" style="color: var(--text-secondary);">Sama dengan alamat perusahaan</span>
                            </label>
                        </div>
                        <button type="button" @click="billingAddresses.push({})"
                            class="text-xs font-medium px-3 py-1.5 rounded-lg transition-colors"
                            style="background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--accent-blue);">
                            + Tambah Alamat
                        </button>
                    </div>
                    <template x-for="(ba, index) in billingAddresses" :key="index">
                        <div class="p-4 mb-3 rounded-xl" style="background: var(--bg-secondary); border: 1px solid var(--border-color);">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-semibold" style="color: var(--text-muted);" x-text="'Alamat Penagihan #' + (index + 1)"></span>
                                <button type="button" @click="billingAddresses.splice(index, 1)" class="text-xs" style="color: var(--accent-rose);">Hapus</button>
                            </div>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Label</label>
                                    <input type="text" :name="'billing_addresses[' + index + '][label]'"
                                        placeholder="Kantor Pusat / Cabang"
                                        style="width: 100%; padding: 0.5rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; outline: none; background: var(--bg-primary); border: 1px solid var(--border-color); color: var(--text-primary);"
                                        onfocus="this.style.borderColor='var(--accent-blue)'"
                                        onblur="this.style.borderColor='var(--border-color)'">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Kota</label>
                                    <input type="text" :name="'billing_addresses[' + index + '][city]'"
                                        placeholder="Jakarta"
                                        style="width: 100%; padding: 0.5rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; outline: none; background: var(--bg-primary); border: 1px solid var(--border-color); color: var(--text-primary);"
                                        onfocus="this.style.borderColor='var(--accent-blue)'"
                                        onblur="this.style.borderColor='var(--border-color)'">
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Alamat</label>
                                    <textarea :name="'billing_addresses[' + index + '][address]'" rows="2"
                                        placeholder="Jl. Contoh No. 123"
                                        style="width: 100%; padding: 0.5rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; outline: none; background: var(--bg-primary); border: 1px solid var(--border-color); color: var(--text-primary); resize: none;"
                                        onfocus="this.style.borderColor='var(--accent-blue)'"
                                        onblur="this.style.borderColor='var(--border-color)'"></textarea>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Provinsi</label>
                                    <input type="text" :name="'billing_addresses[' + index + '][province]'"
                                        placeholder="DKI Jakarta"
                                        style="width: 100%; padding: 0.5rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; outline: none; background: var(--bg-primary); border: 1px solid var(--border-color); color: var(--text-primary);"
                                        onfocus="this.style.borderColor='var(--accent-blue)'"
                                        onblur="this.style.borderColor='var(--border-color)'">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Kode Pos</label>
                                    <input type="text" :name="'billing_addresses[' + index + '][postal_code]'"
                                        placeholder="12345"
                                        style="width: 100%; padding: 0.5rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; outline: none; background: var(--bg-primary); border: 1px solid var(--border-color); color: var(--text-primary);"
                                        onfocus="this.style.borderColor='var(--accent-blue)'"
                                        onblur="this.style.borderColor='var(--border-color)'">
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Negara</label>
                                    <input type="text" :name="'billing_addresses[' + index + '][country]'"
                                        placeholder="Indonesia"
                                        style="width: 100%; padding: 0.5rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; outline: none; background: var(--bg-primary); border: 1px solid var(--border-color); color: var(--text-primary);"
                                        onfocus="this.style.borderColor='var(--accent-blue)'"
                                        onblur="this.style.borderColor='var(--border-color)'">
                                </div>
                            </div>
                        </div>
                    </template>
                    <p x-show="billingAddresses.length === 0" class="text-xs text-center py-4" style="color: var(--text-muted);">Belum ada alamat penagihan. Klik "Tambah Alamat" untuk menambahkan.</p>
                </div>

                {{-- Card: Alamat Pengiriman (Shipping) --}}
                <div class="card p-6" x-data="{ shippingAddresses: [] }">
                    <div class="flex items-center justify-between mb-4 pb-3" style="border-bottom: 1px solid var(--border-color);">
                        <div class="flex flex-col">
                            <h2 class="text-sm font-semibold" style="color: var(--text-secondary);">Alamat Pengiriman (Shipping)</h2>
                            <label class="flex items-center gap-2 mt-1">
                                <input type="checkbox" id="same_as_company_shipping" onchange="toggleSameAddress('shipping')" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                <span class="text-xs" style="color: var(--text-secondary);">Sama dengan alamat perusahaan</span>
                            </label>
                        </div>
                        <button type="button" @click="shippingAddresses.push({})"
                            class="text-xs font-medium px-3 py-1.5 rounded-lg transition-colors"
                            style="background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--accent-blue);">
                            + Tambah Alamat
                        </button>
                    </div>
                    <template x-for="(sa, index) in shippingAddresses" :key="index">
                        <div class="p-4 mb-3 rounded-xl" style="background: var(--bg-secondary); border: 1px solid var(--border-color);">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-semibold" style="color: var(--text-muted);" x-text="'Alamat Pengiriman #' + (index + 1)"></span>
                                <button type="button" @click="shippingAddresses.splice(index, 1)" class="text-xs" style="color: var(--accent-rose);">Hapus</button>
                            </div>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Label</label>
                                    <input type="text" :name="'shipping_addresses[' + index + '][label]'"
                                        placeholder="Gudang / Pabrik"
                                        style="width: 100%; padding: 0.5rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; outline: none; background: var(--bg-primary); border: 1px solid var(--border-color); color: var(--text-primary);"
                                        onfocus="this.style.borderColor='var(--accent-blue)'"
                                        onblur="this.style.borderColor='var(--border-color)'">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Kota</label>
                                    <input type="text" :name="'shipping_addresses[' + index + '][city]'"
                                        placeholder="Jakarta"
                                        style="width: 100%; padding: 0.5rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; outline: none; background: var(--bg-primary); border: 1px solid var(--border-color); color: var(--text-primary);"
                                        onfocus="this.style.borderColor='var(--accent-blue)'"
                                        onblur="this.style.borderColor='var(--border-color)'">
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Alamat</label>
                                    <textarea :name="'shipping_addresses[' + index + '][address]'" rows="2"
                                        placeholder="Jl. Contoh No. 123"
                                        style="width: 100%; padding: 0.5rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; outline: none; background: var(--bg-primary); border: 1px solid var(--border-color); color: var(--text-primary); resize: none;"
                                        onfocus="this.style.borderColor='var(--accent-blue)'"
                                        onblur="this.style.borderColor='var(--border-color)'"></textarea>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Provinsi</label>
                                    <input type="text" :name="'shipping_addresses[' + index + '][province]'"
                                        placeholder="DKI Jakarta"
                                        style="width: 100%; padding: 0.5rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; outline: none; background: var(--bg-primary); border: 1px solid var(--border-color); color: var(--text-primary);"
                                        onfocus="this.style.borderColor='var(--accent-blue)'"
                                        onblur="this.style.borderColor='var(--border-color)'">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Kode Pos</label>
                                    <input type="text" :name="'shipping_addresses[' + index + '][postal_code]'"
                                        placeholder="12345"
                                        style="width: 100%; padding: 0.5rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; outline: none; background: var(--bg-primary); border: 1px solid var(--border-color); color: var(--text-primary);"
                                        onfocus="this.style.borderColor='var(--accent-blue)'"
                                        onblur="this.style.borderColor='var(--border-color)'">
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Negara</label>
                                    <input type="text" :name="'shipping_addresses[' + index + '][country]'"
                                        placeholder="Indonesia"
                                        style="width: 100%; padding: 0.5rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; outline: none; background: var(--bg-primary); border: 1px solid var(--border-color); color: var(--text-primary);"
                                        onfocus="this.style.borderColor='var(--accent-blue)'"
                                        onblur="this.style.borderColor='var(--border-color)'">
                                </div>
                            </div>
                        </div>
                    </template>
                    <p x-show="shippingAddresses.length === 0" class="text-xs text-center py-4" style="color: var(--text-muted);">Belum ada alamat pengiriman. Klik "Tambah Alamat" untuk menambahkan.</p>
                </div>

            </div>

            {{-- Kolom Kanan: Status & Assignment --}}
            <div class="space-y-5">
                <div class="card p-6 relative z-20">
                    <h2 class="text-sm font-semibold mb-4 pb-3" style="color: var(--text-secondary); border-bottom: 1px solid var(--border-color);">
                        Status &amp; Assignment
                    </h2>
                    <div class="space-y-4">

                        {{-- Status --}}
                        @if(auth()->user()->isAdminOrAbove())
                        <div>
                            <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">
                                Status <span style="color: var(--accent-rose);">*</span>
                            </label>
                            <x-custom-select name="status" :value="old('status', 'Prospect')" placeholder="Pilih Status" :options="[
                                'Prospect' => 'Prospect',
                                'Active' => 'Active (Aktif)',
                                'Inactive' => 'Inactive',
                                'Blacklist' => 'Blacklist',
                            ]" />
                        </div>
                        @else
                        <div class="p-3 rounded-xl" style="background: rgba(245,158,11,0.06); border: 1px solid rgba(245,158,11,0.15);">
                            <p class="text-xs font-medium" style="color: #d97706;">Status Otomatis:</p>
                            <div class="flex items-center mt-1">
                                <span class="status-badge status-pending text-xs">
                                    <span class="w-1.5 h-1.5 rounded-full inline-block" style="background: currentColor;"></span>
                                    Prospect (Menunggu Approval)
                                </span>
                            </div>
                        </div>
                        <input type="hidden" name="status" value="Prospect">
                        @endif

                        {{-- Assign Sales --}}
                        @if(auth()->user()->isAdminOrAbove())
                        <div>
                            <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">Assign ke Sales Marketing</label>
                            <x-custom-select name="sales_id" :value="old('sales_id')" placeholder="Pilih Sales Marketing" :options="collect(['' => 'Pilih Sales Marketing'])->merge($salesList->pluck('name', 'id')->toArray())" />
                        </div>
                        @else
                        <div class="p-3 rounded-xl" style="background: rgba(37,99,235,0.06); border: 1px solid rgba(37,99,235,0.15);">
                            <p class="text-xs font-medium" style="color: var(--accent-blue);">Ditugaskan ke:</p>
                            <p class="text-sm font-semibold mt-0.5" style="color: var(--text-primary);">{{ auth()->user()->name }}</p>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Tombol Aksi --}}
                <div class="flex flex-col gap-2 animate-in" style="animation-delay: 0.25s;">
                    <button type="submit"
                        class="w-full flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-semibold text-white rounded-xl transition-all active:scale-95 hover:-translate-y-0.5"
                        style="background: linear-gradient(135deg, var(--accent-blue), #1d4ed8); box-shadow: 0 4px 14px rgba(37,99,235,0.35);">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Simpan Customer
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

    <!-- JavaScript for Address Syncing -->
    <script>
        function toggleSameAddress(type) {
            const isChecked = document.getElementById(`same_as_company_${type}`).checked;
            
            // Company Address Fields
            const companyAddress = document.querySelector('textarea[name="address"]').value;
            const companyCity = document.querySelector('input[name="city"]').value;
            const companyProvince = document.querySelector('input[name="province"]').value;
            const companyPostal = document.querySelector('input[name="postal_code"]').value;
            const companyCountry = document.querySelector('input[name="country"]').value;
            
            // Target Fields
            const prefix = type === 'billing' ? 'billing_addresses[0]' : 'shipping_addresses[0]';
            
            if (isChecked) {
                // Ensure at least one element exists
                const btnContainer = type === 'billing' 
                    ? document.querySelector('div[x-data*="billingAddresses"]')
                    : document.querySelector('div[x-data*="shippingAddresses"]');
                const existingInput = btnContainer.querySelector(`textarea[name="${prefix}[address]"]`);
                
                if (!existingInput) {
                    const addBtn = btnContainer.querySelector('button');
                    if (addBtn) addBtn.click();
                }

                setTimeout(() => {
                    document.querySelector(`textarea[name="${prefix}[address]"]`).value = companyAddress;
                    document.querySelector(`input[name="${prefix}[city]"]`).value = companyCity;
                    document.querySelector(`input[name="${prefix}[province]"]`).value = companyProvince;
                    document.querySelector(`input[name="${prefix}[postal_code]"]`).value = companyPostal;
                    document.querySelector(`input[name="${prefix}[country]"]`).value = companyCountry;
                    
                    // Disable inputs to prevent accidental changes
                    document.querySelector(`textarea[name="${prefix}[address]"]`).readOnly = true;
                    document.querySelector(`input[name="${prefix}[city]"]`).readOnly = true;
                    document.querySelector(`input[name="${prefix}[province]"]`).readOnly = true;
                    document.querySelector(`input[name="${prefix}[postal_code]"]`).readOnly = true;
                    document.querySelector(`input[name="${prefix}[country]"]`).readOnly = true;
                }, 50);
            } else {
                const prefix = type === 'billing' ? 'billing_addresses[0]' : 'shipping_addresses[0]';
                const addrInput = document.querySelector(`textarea[name="${prefix}[address]"]`);
                if (addrInput) {
                    // Enable inputs if unchecked
                    addrInput.readOnly = false;
                    document.querySelector(`input[name="${prefix}[city]"]`).readOnly = false;
                    document.querySelector(`input[name="${prefix}[province]"]`).readOnly = false;
                    document.querySelector(`input[name="${prefix}[postal_code]"]`).readOnly = false;
                    document.querySelector(`input[name="${prefix}[country]"]`).readOnly = false;
                }
            }
        }
    </script>
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
    });
</script>

<script>
    // Live duplicate check saat input nama/email customer
    document.addEventListener('DOMContentLoaded', function () {
        const nameInput = document.querySelector('input[name="company_name"]');
        const emailInput = document.querySelector('input[name="email"]');

        function checkDuplicate(field, value) {
            if (!value || value.length < 3) return;
            const url = `/api/customers/check-duplicate?${field}=${encodeURIComponent(value)}`;
            
            const existingMsg = document.getElementById(`duplicate-${field}-msg`);
            if (existingMsg) existingMsg.remove();

            fetch(url)
                .then(res => res.json())
                .then(data => {
                    if (data.length > 0) {
                        const msg = document.createElement('div');
                        msg.id = `duplicate-${field}-msg`;
                        msg.className = 'mt-2 flex items-start gap-2 p-3 rounded-xl text-xs';
                        msg.style.cssText = 'background: rgba(225,29,72,0.06); border: 1px solid rgba(225,29,72,0.20); color: var(--accent-rose);';
                        msg.innerHTML = `<svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                        <span><strong>Terduplikasi!</strong> "${data[0].company_name}" sudah terdaftar atas nama Sales <strong>${data[0].owner}</strong>. Silakan gunakan nama lain atau hubungi sales terkait.</span>`;
                        
                        const parent = document.querySelector(`input[name="${field}"]`).closest('.sm\\:col-span-2, div');
                        (parent || document.querySelector(`input[name="${field}"]`).parentElement).appendChild(msg);
                    }
                })
                .catch(() => {});
        }

        if (nameInput) {
            nameInput.addEventListener('blur', function () {
                checkDuplicate('q', this.value);
            });
        }
        if (emailInput) {
            emailInput.addEventListener('blur', function () {
                checkDuplicate('email', this.value);
            });
        }
    });

    let contactCounter = 100; // Mulai dari 100 agar aman dari old contacts
    function addContactRow() {
        const emptyMsg = document.getElementById('contacts-empty-msg');
        if (emptyMsg) emptyMsg.style.display = 'none';

        const idx = contactCounter++;
        const rowId = 'contact-row-new-' + idx;
        const inputStyle = "width:100%;padding:0.5rem 0.75rem;border-radius:0.6rem;font-size:0.8rem;outline:none;background:var(--bg-primary);border:1px solid var(--border-color);color:var(--text-primary);";
        const focusBlur = `onfocus="this.style.borderColor='var(--accent-blue)'" onblur="this.style.borderColor='var(--border-color)'"`;

        const html = `
        <div class="contact-row p-4 rounded-xl relative" style="background: var(--bg-secondary); border: 1px solid var(--border-color);" id="${rowId}">
            <button type="button" onclick="removeContactRow('${rowId}')"
                class="absolute top-3 right-3 p-1 rounded-lg transition-colors" style="color: var(--accent-rose);" title="Hapus kontak ini">
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
        
        // Show empty message if no contacts left
        const container = document.getElementById('contacts-container');
        if (container && container.querySelectorAll('.contact-row').length === 0) {
            const emptyMsg = document.getElementById('contacts-empty-msg');
            if (emptyMsg) emptyMsg.style.display = 'block';
        }
    }
</script>
