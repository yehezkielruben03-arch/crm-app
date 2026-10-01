<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold leading-tight" style="color: var(--text-primary);">
                    📖 Buku Panduan Pengguna Resmi CRM Analyst
                </h2>
                <p class="text-xs" style="color: var(--text-muted);">
                    PT. Pedia Technology Indonesia — Pedoman Operasional Menyeluruh (Deep Scan) untuk Tim Sales, Admin, dan Leader.
                </p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('manual-book.download') }}" 
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold text-white shadow-sm hover:opacity-90 transition-all"
                   style="background: #2563eb;">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Unduh Format PDF Resmi (15 Halaman HD)
                </a>
                <button onclick="window.print()" 
                        class="inline-flex items-center gap-2 px-3 py-2 rounded-xl text-xs font-semibold transition-all border"
                        style="background: var(--bg-card); color: var(--text-primary); border-color: var(--border-color);">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Cetak
                </button>
            </div>
        </div>
    </x-slot>

    <!-- Quick Role Jumper Navigation -->
    <div class="card p-3 mb-6 flex flex-wrap items-center justify-between gap-3 text-sm">
        <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Pilih Bab Panduan:</span>
        <div class="flex flex-wrap gap-2">
            <a href="#alur" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200">🔄 Alur Transaksi</a>
            <a href="#sales" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-blue-50 text-blue-700 hover:bg-blue-100">🔵 Panduan Sales</a>
            <a href="#pic-section" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-indigo-50 text-indigo-700 hover:bg-indigo-100">👥 Kontak PIC Customer</a>
            <a href="#projek-section" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-teal-50 text-teal-800 hover:bg-teal-100">🏢 RFQ Projek</a>
            <a href="#admin" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-amber-50 text-amber-800 hover:bg-amber-100">🟠 Panduan Admin</a>
            <a href="#vendor-section" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-orange-50 text-orange-800 hover:bg-orange-100">🏭 Vendor & Mainpower</a>
            <a href="#leader" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-800 hover:bg-emerald-100">🟢 Panduan Leader</a>
            <a href="#analytics-section" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-cyan-50 text-cyan-800 hover:bg-cyan-100">📊 Revenue Analytics</a>
            <a href="#faq" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-purple-50 text-purple-700 hover:bg-purple-100">❓ Tanya Jawab (FAQ)</a>
        </div>
    </div>

    <!-- Friendly Senior Banner -->
    <div class="p-4 rounded-2xl mb-8 flex items-start gap-4 border" style="background: #eff6ff; border-color: #bfdbfe;">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 bg-blue-600 text-white font-bold text-lg">
            💡
        </div>
        <div>
            <h3 class="text-base font-bold text-blue-900 mb-1">Panduan Lengkap (Deep Scan) untuk Seluruh Staf & Pimpinan PT. Pedia Technology Indonesia</h3>
            <p class="text-sm text-blue-800 leading-relaxed">
                Buku panduan ini telah diperbarui secara mendalam dengan menyertakan <strong>seluruh alur kerja sistem CRM</strong>: mulai dari manajemen <strong>Kontak PIC Customer (Multiple PIC & Pop-up Tambah PIC Instan)</strong>, perbedaan <strong>RFQ Non-Projek vs RFQ Projek (3 Blok Kategori: Hardware, Jasa, Material)</strong>, direktori <strong>Master Rekanan Vendor & Portal Mainpower</strong>, hingga dasbor pimpinan <strong>Approval Center & Revenue Analytics</strong>.
            </p>
        </div>
    </div>

    {{-- ═════════════════════════════════════════════════════════════
         BAGIAN 1: ALUR TRANSAKSI UTAMA
         ═════════════════════════════════════════════════════════════ --}}
    <section id="alur" class="card p-6 mb-8 scroll-mt-20">
        <div class="flex items-center gap-3 mb-4 border-b pb-3" style="border-color: var(--border-color);">
            <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-blue-100 text-blue-700 font-bold">1</div>
            <div>
                <h3 class="text-lg font-bold" style="color: var(--text-primary);">Bagan Alur Transaksi CRM (Dari Awal Sampai Berhasil)</h3>
                <p class="text-xs" style="color: var(--text-muted);">Setiap penawaran barang/jasa di PT. Pedia Technology Indonesia bergerak melewati 6 tahapan terintegrasi:</p>
            </div>
        </div>

        <div class="overflow-x-auto mb-4">
            <table class="data-table text-sm w-full">
                <thead>
                    <tr>
                        <th class="w-16 text-center">Tahap</th>
                        <th class="w-44">Status di Sistem</th>
                        <th class="w-36">Penanggung Jawab</th>
                        <th>Tindakan Nyata di Aplikasi Web</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="text-center font-bold">1</td>
                        <td><span class="px-2.5 py-1 rounded-md text-xs font-bold bg-amber-100 text-amber-800 border border-amber-300">PENDING ADMIN</span></td>
                        <td class="font-bold text-blue-700">Tim Sales</td>
                        <td>Mendaftarkan klien & kontak PIC, lalu menginputkan daftar barang/jasa ke formulir RFQ.</td>
                    </tr>
                    <tr>
                        <td class="text-center font-bold">2</td>
                        <td><span class="px-2.5 py-1 rounded-md text-xs font-bold bg-amber-100 text-amber-800 border border-amber-300">PENDING LEADER</span></td>
                        <td class="font-bold text-amber-700">Tim Admin</td>
                        <td>Memilih vendor rekanan, memasukkan harga modal (HPP), estimasi ongkir, dan margin laba %.</td>
                    </tr>
                    <tr>
                        <td class="text-center font-bold">3</td>
                        <td><span class="px-2.5 py-1 rounded-md text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">APPROVED</span></td>
                        <td class="font-bold text-emerald-700">Tim Leader</td>
                        <td>Pimpinan meninjau kelayakan margin. Jika setuju, surat penawaran (Quotation PDF) resmi terbit otomatis.</td>
                    </tr>
                    <tr>
                        <td class="text-center font-bold">4</td>
                        <td><span class="px-2.5 py-1 rounded-md text-xs font-bold bg-cyan-100 text-cyan-800 border border-cyan-300">QUOTATION SENT</span></td>
                        <td class="font-bold text-blue-700">Tim Sales</td>
                        <td>Sales mengunduh PDF resmi (berstempel dinamis & TTD), mengirim ke klien, dan klik "Mark Quotation Sent".</td>
                    </tr>
                    <tr>
                        <td class="text-center font-bold">5</td>
                        <td><span class="px-2.5 py-1 rounded-md text-xs font-bold bg-purple-100 text-purple-800 border border-purple-300">PO RECEIVED</span></td>
                        <td class="font-bold text-purple-700">Sales & Admin</td>
                        <td>Klien sepakat dan menerbitkan lembar PO. Sales mengunggah berkas PO tersebut ke sistem RFQ.</td>
                    </tr>
                    <tr>
                        <td class="text-center font-bold">6</td>
                        <td><span class="px-2.5 py-1 rounded-md text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">GOAL (SUKSES)</span></td>
                        <td class="font-bold text-emerald-700">Tim Leader</td>
                        <td>Leader memverifikasi berkas PO dan klik "Approve Menjadi GOAL". Omzet resmi masuk ke pencapaian sales!</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-700">
            <strong>📌 Catatan Integrasi:</strong> Sistem CRM ini terhubung secara <em>real-time</em>. Begitu satu tim menyelesaikan tugasnya, sistem otomatis mengirim notifikasi lonceng ke tim penanggung jawab langkah berikutnya.
        </div>
    </section>

    {{-- ═════════════════════════════════════════════════════════════
         BAGIAN 2: PANDUAN TIM SALES
         ═════════════════════════════════════════════════════════════ --}}
    <section id="sales" class="card p-6 mb-8 scroll-mt-20">
        <div class="flex items-center gap-3 mb-6 border-b pb-3" style="border-color: var(--border-color);">
            <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-blue-600 text-white font-bold">2</div>
            <div>
                <h3 class="text-lg font-bold" style="color: var(--text-primary);">BAB 1: Panduan Lengkap untuk Tim Sales (Pemasaran)</h3>
                <p class="text-xs" style="color: var(--text-muted);">Panduan dari login, pendaftaran profil & tanda tangan, membuat RFQ, unduh penawaran, sampai unggah PO.</p>
            </div>
        </div>

        <!-- 1.1 Login -->
        <div class="mb-8">
            <h4 class="text-base font-bold text-slate-800 mb-2">1.1 Cara Masuk ke Aplikasi CRM (Login)</h4>
            <p class="text-sm text-slate-600 mb-4">
                Buka browser Chrome atau Edge di komputer Bapak/Ibu, lalu buka alamat web CRM kantor. Layar masuk asli tampak seperti gambar berikut:
            </p>
            <div class="rounded-xl overflow-hidden border border-slate-200 shadow-sm max-w-4xl mb-4 bg-white">
                <img src="{{ asset('images/manual/step1_login.png') }}" alt="Foto 1.1: Tangkapan Layar Asli Halaman Masuk (Login) CRM" class="w-full h-auto">
            </div>
            <div class="flex items-center justify-between text-xs text-slate-500 mb-3 max-w-4xl px-1">
                <span>Foto 1.1: Tangkapan Layar Asli Halaman Masuk (Login) CRM</span>
                <span class="font-mono bg-slate-100 px-2 py-0.5 rounded">URL: /login</span>
            </div>
            <div class="overflow-x-auto max-w-4xl mb-4">
                <table class="data-table text-sm">
                    <thead>
                        <tr>
                            <th class="w-64">Nama Bagian di Layar</th>
                            <th>Petunjuk Pengisian untuk Bapak/Ibu</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="font-bold">Username / Email</td>
                            <td>Ketik alamat email resmi kantor Anda (contoh: <code>ade@crm.com</code>) atau username akun Anda.</td>
                        </tr>
                        <tr>
                            <td class="font-bold">Password</td>
                            <td>Ketik kata sandi akun Anda. Anda bisa mengklik ikon mata di sebelah kanan untuk memeriksa ketikan Anda.</td>
                        </tr>
                        <tr>
                            <td class="font-bold">Keep me signed in on this device</td>
                            <td>Centang kotak ini jika Anda menggunakan komputer/laptop kerja pribadi agar tidak perlu login ulang tiap hari.</td>
                        </tr>
                        <tr>
                            <td class="font-bold">Tombol "Sign In to Dashboard"</td>
                            <td>Klik tombol warna <span class="px-2 py-0.5 rounded text-xs font-bold text-white bg-blue-600">Sign In to Dashboard</span> (tombol biru di bawah formulir) untuk masuk ke halaman utama aplikasi.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 1.2 Profil & Tanda Tangan -->
        <div class="mb-8 pt-6 border-t border-slate-100">
            <h4 class="text-base font-bold text-slate-800 mb-2">1.2 Mengatur Jabatan & Tanda Tangan Digital Sales</h4>
            <p class="text-sm text-slate-600 mb-4">
                Sebelum membuat penawaran pertama kali, Bapak/Ibu <strong>wajib</strong> mengisi jabatan dan tanda tangan digital di menu <strong>Profile</strong> (klik foto/nama di sidebar kiri bawah atau pojok kanan atas).
            </p>
            <div class="rounded-xl overflow-hidden border border-slate-200 shadow-sm max-w-4xl mb-4 bg-white">
                <img src="{{ asset('images/manual/step2_profil_ttd.png') }}" alt="Foto 1.2: Tangkapan Layar Asli Menu Profil & Kanvas Tanda Tangan Digital" class="w-full h-auto">
            </div>
            <div class="flex items-center justify-between text-xs text-slate-500 mb-3 max-w-4xl px-1">
                <span>Foto 1.2: Tangkapan Layar Asli Menu Profil & Kanvas Tanda Tangan Digital</span>
                <span class="font-mono bg-slate-100 px-2 py-0.5 rounded">URL: /profile</span>
            </div>
            <div class="overflow-x-auto max-w-4xl mb-4">
                <table class="data-table text-sm">
                    <thead>
                        <tr>
                            <th class="w-64">Nama Kolom / Tombol</th>
                            <th>Cara Pengisian yang Tepat</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="font-bold">JABATAN RESMI DI DOKUMEN *</td>
                            <td>Ketik nama sebutan jabatan resmi Anda (contoh: <code>Account Manager</code> atau <code>Senior Sales Executive</code>). Jabatan ini otomatis tercetak tepat di bawah nama Anda pada surat penawaran resmi.</td>
                        </tr>
                        <tr>
                            <td class="font-bold">NOMOR TELEPON / WHATSAPP</td>
                            <td>Ketik nomor HP aktif Anda (contoh: <code>0812-3456-7890</code>) untuk dicantumkan pada paragraf penutup surat penawaran.</td>
                        </tr>
                        <tr>
                            <td class="font-bold">Kanvas "Coret di Layar"</td>
                            <td>Gunakan mouse (atau jari jika menggunakan layar sentuh/HP) untuk menggoreskan tanda tangan Anda di kotak putih. Jika salah, klik tombol <span class="px-2 py-0.5 rounded text-xs font-semibold bg-slate-200 text-slate-700">Bersihkan</span> lalu ulangi.</td>
                        </tr>
                        <tr>
                            <td class="font-bold">Pilihan Ketebalan</td>
                            <td>Pilih opsi <strong>"Tebal / Kereng"</strong> agar garis goresan tanda tangan terlihat tebal, tegas, dan berwibawa saat dicetak pada kertas.</td>
                        </tr>
                        <tr>
                            <td class="font-bold">Simulasi di Lembar Quotation</td>
                            <td>Kotak di sebelah kanan otomatis memperlihatkan simulasi tanda tangan Anda yang disandingkan dengan <strong>stempel resmi PT. Pedia Technology Indonesia</strong>.</td>
                        </tr>
                        <tr>
                            <td class="font-bold">Tombol "Simpan Perubahan Identitas & TTD"</td>
                            <td>Klik tombol warna <span class="px-2 py-0.5 rounded text-xs font-bold text-white bg-blue-600">✓ Simpan Perubahan Identitas & TTD</span> di bagian bawah untuk menyimpan.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 1.3 Tambah Customer -->
        <div class="mb-8 pt-6 border-t border-slate-100">
            <h4 class="text-base font-bold text-slate-800 mb-2">1.3 Mendaftarkan Pelanggan / Klien Baru</h4>
            <p class="text-sm text-slate-600 mb-4">
                Setiap ada calon pembeli baru, daftarkan datanya terlebih dahulu melalui menu: <strong>Customers ➔ + Tambah Customer</strong>.
            </p>
            <div class="rounded-xl overflow-hidden border border-slate-200 shadow-sm max-w-4xl mb-4 bg-white">
                <img src="{{ asset('images/manual/step3_tambah_klien.png') }}" alt="Foto 1.3: Tangkapan Layar Asli Formulir Pendaftaran Customer Baru" class="w-full h-auto">
            </div>
            <div class="flex items-center justify-between text-xs text-slate-500 mb-3 max-w-4xl px-1">
                <span>Foto 1.3: Tangkapan Layar Asli Formulir Pendaftaran Customer Baru</span>
                <span class="font-mono bg-slate-100 px-2 py-0.5 rounded">URL: /customers/create</span>
            </div>
            <div class="overflow-x-auto max-w-4xl mb-4">
                <table class="data-table text-sm">
                    <thead>
                        <tr>
                            <th class="w-64">Bagian Formulir</th>
                            <th>Petunjuk Pengisian</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="font-bold">Tipe Customer</td>
                            <td>Pilih badan usaha dari kotak pilihan: <strong>PT (Perseroan Terbatas), CV, Perorangan</strong>, atau <strong>Pemerintah</strong>.</td>
                        </tr>
                        <tr>
                            <td class="font-bold">Nama Perusahaan *</td>
                            <td>Ketik nama perusahaan pembeli <em>tanpa menuliskan PT/CV lagi di depan</em> (contoh: cukup ketik <code>Karya Bangsa</code>).</td>
                        </tr>
                        <tr>
                            <td class="font-bold">Bidang Usaha & Industry Sector</td>
                            <td>Ketik sektor usaha pembeli (contoh: <code>Distribusi Elektronik</code>, Kontraktor atau Manufaktur).</td>
                        </tr>
                        <tr>
                            <td class="font-bold">Lokasi & Alamat Lengkap</td>
                            <td>Pilih bertingkat: <strong>Provinsi ➔ Kota/Kabupaten ➔ Kecamatan ➔ Kelurahan/Desa</strong>, dan ketik alamat lengkap kantor pembeli untuk keperluan kop surat penawaran dan alamat tujuan kirim barang.</td>
                        </tr>
                        <tr>
                            <td class="font-bold">Tombol "Simpan Customer"</td>
                            <td>Klik tombol warna <span class="px-2 py-0.5 rounded text-xs font-bold text-white bg-blue-600">✓ Simpan Customer</span> di panel sebelah kanan untuk menyimpan data klien ke sistem.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 1.4 Manajemen Kontak PIC Customer -->
        <div id="pic-section" class="mb-8 pt-6 border-t border-slate-100 scroll-mt-20">
            <h4 class="text-base font-bold text-slate-800 mb-2">1.4 Mengelola Kontak Narahubung / PIC (Person In Charge)</h4>
            <p class="text-sm text-slate-600 mb-4">
                Setiap customer dapat memiliki lebih dari satu narahubung (Multiple PIC) untuk mempermudah koordinasi penawaran harga, penagihan, maupun koordinasi teknis lapangan.
            </p>
            <div class="rounded-xl overflow-hidden border border-slate-200 shadow-sm max-w-4xl mb-4 bg-white">
                <img src="{{ asset('images/manual/step3b_kontak_pic.png') }}" alt="Foto 1.4: Tangkapan Layar Asli Area Formulir Daftar Kontak Narahubung (PIC Customer)" class="w-full h-auto">
            </div>
            <div class="flex items-center justify-between text-xs text-slate-500 mb-3 max-w-4xl px-1">
                <span>Foto 1.4: Tangkapan Layar Asli Area Formulir Daftar Kontak Narahubung (PIC Customer)</span>
                <span class="font-mono bg-slate-100 px-2 py-0.5 rounded">Bagian: Daftar Kontak Person</span>
            </div>
            <div class="overflow-x-auto max-w-4xl mb-4">
                <table class="data-table text-sm">
                    <thead>
                        <tr>
                            <th class="w-64">Kolom Formulir PIC</th>
                            <th>Rincian Informasi yang Harus Diisi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="font-bold">Nama PIC *</td>
                            <td>Ketik nama lengkap pejabat atau narahubung yang Anda hubungi di perusahaan klien (contoh: <code>Bpk. Budi Santoso</code>). Wajib diisi minimal 1 kontak.</td>
                        </tr>
                        <tr>
                            <td class="font-bold">Jabatan</td>
                            <td>Ketik posisi resmi PIC tersebut (contoh: <code>Purchasing Manager</code>, <code>Direktur Operasional</code>, <code>IT Supervisor</code>, atau <code>General Affairs</code>).</td>
                        </tr>
                        <tr>
                            <td class="font-bold">WhatsApp / HP</td>
                            <td>Ketik nomor WhatsApp aktif PIC untuk jalur komunikasi cepat (contoh: <code>0812-9876-5432</code>). Nomor ini sangat penting untuk pengiriman dokumen via chat.</td>
                        </tr>
                        <tr>
                            <td class="font-bold">Email & Divisi</td>
                            <td>Ketik alamat email resmi PIC (contoh: <code>budi.santoso@karyabangsa.co.id</code>) dan divisinya (contoh: <code>Procurement / Pengadaan</code> atau <code>IT Infrastructure</code>).</td>
                        </tr>
                        <tr>
                            <td class="font-bold">Checkbox "Kontak Utama"</td>
                            <td>Centang kotak <strong>Kontak Utama</strong> pada narahubung yang paling sering dihubungi agar otomatis terpilih saat Sales membuat permintaan penawaran (RFQ).</td>
                        </tr>
                        <tr>
                            <td class="font-bold">Tombol "+ Tambah Kontak"</td>
                            <td>Klik tombol warna biru muda di kanan atas tabel kontak untuk menambah narahubung ke-2, ke-3, dst. pada perusahaan yang sama tanpa batasan jumlah.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 1.5 Buat RFQ & 1.6 Modal Tambah PIC Instan -->
        <div class="mb-8 pt-6 border-t border-slate-100">
            <h4 class="text-base font-bold text-slate-800 mb-2">1.5 Membuat Permintaan Penawaran Harga (RFQ) Baru & Fitur Pop-Up Tambah PIC Instan</h4>
            <p class="text-sm text-slate-600 mb-4">
                Setelah customer terdaftar, masukkan daftar barang yang diminta pembeli melalui menu: <strong>RFQ ➔ + Buat RFQ</strong>. Jika kontak narahubung belum terdaftar, gunakan fitur spesial pop-up PIC instan tanpa perlu keluar dari form!
            </p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-w-4xl mb-4">
                <div class="rounded-xl overflow-hidden border border-slate-200 shadow-sm bg-white">
                    <img src="{{ asset('images/manual/step4_buat_rfq.png') }}" alt="Formulir Buat RFQ Asli" class="w-full h-auto">
                    <div class="p-2 text-xs text-slate-500 bg-slate-50 border-t border-slate-200">Foto 1.5: Formulir Buat RFQ</div>
                </div>
                <div class="rounded-xl overflow-hidden border border-slate-200 shadow-sm bg-white">
                    <img src="{{ asset('images/manual/step4b_inline_pic_modal.png') }}" alt="Modal Tambah PIC Instan" class="w-full h-auto">
                    <div class="p-2 text-xs text-slate-500 bg-slate-50 border-t border-slate-200">Foto 1.6: Pop-up Tambah PIC Instan (AJAX)</div>
                </div>
            </div>
            <div class="overflow-x-auto max-w-4xl mb-4">
                <table class="data-table text-sm">
                    <thead>
                        <tr>
                            <th class="w-64">Bagian Formulir</th>
                            <th>Cara Pengisian & Fitur Spesial untuk Sales</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="font-bold">Customer *</td>
                            <td>Klik kotak "Pilih Customer", lalu ketik nama atau kode perusahaan yang telah Anda daftarkan sebelumnya.</td>
                        </tr>
                        <tr>
                            <td class="font-bold">PIC Tujuan *</td>
                            <td>Pilih narahubung pembeli pada dropdown. Kontak utama otomatis terpilih sebagai penerima surat penawaran.</td>
                        </tr>
                        <tr>
                            <td class="font-bold">Fitur "+ Tambah PIC" (Instan)</td>
                            <td>Jika kontak yang diinginkan belum ada di sistem, klik tombol biru <strong>+ Tambah PIC</strong> (Foto 1.6). Pop-up modal instan akan muncul: ketik Nama, Jabatan, WA, dan Email, lalu klik <strong>"Simpan PIC"</strong>. Data langsung tersimpan dan seketika terpilih di form RFQ tanpa perlu refresh halaman!</td>
                        </tr>
                        <tr>
                            <td class="font-bold">Fitur "Edit PIC"</td>
                            <td>Jika nomor WhatsApp atau jabatan PIC tujuan berganti, klik tombol kuning <strong>Edit PIC</strong> untuk memperbarui data narahubung saat itu juga.</td>
                        </tr>
                        <tr>
                            <td class="font-bold">Tipe RFQ *</td>
                            <td>Pilih radio button: <strong>Non Projek</strong> (untuk pengadaan barang umum/ritel) atau <strong>Projek</strong> (untuk paket proyek pengadaan besar).</td>
                        </tr>
                        <tr>
                            <td class="font-bold">Tombol "Simpan RFQ"</td>
                            <td>Klik tombol warna <span class="px-2 py-0.5 rounded text-xs font-bold text-white bg-blue-600">✓ Simpan RFQ</span> di panel kanan atas. RFQ berhasil dibuat dan status otomatis menjadi <span class="px-2 py-0.5 rounded text-xs font-bold bg-amber-100 text-amber-800">PENDING ADMIN</span>.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 1.7 RFQ Projek -->
        <div id="projek-section" class="mb-8 pt-6 border-t border-slate-100 scroll-mt-20">
            <h4 class="text-base font-bold text-slate-800 mb-2">1.6 RFQ Projek (3 Blok Kategori: Hardware, Jasa Pemasangan, Material Support)</h4>
            <p class="text-sm text-slate-600 mb-4">
                Untuk pengadaan paket proyek skala besar (instalasi jaringan, data center, server rack, CCTV pabrik, atau sistem otomatisasi), pilih opsi <strong>🏢 Projek</strong> pada formulir RFQ.
            </p>
            <div class="rounded-xl overflow-hidden border border-slate-200 shadow-sm max-w-4xl mb-4 bg-white">
                <img src="{{ asset('images/manual/step4c_rfq_projek.png') }}" alt="Formulir RFQ Projek 3 Kategori" class="w-full h-auto">
            </div>
            <div class="flex items-center justify-between text-xs text-slate-500 mb-3 max-w-4xl px-1">
                <span>Foto 1.7: Tangkapan Layar Asli Detail Item RFQ Projek dengan 3 Blok Kategori Terstruktur</span>
                <span class="font-mono bg-slate-100 px-2 py-0.5 rounded">Kategori: Hardware, Jasa Pemasangan, Material Support</span>
            </div>
            <div class="overflow-x-auto max-w-4xl mb-4">
                <table class="data-table text-sm">
                    <thead>
                        <tr>
                            <th class="w-64">Blok Kategori Projek</th>
                            <th>Penjelasan, Contoh Barang & Cara Input</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="font-bold">I. HARDWARE</td>
                            <td><strong>Perangkat Keras Utama:</strong> Klik tombol biru <code>+ Hardware</code> untuk memasukkan peralatan utama (contoh: Server Rackmount 2U, Switch Cisco 24-Port PoE, Kamera CCTV Hikvision IP Dome, Router Mikrotik Cloud Core, Unit UPS 3000VA).</td>
                        </tr>
                        <tr>
                            <td class="font-bold">II. JASA PEMASANGAN</td>
                            <td><strong>Tenaga Kerja Teknisi Lapangan:</strong> Klik tombol hijau <code>+ Jasa Pemasangan</code> untuk memasukkan jasa instalasi teknis (contoh: Jasa Penarikan Kabel UTP per titik, Jasa Terminasi & Testing Fluke, Jasa Konfigurasi VLAN & Firewall, Jasa Pemasangan Bracket & Tray).</td>
                        </tr>
                        <tr>
                            <td class="font-bold">III. MATERIAL SUPPORT</td>
                            <td><strong>Material Bantu Habis Pakai:</strong> Klik tombol oranye <code>+ Material Support</code> untuk material pendukung instalasi (contoh: Kabel LAN Belden Cat6, Pipa Konduit Clipsal 20mm, Modular Jack RJ45, Patch Cord 1m & 3m, Velcro Cable Tie, Dynabolt).</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 1.8 Unduh Surat Penawaran -->
        <div class="mb-8 pt-6 border-t border-slate-100">
            <h4 class="text-base font-bold text-slate-800 mb-2">1.7 Mengunduh Surat Penawaran Resmi (Quotation PDF) & Fitur Revisi QTY</h4>
            <p class="text-sm text-slate-600 mb-4">
                Ketika penawaran telah disetujui Leader, status RFQ berubah menjadi <span class="px-2.5 py-1 rounded text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">APPROVED</span>. Surat resmi penawaran harga siap diunduh dan dikirim ke pembeli.
            </p>
            <div class="rounded-xl overflow-hidden border border-slate-200 shadow-sm max-w-4xl mb-4 bg-white">
                <img src="{{ asset('images/manual/step7_unduh_quotation.png') }}" alt="Foto 1.8: Tangkapan Layar Asli Halaman Detail RFQ Disetujui (Approved) & Tombol Aksi" class="w-full h-auto">
            </div>
            <div class="flex items-center justify-between text-xs text-slate-500 mb-3 max-w-4xl px-1">
                <span>Foto 1.8: Tangkapan Layar Asli Halaman Detail RFQ Disetujui (Approved) & Tombol Aksi</span>
                <span class="font-mono bg-slate-100 px-2 py-0.5 rounded">URL: /rfqs/{id}</span>
            </div>
            <div class="overflow-x-auto max-w-4xl mb-4">
                <table class="data-table text-sm">
                    <thead>
                        <tr>
                            <th class="w-64">Tombol di Layar</th>
                            <th>Fungsi & Petunjuk Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="font-bold"><span class="px-2 py-0.5 rounded text-xs font-bold text-white bg-blue-600">👁 Web Preview</span></td>
                            <td>Membuka pratinjau tampilan surat resmi penawaran di tab baru browser untuk Anda tinjau kembali sebelum dikirim.</td>
                        </tr>
                        <tr>
                            <td class="font-bold"><span class="px-2 py-0.5 rounded text-xs font-bold text-white bg-amber-600">📥 Download PDF</span></td>
                            <td>Mengunduh berkas PDF resmi penawaran harga ke komputer Anda. File ini <strong>sudah otomatis lengkap dengan kop surat PT. Pedia Technology Indonesia, tanda tangan digital Anda, dan stempel dinamis perusahaan</strong> tanpa perlu print-scan manual!</td>
                        </tr>
                        <tr>
                            <td class="font-bold"><span class="px-2 py-0.5 rounded text-xs font-bold text-white bg-emerald-600">🔔 Mark Quotation Sent...</span></td>
                            <td>Klik tombol hijau ini setelah Anda selesai mengirimkan surat penawaran ke pembeli via WhatsApp atau Email agar sistem mencatat status penawaran telah terkirim (<span class="px-2 py-0.5 rounded text-xs font-bold bg-cyan-100 text-cyan-800">QUOTATION SENT</span>).</td>
                        </tr>
                        <tr>
                            <td class="font-bold"><span class="px-2 py-0.5 rounded text-xs font-semibold bg-slate-200 text-slate-700">✏️ Revisi QTY</span></td>
                            <td>Gunakan tombol ini bila pembeli ingin menambah atau mengurangi jumlah order barang tanpa harus mengulang pembuatan RFQ dari awal. Sistem otomatis mengalikan kuantiti baru dengan harga satuan yang telah disetujui.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 1.9 Upload Bukti PO & Dasbor Sales -->
        <div class="pt-6 border-t border-slate-100">
            <h4 class="text-base font-bold text-slate-800 mb-2">1.8 Mengunggah Dokumen Purchase Order (PO) Customer & Dasbor Sales</h4>
            <p class="text-sm text-slate-600 mb-4">
                Kabar gembira! Pembeli setuju dan menerbitkan lembar Purchase Order (PO) resmi. Buka nomor RFQ tersebut, lalu gulir ke bawah ke bagian <strong>"Upload PO Customer (Menjadi GOAL)"</strong>.
            </p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-w-4xl mb-4">
                <div class="rounded-xl overflow-hidden border border-slate-200 shadow-sm bg-white">
                    <img src="{{ asset('images/manual/step8_upload_po.png') }}" alt="Form Upload PO" class="w-full h-auto">
                    <div class="p-2 text-xs text-slate-500 bg-slate-50 border-t border-slate-200">Foto 1.9: Form Upload Berkas PO Customer</div>
                </div>
                <div class="rounded-xl overflow-hidden border border-slate-200 shadow-sm bg-white">
                    <img src="{{ asset('images/manual/step0_dashboard_sales.png') }}" alt="Dasbor Sales" class="w-full h-auto">
                    <div class="p-2 text-xs text-slate-500 bg-slate-50 border-t border-slate-200">Foto 1.10: Dasbor Sales & Target Bulanan</div>
                </div>
            </div>
            <div class="overflow-x-auto max-w-4xl mb-4">
                <table class="data-table text-sm">
                    <thead>
                        <tr>
                            <th class="w-64">Elemen Formulir / Dasbor</th>
                            <th>Instruksi Pengisian & Pemantauan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="font-bold">Pilih Berkas (Choose File)</td>
                            <td>Klik tombol <strong>"Choose File"</strong>, lalu pilih file berkas PDF atau foto hasil scan dokumen PO dari pembeli di komputer Anda.</td>
                        </tr>
                        <tr>
                            <td class="font-bold">Tombol "Upload PO"</td>
                            <td>Klik tombol warna <span class="px-2 py-0.5 rounded text-xs font-bold text-white bg-blue-600">⬆ Upload PO</span> di bawahnya. Berkas PO akan tersimpan dan status transaksi otomatis beralih menjadi <span class="px-2.5 py-1 rounded text-xs font-bold bg-purple-100 text-purple-800">PO RECEIVED (PENDING LEADER)</span> untuk pengesahan akhir pimpinan.</td>
                        </tr>
                        <tr>
                            <td class="font-bold">Dasbor Penjualan Sales</td>
                            <td>Pantau penawaran aktif dan target omzet bulanan di menu <strong>Dashboard</strong>. Begitu PO disetujui Leader, omzet otomatis masuk ke pencapaian target sales Anda.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    {{-- ═════════════════════════════════════════════════════════════
         BAGIAN 3: PANDUAN TIM ADMIN
         ═════════════════════════════════════════════════════════════ --}}
    <section id="admin" class="card p-6 mb-8 scroll-mt-20">
        <div class="flex items-center gap-3 mb-6 border-b pb-3" style="border-color: var(--border-color);">
            <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-amber-500 text-white font-bold">3</div>
            <div>
                <h3 class="text-lg font-bold" style="color: var(--text-primary);">BAB 2: Panduan Lengkap untuk Tim Admin (Purchasing & Operasional)</h3>
                <p class="text-xs" style="color: var(--text-muted);">Menerima antrean RFQ, memilih vendor, menetapkan HPP, menghitung ongkir, dan mengajukan margin ke Leader.</p>
            </div>
        </div>

        <div class="mb-8">
            <h4 class="text-base font-bold text-slate-800 mb-2">2.1 Tugas Pokok & Cara Mengisi Formulir Kalkulasi HPP & Penawaran</h4>
            <p class="text-sm text-slate-600 mb-4">
                Buka menu <strong>RFQ</strong>, pilih baris dengan status <span class="px-2 py-0.5 rounded text-xs font-bold bg-amber-100 text-amber-800">PENDING ADMIN</span>, lalu klik tombol <strong>"Isi HPP"</strong> atau <strong>"Kalkulasi HPP"</strong>.
            </p>
            <div class="rounded-xl overflow-hidden border border-slate-200 shadow-sm max-w-4xl mb-4 bg-white">
                <img src="{{ asset('images/manual/step5_admin_hpp.png') }}" alt="Foto 2.1: Tangkapan Layar Asli Formulir Penetapan Vendor, Harga Modal (HPP), dan Margin" class="w-full h-auto">
            </div>
            <div class="flex items-center justify-between text-xs text-slate-500 mb-3 max-w-4xl px-1">
                <span>Foto 2.1: Tangkapan Layar Asli Formulir Penetapan Vendor, Harga Modal (HPP), dan Margin</span>
                <span class="font-mono bg-slate-100 px-2 py-0.5 rounded">URL: /rfqs/{id}/price</span>
            </div>
            <div class="overflow-x-auto max-w-4xl mb-4">
                <table class="data-table text-sm">
                    <thead>
                        <tr>
                            <th class="w-64">Bagian di Layar</th>
                            <th>Petunjuk Pengisian untuk Tim Admin</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="font-bold">1. DESKRIPSI & VENDOR</td>
                            <td>Pilih nama rekanan penyedia barang pada kotak <strong>Vendor Supplier</strong> (contoh: distributor resmi atau rekanan terdaftar). Jika ada banyak item barang dari vendor yang berbeda, Anda bisa memilih vendor yang berbeda pada tiap baris barang.</td>
                        </tr>
                        <tr>
                            <td class="font-bold">2. BIAYA MODAL (HPP)</td>
                            <td>
                                <ul class="list-disc list-inside space-y-1 text-xs">
                                    <li><strong>HPP Dasar / Modal Satuan *:</strong> Ketik angka harga beli dari vendor (tanpa titik).</li>
                                    <li><strong>Ongkir ke Pedia (Inbound):</strong> Isi biaya kirim dari vendor ke kantor bila ada.</li>
                                    <li><strong>Ongkir dari Pedia ke Customer (Outbound):</strong> Isi ongkos kirim ke lokasi pembeli.</li>
                                </ul>
                                <span class="text-xs text-slate-500 mt-1 block">Sistem otomatis menghitung <strong>TOTAL MODAL SATUAN</strong> secara tepat.</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="font-bold">3. MARGIN, CEILING & PENAWARAN</td>
                            <td>
                                <ul class="list-disc list-inside space-y-1 text-xs">
                                    <li><strong>Tipe & Nilai Margin:</strong> Masukkan persentase laba kotor yang ditargetkan (misal: ketik 15 untuk 15%). Sistem langsung menghitung laba per unit (PROFIT).</li>
                                    <li><strong>Pembulatan (Ceiling):</strong> Klik salah satu tombol pembulatan 1, 1k, 10k, atau 50k agar harga penawaran bulat dan profesional.</li>
                                    <li>Kotak hitam <strong>HARGA JUAL KLIEN</strong> langsung menampilkan angka penawaran final per unit.</li>
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <td class="font-bold">Catatan Khusus Penawaran (Quotation Note)</td>
                            <td>Ketik klausul syarat penawaran di kotak ini (misal garansi resmi 1 tahun, stok terbatas tidak mengikat). Catatan ini bersifat fleksibel; jika diisi otomatis tercetak di surat quotation, jika kosong otomatis disembunyikan.</td>
                        </tr>
                        <tr>
                            <td class="font-bold">Tombol "Submit HPP ke Leader"</td>
                            <td>Periksa ringkasan di bar bawah: Total Modal, Estimasi Laba, dan Grand Total Jual. Jika sudah sesuai, klik tombol warna <span class="px-2 py-0.5 rounded text-xs font-bold text-white bg-blue-600">✓ Submit HPP ke Leader</span> untuk meneruskan ke meja pimpinan.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="p-3.5 rounded-xl bg-amber-50 border border-amber-200 text-xs text-amber-900 mt-4">
                <strong>⚠️ Perhatian untuk Admin:</strong> Pastikan seluruh baris item barang telah diisi harga modalnya sebelum menekan tombol submit agar pimpinan dapat melihat kalkulasi laba secara utuh.
            </div>
        </div>

        <!-- 2.2 Vendor Master & Portal Mainpower -->
        <div id="vendor-section" class="pt-6 border-t border-slate-100 scroll-mt-20">
            <h4 class="text-base font-bold text-slate-800 mb-2">2.2 Manajemen Master Rekanan Vendor & Portal Standar Mainpower</h4>
            <p class="text-sm text-slate-600 mb-4">
                Admin mengelola direktori supplier rekanan di menu <strong>Vendor</strong> dan standarisasi tarif tenaga kerja teknisi di menu <strong>Portal Mainpower</strong>.
            </p>
            <div class="rounded-xl overflow-hidden border border-slate-200 shadow-sm max-w-4xl mb-4 bg-white">
                <img src="{{ asset('images/manual/step5b_vendor.png') }}" alt="Foto 2.2: Tangkapan Layar Asli Direktori Master Vendor & Supplier Rekanan" class="w-full h-auto">
            </div>
            <div class="flex items-center justify-between text-xs text-slate-500 mb-3 max-w-4xl px-1">
                <span>Foto 2.2: Tangkapan Layar Asli Direktori Master Vendor & Supplier Rekanan</span>
                <span class="font-mono bg-slate-100 px-2 py-0.5 rounded">URL: /vendors</span>
            </div>
            <div class="overflow-x-auto max-w-4xl mb-4">
                <table class="data-table text-sm">
                    <thead>
                        <tr>
                            <th class="w-64">Data & Fitur</th>
                            <th>Penjelasan Fungsi & Pengisian</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="font-bold">Master Vendor & Supplier</td>
                            <td>Daftarkan nama supplier, kategori produk (Hardware, CCTV, Kabel Jaringan, dsb), nama PIC rekanan, nomor WhatsApp/telepon, serta rekening bank untuk pembayaran.</td>
                        </tr>
                        <tr>
                            <td class="font-bold">Portal Standar Mainpower</td>
                            <td>Menetapkan tarif baku tenaga kerja teknisi: Upah Pokok Harian (MP), Uang Makan, BPJS Kesehatan & Ketenagakerjaan (%), serta Tarif Lembur per Jam. Standar ini menjadi acuan biaya HPP jasa instalasi proyek.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    {{-- ═════════════════════════════════════════════════════════════
         BAGIAN 4: PANDUAN LEADER / PIMPINAN
         ═════════════════════════════════════════════════════════════ --}}
    <section id="leader" class="card p-6 mb-8 scroll-mt-20">
        <div class="flex items-center gap-3 mb-6 border-b pb-3" style="border-color: var(--border-color);">
            <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-emerald-600 text-white font-bold">4</div>
            <div>
                <h3 class="text-lg font-bold" style="color: var(--text-primary);">BAB 3: Panduan Lengkap untuk Leader / Pimpinan</h3>
                <p class="text-xs" style="color: var(--text-muted);">Persetujuan margin keuntungan, wewenang backup pengisian HPP, Approval Center, dan Revenue Analytics.</p>
            </div>
        </div>

        <!-- 3.1 Approval Margin -->
        <div class="mb-8">
            <h4 class="text-base font-bold text-slate-800 mb-2">3.1 Meninjau & Menyetujui Margin Penawaran</h4>
            <p class="text-sm text-slate-600 mb-4">
                Leader adalah penentu kelayakan keuntungan perusahaan. Saat ada RFQ dengan status <span class="px-2 py-0.5 rounded text-xs font-bold bg-amber-100 text-amber-800">PENDING LEADER</span>, pimpinan membuka detail RFQ untuk meninjau kalkulasi biaya.
            </p>
            <div class="rounded-xl overflow-hidden border border-slate-200 shadow-sm max-w-4xl mb-4 bg-white">
                <img src="{{ asset('images/manual/step6_leader_approval.png') }}" alt="Foto 3.1: Tangkapan Layar Asli Tinjauan Keuntungan dan Tombol Persetujuan Leader" class="w-full h-auto">
            </div>
            <div class="flex items-center justify-between text-xs text-slate-500 mb-3 max-w-4xl px-1">
                <span>Foto 3.1: Tangkapan Layar Asli Tinjauan Keuntungan dan Tombol Persetujuan Leader</span>
                <span class="font-mono bg-slate-100 px-2 py-0.5 rounded">URL: /rfqs/{id}</span>
            </div>
            <div class="overflow-x-auto max-w-4xl mb-4">
                <table class="data-table text-sm">
                    <thead>
                        <tr>
                            <th class="w-64">Tombol Aksi Pimpinan</th>
                            <th>Fungsi & Petunjuk Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="font-bold"><span class="px-2 py-0.5 rounded text-xs font-bold text-white bg-emerald-600">✓ Approve HPP</span></td>
                            <td>Klik tombol warna <strong>HIJAU</strong> ini jika persentase margin keuntungan sudah layak. Surat penawaran resmi langsung diterbitkan (<span class="px-2 py-0.5 rounded text-xs font-bold bg-emerald-100 text-emerald-800">APPROVED</span>) dan Sales langsung dapat mengunduh berkas PDF.</td>
                        </tr>
                        <tr>
                            <td class="font-bold"><span class="px-2 py-0.5 rounded text-xs font-bold text-white bg-red-600">✕ Reject / Minta Revisi</span></td>
                            <td>Klik tombol warna <strong>MERAH</strong> ini jika margin dirasa terlalu tipis atau modal vendor masih bisa dinegosiasikan. Jendela popup akan muncul: ketik arahan revisi Anda pada kolom <em>"Alasan / Catatan Revisi"</em> lalu klik "Kirim & Minta Revisi". RFQ kembali ke meja Admin.</td>
                        </tr>
                        <tr>
                            <td class="font-bold"><span class="px-2 py-0.5 rounded text-xs font-bold text-white bg-blue-600">Edit / Sesuaikan HPP</span></td>
                            <td>Pimpinan dapat mengklik tombol biru ini untuk mengubah sendiri persentase margin atau harga modal vendor secara langsung tanpa harus melempar revisi ke Admin.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="p-3.5 rounded-xl bg-blue-50 border border-blue-200 text-xs text-blue-900 mt-4">
                <strong>💡 Wewenang Khusus Backup Leader:</strong> Bila staf Admin berhalangan masuk (cuti/sakit), Bapak/Ibu Leader memiliki wewenang langsung membuka RFQ yang masih berstatus <em>Pending Admin</em> melalui tombol hijau tosca di kanan atas bertuliskan <strong>"Edit HPP / Sesuaikan Harga"</strong>, mengisi modal vendor sendiri, dan menyimpannya langsung dengan tombol <strong>"Simpan & Sesuaikan HPP (Leader)"</strong>.
            </div>
        </div>

        <!-- 3.2 Approval Hub & GOAL -->
        <div class="mb-8 pt-6 border-t border-slate-100">
            <h4 class="text-base font-bold text-slate-800 mb-2">3.2 Pusat Persetujuan Terpadu (Approval Center) & Pengesahan Akhir GOAL</h4>
            <p class="text-sm text-slate-600 mb-4">
                Pimpinan dapat mengawasi semua antrean persetujuan (Customer Baru, RFQ Pending Leader, dan PO Klien) dari satu pintu melalui menu <strong>Approvals</strong>.
            </p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-w-4xl mb-4">
                <div class="rounded-xl overflow-hidden border border-slate-200 shadow-sm bg-white">
                    <img src="{{ asset('images/manual/step6b_approvals_hub.png') }}" alt="Approval Center" class="w-full h-auto">
                    <div class="p-2 text-xs text-slate-500 bg-slate-50 border-t border-slate-200">Foto 3.2: Approval Center Terpadu</div>
                </div>
                <div class="rounded-xl overflow-hidden border border-slate-200 shadow-sm bg-white">
                    <img src="{{ asset('images/manual/step9_leader_goal.png') }}" alt="Pengesahan GOAL" class="w-full h-auto">
                    <div class="p-2 text-xs text-slate-500 bg-slate-50 border-t border-slate-200">Foto 3.3: Layar Persetujuan GOAL</div>
                </div>
            </div>
            <div class="overflow-x-auto max-w-4xl mb-4">
                <table class="data-table text-sm">
                    <thead>
                        <tr>
                            <th class="w-64">Elemen di Layar</th>
                            <th>Instruksi Tindakan Pimpinan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="font-bold">Klien Menunggu Approval</td>
                            <td>Daftar calon customer baru yang didaftarkan sales. Pimpinan memeriksa kelengkapan data lalu klik tombol <span class="px-2 py-0.5 rounded text-xs font-bold text-white bg-emerald-600">✓ Approve</span> untuk mengaktifkan status customer.</td>
                        </tr>
                        <tr>
                            <td class="font-bold">Pemeriksaan Berkas PO Klien</td>
                            <td>Klik dokumen file PO pembeli yang terlampir untuk memastikan kesesuaian nilai nominal transaksi, item barang yang dipesan, serta tanda tangan pemesan.</td>
                        </tr>
                        <tr>
                            <td class="font-bold">Tombol "✓ Approve Menjadi GOAL"</td>
                            <td>Klik tombol warna <span class="px-2 py-0.5 rounded text-xs font-bold text-white bg-blue-600">✓ Approve Menjadi GOAL</span>. Transaksi resmi dinyatakan sah (<span class="px-2 py-0.5 rounded text-xs font-bold bg-emerald-100 text-emerald-800">GOAL</span>)! Nilai penjualan otomatis langsung tercatat ke dalam omzet dan pencapaian target bulanan sales yang bersangkutan.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 3.3 Revenue Analytics -->
        <div id="analytics-section" class="pt-6 border-t border-slate-100 scroll-mt-20">
            <h4 class="text-base font-bold text-slate-800 mb-2">3.3 Revenue Analytics & Pemantauan Target Omzet Bulanan</h4>
            <p class="text-sm text-slate-600 mb-4">
                Pimpinan dapat memantau akumulasi omzet, tren kenaikan pendapatan bulanan, serta leaderboard pencapaian target sales di menu <strong>Revenue</strong>.
            </p>
            <div class="rounded-xl overflow-hidden border border-slate-200 shadow-sm max-w-4xl mb-4 bg-white">
                <img src="{{ asset('images/manual/step0b_analytics.png') }}" alt="Foto 3.4: Tangkapan Layar Asli Dasbor Analytics & Laporan Finansial" class="w-full h-auto">
            </div>
            <div class="flex items-center justify-between text-xs text-slate-500 mb-3 max-w-4xl px-1">
                <span>Foto 3.4: Tangkapan Layar Asli Dasbor Analytics & Laporan Finansial PT. Pedia Technology Indonesia</span>
                <span class="font-mono bg-slate-100 px-2 py-0.5 rounded">URL: /analytics</span>
            </div>
            <div class="overflow-x-auto max-w-4xl mb-4">
                <table class="data-table text-sm">
                    <thead>
                        <tr>
                            <th class="w-64">Metrik Finansial</th>
                            <th>Penjelasan & Indikator Kinerja</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="font-bold">TOTAL REVENUE & BULAN INI</td>
                            <td>Akumulasi omzet transaksi berstatus GOAL sepanjang masa dan performa penjualan di bulan berjalan.</td>
                        </tr>
                        <tr>
                            <td class="font-bold">AVG. ORDER VALUE (AOV)</td>
                            <td>Rata-rata nilai per transaksi closing untuk memantau peningkatan skala nilai pesanan pembeli.</td>
                        </tr>
                        <tr>
                            <td class="font-bold">Performa Sales Marketing</td>
                            <td>Papan peringkat yang menampilkan persentase pencapaian masing-masing staf sales terhadap target bulanan yang telah ditetapkan manajemen.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    {{-- ═════════════════════════════════════════════════════════════
         BAGIAN 5: TANYA JAWAB (FAQ) & MANAJEMEN USER
         ═════════════════════════════════════════════════════════════ --}}
    <section id="faq" class="card p-6 mb-8 scroll-mt-20">
        <div class="flex items-center gap-3 mb-6 border-b pb-3" style="border-color: var(--border-color);">
            <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-purple-600 text-white font-bold">5</div>
            <div>
                <h3 class="text-lg font-bold" style="color: var(--text-primary);">BAB 4: Manajemen Pengguna & Tanya Jawab Sering Terjadi (FAQ)</h3>
                <p class="text-xs" style="color: var(--text-muted);">Pengaturan akun karyawan, migrasi klien sales resign, serta solusi cepat untuk kendala sehari-hari.</p>
            </div>
        </div>

        <div class="mb-8">
            <h4 class="text-base font-bold text-slate-800 mb-2">4.1 Fitur Migrasi Database Pelanggan (Bila Sales Resign)</h4>
            <p class="text-sm text-slate-600 mb-3">
                Pada menu <strong>User Management</strong>, Admin atau Leader dapat membuka akun sales yang berhenti bekerja, lalu mengklik tombol <strong>"Migrasi Customer"</strong> untuk memindahkan seluruh data klien dan riwayat transaksinya ke sales baru dalam 1 kali klik tanpa risiko data hilang.
            </p>
        </div>

        <h4 class="text-base font-bold text-slate-800 mb-3">4.2 Pertanyaan yang Sering Diajukan (FAQ)</h4>
        <div class="space-y-4 max-w-4xl">
            <div class="p-4 rounded-xl border border-blue-200 bg-blue-50/40">
                <h4 class="font-bold text-sm text-blue-900 mb-1">❓ 1. Bagaimana jika saya lupa kata sandi (password) saat ingin login?</h4>
                <p class="text-xs text-blue-800 leading-relaxed">
                    Bapak/Ibu tidak perlu cemas. Anda dapat mengklik tautan <em>"Forgot password?"</em> di atas kotak sandi login, atau segera hubungi Tim Administrator CRM kantor. Akun Anda dapat dibantu reset kata sandi baru dalam waktu kurang dari 1 menit.
                </p>
            </div>

            <div class="p-4 rounded-xl border border-amber-200 bg-amber-50/40">
                <h4 class="font-bold text-sm text-amber-900 mb-1">❓ 2. Kenapa tanda tangan saya belum keluar di lembar penawaran PDF?</h4>
                <p class="text-xs text-amber-800 leading-relaxed">
                    Pastikan Bapak/Ibu sudah menyimpan tanda tangan dan jabatan di menu <strong>Profile</strong> (lihat Bab 1.2 di atas). Klik tombol <em>"✓ Simpan Perubahan Identitas & TTD"</em>. Setelah tersimpan, unduh ulang file surat penawarannya di halaman detail RFQ agar tanda tangan terbaru otomatis menempel rapi.
                </p>
            </div>

            <div class="p-4 rounded-xl border border-emerald-200 bg-emerald-50/40">
                <h4 class="font-bold text-sm text-emerald-900 mb-1">❓ 3. Klien meminta perubahan jumlah barang (Qty) setelah surat dikirim, apa solusinya?</h4>
                <p class="text-xs text-emerald-800 leading-relaxed">
                    Sales tidak perlu membuat RFQ dari nol! Cukup buka halaman detail nomor RFQ tersebut, lalu klik tombol warna abu-abu bertuliskan <strong>"✏️ Revisi QTY"</strong>. Masukkan jumlah pesanan yang baru, sistem otomatis mengalikan dengan harga satuan yang sudah disetujui sebelumnya.
                </p>
            </div>

            <div class="p-4 rounded-xl border border-indigo-200 bg-indigo-50/40">
                <h4 class="font-bold text-sm text-indigo-900 mb-1">❓ 4. Klien meminta potongan harga khusus / negosiasi ulang, apa yang harus dilakukan?</h4>
                <p class="text-xs text-indigo-800 leading-relaxed">
                    Hubungi Leader untuk membuka detail RFQ tersebut lalu klik tombol <strong>"Edit / Sesuaikan HPP"</strong> atau <strong>"✕ Reject / Minta Revisi"</strong>. Status penawaran akan disesuaikan kembali sehingga margin keuntungan dapat disepakati ulang dengan persetujuan pimpinan.
                </p>
            </div>

            <div class="p-4 rounded-xl border border-slate-200 bg-slate-50">
                <h4 class="font-bold text-sm text-slate-800 mb-1">❓ 5. Apakah sistem CRM ini bisa dibuka lewat HP atau Tablet?</h4>
                <p class="text-xs text-slate-600 leading-relaxed">
                    Bisa sekali. Tampilan CRM Analyst sudah responsif (<em>mobile-friendly</em>) sehingga tetap rapi, nyaman dibaca, dan mudah dipencet saat dibuka melalui Google Chrome atau Safari di Handphone maupun iPad/Tablet.
                </p>
            </div>
        </div>

        <!-- Help Desk Info -->
        <div class="mt-8 p-5 rounded-xl border border-blue-200 bg-blue-50/70 max-w-4xl">
            <h4 class="text-sm font-bold text-blue-900 mb-2">📞 Layanan Bantuan & Dukungan Pengguna</h4>
            <p class="text-xs text-blue-700 mb-3">Jika Bapak/Ibu mengalami kendala teknis atau ada langkah yang kurang jelas, silakan hubungi tim pendukung internal kami:</p>
            <div class="grid md:grid-cols-2 gap-3 text-xs text-blue-800">
                <div><strong>Perusahaan:</strong> PT. Pedia Technology Indonesia</div>
                <div><strong>Narahubung Teknis:</strong> Tim Administrator CRM & IT Support</div>
                <div><strong>Nomor Telepon Kantor:</strong> 021-3971-2155</div>
                <div><strong>Alamat Email Bantuan:</strong> support@pedia-technology.co.id</div>
                <div><strong>Jam Operasional Layanan:</strong> Senin – Jumat, Pukul 08.30 – 17.30 WIB</div>
            </div>
        </div>
    </section>

</x-app-layout>
