<x-app-layout>
    <div x-data="{
        gaji: {{ old('mp', (int)($mainpower->mp ?? 200000)) }},
        uangMakan: {{ old('uang_makan', (int)($mainpower->uang_makan ?? 50000)) }},
        thr: {{ old('thr', (int)($mainpower->thr ?? 0)) }},
        bpjsKesPercent: {{ old('bpjs_kes_percent', (float)($mainpower->bpjs_kes_percent ?? 4.0)) }},
        bpjsTkPercent: {{ old('bpjs_tk_percent', (float)($mainpower->bpjs_tk_percent ?? 5.7)) }},
        lemburPerJam: {{ old('lembur_per_jam', (int)($mainpower->lembur_per_jam ?? 37500)) }},

        get bpjsKesNominal() {
            return Math.round((parseFloat(this.gaji) || 0) * (parseFloat(this.bpjsKesPercent) || 0) / 100);
        },
        get bpjsTkNominal() {
            return Math.round((parseFloat(this.gaji) || 0) * (parseFloat(this.bpjsTkPercent) || 0) / 100);
        },
        get totalRateHarian() {
            const g = parseFloat(this.gaji) || 0;
            const um = parseFloat(this.uangMakan) || 0;
            const t = parseFloat(this.thr) || 0;
            return Math.round(g + um + t + this.bpjsKesNominal + this.bpjsTkNominal);
        },
        formatRupiah(num) {
            return new Intl.NumberFormat('id-ID').format(num);
        },
        autoCalcLembur() {
            const g = parseFloat(this.gaji) || 0;
            this.lemburPerJam = Math.round((g / 8) * 1.5);
        }
    }">
        <!-- Page Header -->
        <div class="flex flex-col gap-4 mb-6 sm:flex-row sm:items-center sm:justify-between animate-in" style="animation-delay: 0.05s;">
            <div>
                <h1 class="text-xl font-bold flex items-center gap-2" style="color: var(--text-primary);">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--accent-blue);">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    Portal Tarif Mainpower (Internal Pedia)
                </h1>
                <p class="mt-1 text-sm" style="color: var(--text-muted);">Setup standar acuan kompensasi teknisi & BPJS untuk otomatisasi kalkulasi HPP RFQ Jasa Pemasangan</p>
            </div>

            <div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold" style="background: rgba(37,99,235,0.1); color: var(--accent-blue); border: 1px solid rgba(37,99,235,0.2);">
                    <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                    Formula Aktif Internal Pedia
                </span>
            </div>
        </div>

        @if (session('success'))
            <div class="p-4 mb-6 text-sm font-medium rounded-xl flex items-center gap-3 animate-in" style="background: rgba(16,185,129,0.12); color: var(--accent-emerald); border: 1px solid rgba(16,185,129,0.25);">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if (isset($errors) && $errors->any())
            <div class="p-4 mb-6 text-sm font-medium rounded-xl animate-in" style="background: rgba(244,63,94,0.12); color: var(--accent-rose); border: 1px solid rgba(244,63,94,0.25);">
                <div class="font-bold mb-1">Perhatian:</div>
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Form Input Formula -->
            <div class="lg:col-span-2">
                <div class="card p-6">
                    <div class="flex items-center justify-between pb-4 mb-5" style="border-bottom: 1px solid var(--border-color);">
                        <div>
                            <h2 class="text-base font-bold" style="color: var(--text-primary);">Komponen Tarif Teknisi / Mainpower</h2>
                            <p class="text-xs mt-0.5" style="color: var(--text-muted);">Masukkan parameter upah dasar dan persentase BPJS</p>
                        </div>
                    </div>

                    <form action="{{ route('mainpowers.store') }}" method="POST">
                        @csrf

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-6 text-sm">
                            {{-- Gaji Pokok Harian --}}
                            <div>
                                <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">
                                    Gaji Pokok Harian (MP) <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-xs font-bold" style="color: var(--text-muted);">Rp</span>
                                    <input type="number" name="mp" x-model.number="gaji" @input="autoCalcLembur()" required min="0" step="1000"
                                        class="w-full pl-10 pr-3 py-2.5 rounded-xl outline-none font-semibold text-sm"
                                        style="background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary);">
                                </div>
                                <p class="text-[11px] mt-1" style="color: var(--text-muted);">Upah pokok teknisi per 1 hari kerja (8 jam)</p>
                            </div>

                            {{-- Uang Makan Harian --}}
                            <div>
                                <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">
                                    Uang Makan Harian
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-xs font-bold" style="color: var(--text-muted);">Rp</span>
                                    <input type="number" name="uang_makan" x-model.number="uangMakan" min="0" step="1000"
                                        class="w-full pl-10 pr-3 py-2.5 rounded-xl outline-none font-semibold text-sm"
                                        style="background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary);">
                                </div>
                                <p class="text-[11px] mt-1" style="color: var(--text-muted);">Tunjangan makan & transport harian</p>
                            </div>

                            {{-- Tunjangan Hari Raya (THR Pro-rata) --}}
                            <div>
                                <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">
                                    Cadangan THR Harian
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-xs font-bold" style="color: var(--text-muted);">Rp</span>
                                    <input type="number" name="thr" x-model.number="thr" min="0" step="1000"
                                        class="w-full pl-10 pr-3 py-2.5 rounded-xl outline-none font-semibold text-sm"
                                        style="background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary);">
                                </div>
                                <p class="text-[11px] mt-1" style="color: var(--text-muted);">Penyisihan THR per hari kerja</p>
                            </div>

                            {{-- Lembur per Jam --}}
                            <div>
                                <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">
                                    Tarif Lembur per Jam
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-xs font-bold" style="color: var(--text-muted);">Rp</span>
                                    <input type="number" name="lembur_per_jam" x-model.number="lemburPerJam" min="0" step="500"
                                        class="w-full pl-10 pr-3 py-2.5 rounded-xl outline-none font-semibold text-sm"
                                        style="background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary);">
                                </div>
                                <p class="text-[11px] mt-1" style="color: var(--text-muted);">Standar: 1.5x upah per jam kerja</p>
                            </div>
                        </div>

                        {{-- Section BPJS --}}
                        <div class="p-4 rounded-xl mb-6" style="background: var(--bg-secondary); border: 1px solid var(--border-color);">
                            <h3 class="text-xs font-bold uppercase tracking-wider mb-3 flex items-center gap-1.5" style="color: var(--accent-blue);">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                Formula Jaminan Sosial & BPJS
                            </h3>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                {{-- BPJS Kesehatan --}}
                                <div>
                                    <div class="flex items-center justify-between mb-1.5">
                                        <label class="text-xs font-semibold" style="color: var(--text-secondary);">BPJS Kesehatan</label>
                                        <span class="text-xs font-mono font-bold" style="color: var(--accent-emerald);" x-text="'Rp ' + formatRupiah(bpjsKesNominal)"></span>
                                    </div>
                                    <div class="relative">
                                        <input type="number" name="bpjs_kes_percent" x-model.number="bpjsKesPercent" step="0.1" min="0" max="100"
                                            class="w-full pl-3 pr-8 py-2 rounded-xl outline-none font-semibold text-sm"
                                            style="background: var(--bg-primary); border: 1px solid var(--border-color); color: var(--text-primary);">
                                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-bold" style="color: var(--text-muted);">%</span>
                                    </div>
                                    <input type="hidden" name="bpjs_kes" :value="bpjsKesNominal">
                                    <p class="text-[11px] mt-1" style="color: var(--text-muted);">Standar Pedia: 4.0% dari Gaji Pokok</p>
                                </div>

                                {{-- BPJS Ketenagakerjaan --}}
                                <div>
                                    <div class="flex items-center justify-between mb-1.5">
                                        <label class="text-xs font-semibold" style="color: var(--text-secondary);">BPJS Ketenagakerjaan (TK)</label>
                                        <span class="text-xs font-mono font-bold" style="color: var(--accent-emerald);" x-text="'Rp ' + formatRupiah(bpjsTkNominal)"></span>
                                    </div>
                                    <div class="relative">
                                        <input type="number" name="bpjs_tk_percent" x-model.number="bpjsTkPercent" step="0.1" min="0" max="100"
                                            class="w-full pl-3 pr-8 py-2 rounded-xl outline-none font-semibold text-sm"
                                            style="background: var(--bg-primary); border: 1px solid var(--border-color); color: var(--text-primary);">
                                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-bold" style="color: var(--text-muted);">%</span>
                                    </div>
                                    <input type="hidden" name="bpjs_tk" :value="bpjsTkNominal">
                                    <p class="text-[11px] mt-1" style="color: var(--text-muted);">Standar Pedia: 5.7% (JKK, JKM, JHT)</p>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-3" style="border-top: 1px solid var(--border-color);">
                            <button type="submit" class="px-6 py-2.5 text-sm font-semibold text-white rounded-xl transition-all hover:shadow-lg"
                                style="background: linear-gradient(135deg, var(--accent-blue), #1d4ed8); box-shadow: 0 4px 12px rgba(37,99,235,0.25);">
                                Simpan Tarif Portal MP
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Preview Total Rate Card -->
            <div class="space-y-6">
                <div class="card p-6" style="background: linear-gradient(145deg, rgba(37,99,235,0.06), rgba(16,185,129,0.06)); border: 1px solid rgba(37,99,235,0.2);">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-xs font-bold uppercase tracking-wider" style="color: var(--accent-blue);">Total Rate Harian</span>
                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full" style="background: rgba(16,185,129,0.12); color: var(--accent-emerald);">Auto-Calculated</span>
                    </div>

                    <div class="mb-4">
                        <p class="text-3xl font-extrabold tracking-tight" style="color: var(--text-primary);">
                            Rp <span x-text="formatRupiah(totalRateHarian)"></span>
                        </p>
                        <p class="text-xs mt-1" style="color: var(--text-muted);">per teknisi / hari kerja</p>
                    </div>

                    <div class="space-y-2.5 text-xs pt-4" style="border-top: 1px solid var(--border-color);">
                        <div class="flex justify-between" style="color: var(--text-secondary);">
                            <span>Gaji Pokok Harian:</span>
                            <span class="font-semibold font-mono" x-text="'Rp ' + formatRupiah(gaji)"></span>
                        </div>
                        <div class="flex justify-between" style="color: var(--text-secondary);">
                            <span>Uang Makan Harian:</span>
                            <span class="font-semibold font-mono" x-text="'Rp ' + formatRupiah(uangMakan)"></span>
                        </div>
                        <div class="flex justify-between" style="color: var(--text-secondary);">
                            <span>THR Pro-rata:</span>
                            <span class="font-semibold font-mono" x-text="'Rp ' + formatRupiah(thr)"></span>
                        </div>
                        <div class="flex justify-between" style="color: var(--text-secondary);">
                            <span>BPJS Kesehatan (<span x-text="bpjsKesPercent"></span>%):</span>
                            <span class="font-semibold font-mono" x-text="'Rp ' + formatRupiah(bpjsKesNominal)"></span>
                        </div>
                        <div class="flex justify-between" style="color: var(--text-secondary);">
                            <span>BPJS TK (<span x-text="bpjsTkPercent"></span>%):</span>
                            <span class="font-semibold font-mono" x-text="'Rp ' + formatRupiah(bpjsTkNominal)"></span>
                        </div>
                    </div>
                </div>

                {{-- Card Info Integrasi RFQ --}}
                <div class="card p-5">
                    <h3 class="text-xs font-bold uppercase tracking-wider mb-2 flex items-center gap-1.5" style="color: var(--text-secondary);">
                        <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Integrasi Otomatis HPP RFQ
                    </h3>
                    <p class="text-xs leading-relaxed" style="color: var(--text-muted);">
                        Nilai Total Rate Harian di atas secara langsung ditarik oleh tombol <span class="font-semibold px-1.5 py-0.5 rounded bg-blue-100 text-blue-700">"Gunakan MP"</span> di halaman kalkulasi harga HPP Jasa Pemasangan (<code class="font-mono text-[11px]">/rfqs/{id}/price</code>).
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
