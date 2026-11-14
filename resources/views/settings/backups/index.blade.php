<x-app-layout>

    <!-- Page Header -->
    <div class="flex flex-col gap-4 mb-6 sm:flex-row sm:items-center sm:justify-between animate-in" style="animation-delay: 0.05s;">
        <div>
            <h1 class="text-xl font-bold flex items-center gap-2" style="color: var(--text-primary);">
                <svg class="w-6 h-6 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/>
                </svg>
                Pusat Backup & Restore Data
            </h1>
            <p class="mt-1 text-sm" style="color: var(--text-muted);">
                Kelola cadangan database MySQL, file dokumen PO/lampiran, dan pemulihan sistem secara aman.
            </p>
        </div>

        <!-- Trigger Backup Modal Button -->
        <div x-data="{ openBackupModal: false }">
            <button @click="openBackupModal = true"
               class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white rounded-xl transition-all hover:-translate-y-0.5 hover:shadow-lg"
               style="background: linear-gradient(135deg, #4f46e5, #3730a3); box-shadow: 0 4px 14px rgba(79, 70, 229, 0.35);">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Buat Backup Sekarang
            </button>

            <!-- Modal Buat Backup Baru -->
            <div x-show="openBackupModal" 
                 x-cloak
                 class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0">
                <div class="w-full max-w-md p-6 bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-gray-100 dark:border-slate-800"
                     @click.away="openBackupModal = false">
                    <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-slate-800">
                        <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                            </svg>
                            Buat Cadangan (Backup)
                        </h3>
                        <button @click="openBackupModal = false" class="text-gray-400 hover:text-gray-600 text-lg font-bold">&times;</button>
                    </div>

                    <form action="{{ route('settings.backups.store') }}" method="POST" class="mt-4 space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-1.5">
                                Lingkup Data Backup
                            </label>
                            <select name="backup_type" required class="w-full px-3 py-2 text-sm rounded-xl border border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                                <option value="full" selected>Lengkap (Database MySQL + Dokumen Storage)</option>
                                <option value="db_only">Hanya Database MySQL (.sql.gz)</option>
                                <option value="files_only">Hanya Dokumen Storage (PO, Lampiran, Tanda Tangan)</option>
                            </select>
                            <p class="mt-1 text-xs text-gray-500">
                                File backup otomatis dikompresi (.zip) dan disimpan di server dengan perlindungan rotasi 14 hari.
                            </p>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-3">
                            <button type="button" @click="openBackupModal = false" class="px-4 py-2 text-xs font-medium text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-slate-800 rounded-xl hover:bg-gray-200">
                                Batal
                            </button>
                            <button type="submit" class="px-4 py-2 text-xs font-semibold text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 shadow-md">
                                Proses Backup
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Notifikasi Session -->
    @if(session('success'))
    <div class="p-4 mb-6 text-sm text-emerald-800 bg-emerald-50 dark:bg-emerald-950/40 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 rounded-xl flex items-center gap-3">
        <svg class="w-5 h-5 flex-shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span>{{ session('success') }}</span>
    </div>
    @endif

    @if(session('error'))
    <div class="p-4 mb-6 text-sm text-red-800 bg-red-50 dark:bg-red-950/40 dark:text-red-300 border border-red-200 dark:border-red-800 rounded-xl flex items-center gap-3">
        <svg class="w-5 h-5 flex-shrink-0 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span>{{ session('error') }}</span>
    </div>
    @endif

    <!-- Metric Cards -->
    <div class="grid grid-cols-1 gap-4 mb-6 sm:grid-cols-2 lg:grid-cols-4 animate-in" style="animation-delay: 0.1s;">
        <!-- Card 1: Total Backups -->
        <div class="card p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold tracking-wider uppercase text-gray-500 dark:text-gray-400">Total Arsip</p>
                    <h3 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $diskStats['total_backups'] }} File</h3>
                </div>
                <div class="w-12 h-12 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 flex items-center justify-center text-indigo-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                    </svg>
                </div>
            </div>
            <p class="mt-2 text-xs text-gray-500">Tersimpan di storage/app/backups</p>
        </div>

        <!-- Card 2: Total Storage Digunakan -->
        <div class="card p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold tracking-wider uppercase text-gray-500 dark:text-gray-400">Total Ukuran Arsip</p>
                    <h3 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $diskStats['total_backup_formatted'] }}</h3>
                </div>
                <div class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 flex items-center justify-center text-emerald-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                    </svg>
                </div>
            </div>
            <p class="mt-2 text-xs text-emerald-600 font-medium">Hemat ~85% berkat kompresi Gzip</p>
        </div>

        <!-- Card 3: Waktu Backup Terakhir -->
        <div class="card p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold tracking-wider uppercase text-gray-500 dark:text-gray-400">Backup Terakhir</p>
                    <h3 class="mt-1 text-sm font-bold text-gray-900 dark:text-white">
                        {{ $diskStats['last_backup'] ? \Carbon\Carbon::parse($diskStats['last_backup'])->isoFormat('D MMM YYYY, HH:mm') : 'Belum Ada' }}
                    </h3>
                </div>
                <div class="w-12 h-12 rounded-xl bg-amber-50 dark:bg-amber-950/50 flex items-center justify-center text-amber-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="mt-2 text-xs text-gray-500">Jadwal harian: Pukul 02:00 WIB</p>
        </div>

        <!-- Card 4: Kebijakan Retensi -->
        <div class="card p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold tracking-wider uppercase text-gray-500 dark:text-gray-400">Rotasi Retensi</p>
                    <h3 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">14 Hari</h3>
                </div>
                <div class="w-12 h-12 rounded-xl bg-cyan-50 dark:bg-cyan-950/50 flex items-center justify-center text-cyan-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </div>
            </div>
            <p class="mt-2 text-xs text-gray-500">File &gt; 14 hari dibersihkan otomatis</p>
        </div>
    </div>

    <!-- Informasi Edukasi & Keamanan -->
    <div class="p-4 mb-6 rounded-2xl border border-indigo-100 bg-indigo-50/50 dark:bg-indigo-950/20 dark:border-indigo-900/50 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div class="flex items-start gap-3">
            <div class="p-2 rounded-lg bg-indigo-500 text-white flex-shrink-0 mt-0.5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <h4 class="text-sm font-bold text-indigo-950 dark:text-indigo-200">Perlindungan Anti-Salah: Safety Snapshot Otomatis</h4>
                <p class="text-xs text-indigo-800 dark:text-indigo-300 mt-0.5">
                    Setiap kali Anda menekan <strong>Restore</strong>, sistem akan secara otomatis membuat backup darurat (snapshot) dari kondisi terkini sebelum data ditimpa. Anda selalu dapat membatalkan atau kembali ke kondisi sebelumnya kapan saja.
                </p>
            </div>
        </div>
    </div>

    <!-- Tabel Daftar Backup -->
    <div class="card overflow-hidden" x-data="{ restoreModalOpen: false, selectedFile: '', confirmInput: '' }">
        <div class="p-5 border-b border-gray-100 dark:border-slate-800 flex items-center justify-between">
            <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                Daftar Arsip Backup Tersedia
            </h3>
            <span class="text-xs font-semibold px-2.5 py-1 bg-gray-100 dark:bg-slate-800 text-gray-600 dark:text-gray-400 rounded-lg">
                {{ count($backups) }} Arsip
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-[11px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 bg-gray-50/50 dark:bg-slate-800/40 border-b border-gray-100 dark:border-slate-800">
                        <th class="px-5 py-3">Nama Arsip</th>
                        <th class="px-5 py-3">Waktu Dibuat</th>
                        <th class="px-5 py-3">Tipe</th>
                        <th class="px-5 py-3">Ukuran</th>
                        <th class="px-5 py-3">Dibuat Oleh</th>
                        <th class="px-5 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-slate-800 text-xs text-gray-700 dark:text-gray-300">
                    @forelse($backups as $b)
                    <tr class="hover:bg-gray-50/70 dark:hover:bg-slate-800/50 transition-colors">
                        <td class="px-5 py-4 font-mono font-medium text-gray-900 dark:text-white flex items-center gap-2">
                            @if($b['is_snapshot'])
                            <span class="px-2 py-0.5 text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 rounded">
                                Safety Snapshot
                            </span>
                            @else
                            <span class="px-2 py-0.5 text-[10px] font-bold bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300 rounded">
                                Backup
                            </span>
                            @endif
                            <span class="truncate max-w-xs">{{ $b['filename'] }}</span>
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap">
                            {{ \Carbon\Carbon::parse($b['created_at'])->isoFormat('D MMM YYYY, HH:mm') }}
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap">
                            @if($b['type'] === 'full')
                                <span class="text-emerald-600 font-semibold">Lengkap (DB + Files)</span>
                            @elseif($b['type'] === 'db_only')
                                <span class="text-blue-600 font-semibold">Database Saja</span>
                            @else
                                <span class="text-purple-600 font-semibold">Storage Files Saja</span>
                            @endif
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap font-medium text-gray-900 dark:text-white">
                            {{ $b['size_formatted'] }}
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap text-gray-500">
                            {{ $b['created_by'] }}
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap text-right">
                            <div class="inline-flex items-center gap-2">
                                <!-- Download Button -->
                                <a href="{{ route('settings.backups.download', $b['filename']) }}" 
                                   title="Download file zip ke komputer Anda"
                                   class="p-1.5 text-gray-600 dark:text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-slate-800 rounded-lg transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                    </svg>
                                </a>

                                <!-- Restore Trigger Button -->
                                <button type="button"
                                        @click="restoreModalOpen = true; selectedFile = '{{ $b['filename'] }}'; confirmInput = ''"
                                        title="Pulihkan (Restore) data dari backup ini"
                                        class="p-1.5 text-amber-600 hover:text-amber-800 hover:bg-amber-50 dark:hover:bg-amber-950/40 rounded-lg transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                </button>

                                <!-- Delete Button -->
                                <form action="{{ route('settings.backups.destroy', $b['filename']) }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus file backup ini secara permanen?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            title="Hapus file backup"
                                            class="p-1.5 text-red-500 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-950/40 rounded-lg transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-5 py-8 text-center text-gray-400">
                            Belum ada file backup yang tersimpan di server. Klik tombol <strong>"Buat Backup Sekarang"</strong> di atas untuk membuat backup perdana.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Restore Confirmation Modal -->
        <div x-show="restoreModalOpen" 
             x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            <div class="w-full max-w-lg p-6 bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-red-200 dark:border-red-900"
                 @click.away="restoreModalOpen = false">
                <div class="flex items-center gap-3 text-red-600 mb-4">
                    <div class="w-10 h-10 rounded-full bg-red-100 dark:bg-red-950/60 flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">Konfirmasi Pemulihan (Restore)</h3>
                        <p class="text-xs text-gray-500">Tindakan ini memerlukan verifikasi ganda.</p>
                    </div>
                </div>

                <div class="p-3.5 mb-4 text-xs bg-amber-50 dark:bg-amber-950/40 text-amber-900 dark:text-amber-200 rounded-xl border border-amber-200 dark:border-amber-900">
                    <p class="font-semibold mb-1">Peringatan:</p>
                    <p>Data transaksi dan file saat ini akan ditimpa dengan isi dari file backup: <br><strong class="font-mono text-indigo-700 dark:text-indigo-300" x-text="selectedFile"></strong>.</p>
                    <p class="mt-2 text-emerald-700 dark:text-emerald-300 font-medium">
                        ✓ Tenang: Sistem akan secara otomatis membuat <strong>Safety Snapshot</strong> dari data saat ini sebelum proses penimpaan dilakukan.
                    </p>
                </div>

                <form action="{{ route('settings.backups.restore') }}" method="POST" class="space-y-4">
                    @csrf
                    <input type="hidden" name="filename" :value="selectedFile">

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                            Ketik kata <span class="font-bold text-red-600">RESTORE</span> di bawah untuk konfirmasi:
                        </label>
                        <input type="text" 
                               name="confirm_text" 
                               x-model="confirmInput" 
                               placeholder="RESTORE" 
                               required
                               autocomplete="off"
                               class="w-full px-3 py-2 text-sm rounded-xl border border-gray-300 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 text-gray-900 dark:text-white font-mono tracking-wider uppercase focus:ring-2 focus:ring-red-500">
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3">
                        <button type="button" @click="restoreModalOpen = false" class="px-4 py-2 text-xs font-medium text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-slate-800 rounded-xl hover:bg-gray-200">
                            Batal
                        </button>
                        <button type="submit" 
                                :disabled="confirmInput !== 'RESTORE'"
                                :class="confirmInput === 'RESTORE' ? 'bg-red-600 hover:bg-red-700 cursor-pointer shadow-lg' : 'bg-gray-300 dark:bg-slate-700 cursor-not-allowed opacity-50'"
                                class="px-5 py-2 text-xs font-semibold text-white rounded-xl transition-all">
                            Ya, Pulihkan Data
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</x-app-layout>
