<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Buku Panduan Pengguna CRM Analyst - PT. Pedia Technology Indonesia</title>
    <style>
        @page {
            margin: 10mm 12mm 12mm 12mm;
            size: a4 portrait;
        }
        body {
            font-family: 'DejaVu Sans', 'Calibri', 'Helvetica', 'Arial', sans-serif;
            font-size: 9pt;
            color: #1e293b;
            line-height: 1.45;
            margin: 0;
            padding: 0;
        }
        .page-break {
            page-break-after: always;
        }
        .no-break {
            page-break-inside: avoid;
        }

        /* Header & Footer */
        .doc-header {
            width: 100%;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 6px;
            margin-bottom: 10px;
        }
        .doc-footer {
            position: fixed;
            bottom: 0px;
            left: 0px;
            right: 0px;
            height: 18px;
            font-size: 8pt;
            color: #64748b;
            border-top: 1px solid #cbd5e1;
            padding-top: 4px;
            text-align: center;
        }

        /* Typography */
        h1, h2, h3, h4 {
            color: #0f172a;
            margin-top: 0;
            margin-bottom: 6px;
            font-weight: bold;
        }
        h1 { font-size: 16pt; }
        h2 { font-size: 13pt; color: #1e40af; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; margin-top: 10px; }
        h3 { font-size: 11pt; color: #0f172a; margin-top: 8px; }
        p { margin-top: 0; margin-bottom: 6px; }

        /* Tables */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        th, td {
            padding: 5px 7px;
            text-align: left;
            vertical-align: top;
        }
        .table-bordered th, .table-bordered td {
            border: 1px solid #cbd5e1;
        }
        .table-bordered th {
            background-color: #f1f5f9;
            color: #1e293b;
            font-weight: bold;
            font-size: 8.5pt;
        }
        .table-striped tr:nth-child(even) {
            background-color: #f8fafc;
        }

        /* Badges & Chips */
        .badge {
            display: inline-block;
            padding: 2px 6px;
            font-size: 7.5pt;
            font-weight: bold;
            border-radius: 4px;
            text-transform: uppercase;
        }
        .badge-blue { background-color: #dbeafe; color: #1e40af; }
        .badge-green { background-color: #dcfce7; color: #15803d; }
        .badge-amber { background-color: #fef3c7; color: #92400e; }
        .badge-rose { background-color: #ffe4e6; color: #9f1239; }
        .badge-purple { background-color: #f3e8ff; color: #6b21a8; }

        /* Step Callout Circle */
        .num-circle {
            display: inline-block;
            width: 18px;
            height: 18px;
            background-color: #ea580c;
            color: #ffffff;
            font-weight: bold;
            text-align: center;
            line-height: 18px;
            border-radius: 50%;
            font-size: 8pt;
            margin-right: 4px;
        }

        /* Alert / Highlight Boxes */
        .alert-box {
            padding: 8px 10px;
            border-radius: 6px;
            margin-bottom: 10px;
            font-size: 8.5pt;
        }
        .alert-info {
            background-color: #eff6ff;
            border-left: 4px solid #2563eb;
            color: #1e3a8a;
        }
        .alert-warning {
            background-color: #fffbeb;
            border-left: 4px solid #f59e0b;
            color: #78350f;
        }
        .alert-success {
            background-color: #f0fdf4;
            border-left: 4px solid #16a34a;
            color: #14532d;
        }

        /* Real Screenshot Image Display */
        .img-container {
            text-align: center;
            margin: 6px 0 8px 0;
            background: #ffffff;
            border: 1px solid #94a3b8;
            padding: 3px;
            border-radius: 6px;
        }
        .img-container img {
            max-width: 100%;
            height: auto;
            max-height: 220px;
            display: block;
            margin: 0 auto;
        }
        .img-caption {
            font-size: 7.5pt;
            color: #475569;
            font-style: italic;
            margin-top: 3px;
            font-weight: bold;
        }

        /* Cover Specific */
        .cover-box {
            text-align: center;
            padding: 35px 20px 20px 20px;
        }
        .cover-title {
            font-size: 24pt;
            color: #0f172a;
            font-weight: bold;
            line-height: 1.2;
            margin-top: 20px;
            margin-bottom: 10px;
        }
        .cover-subtitle {
            font-size: 12pt;
            color: #475569;
            margin-bottom: 25px;
        }
        .role-pill {
            display: inline-block;
            padding: 6px 14px;
            margin: 4px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 9pt;
        }
    </style>
</head>
<body>

@php
function getImgBase64($relativePath) {
    $fullPath = public_path($relativePath);
    if (file_exists($fullPath)) {
        $ext = pathinfo($fullPath, PATHINFO_EXTENSION);
        $mime = ($ext === 'jpg' || $ext === 'jpeg') ? 'image/jpeg' : 'image/png';
        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($fullPath));
    }
    return '';
}
$logoBase64  = getImgBase64('images/pedia_logo_hd.png');
$step0       = getImgBase64('images/manual/step0_dashboard_sales.png');
$step1       = getImgBase64('images/manual/step1_login.png');
$step2       = getImgBase64('images/manual/step2_profil_ttd.png');
$step3       = getImgBase64('images/manual/step3_tambah_klien.png');
$step4       = getImgBase64('images/manual/step4_buat_rfq.png');
$step5       = getImgBase64('images/manual/step5_admin_hpp.png');
$step6       = getImgBase64('images/manual/step6_leader_approval.png');
$step7       = getImgBase64('images/manual/step7_unduh_quotation.png');
$step8       = getImgBase64('images/manual/step8_upload_po.png');
$step9       = getImgBase64('images/manual/step9_leader_goal.png');
$contohQuo   = getImgBase64('images/contoh_quotation_resmi.png');
@endphp

{{-- ═════════════════════════════════════════════════════════════
     HALAMAN 1: COVER RESMI
     ═════════════════════════════════════════════════════════════ --}}
<div class="cover-box">
    @if($logoBase64)
        <img src="{{ $logoBase64 }}" style="height: 60px; margin-bottom: 8px;" alt="Logo Pedia Technology Indonesia">
    @endif
    <div style="font-size: 13pt; font-weight: bold; letter-spacing: 2px; color: #2563eb; text-transform: uppercase;">
        PT. PEDIA TECHNOLOGY INDONESIA
    </div>

    <div class="cover-title">
        BUKU PANDUAN PENGGUNA<br>
        <span style="color: #2563eb;">SISTEM CRM ANALYST</span>
    </div>

    <div class="cover-subtitle">
        Pedoman Operasional Praktis Alur Penawaran Harga (RFQ), Penetapan Modal, Approval Pimpinan, hingga Closing Berhasil (GOAL).
    </div>

    <div style="margin: 20px 0;">
        <span class="role-pill badge-blue">🔵 PANDUAN TIM SALES</span>
        <span class="role-pill badge-amber">🟠 PANDUAN TIM ADMIN</span>
        <span class="role-pill badge-green">🟢 PANDUAN TIM LEADER</span>
    </div>

    <div class="alert-box alert-info" style="text-align: left; margin: 30px auto 25px auto; max-width: 92%;">
        <strong>💡 Didesain Khusus Agar Sangat Mudah Dipahami:</strong><br>
        Buku panduan ini disusun dengan tampilan tangkapan layar <strong>asli (real screenshot)</strong> langsung dari sistem web CRM yang digunakan sehari-hari. Dilengkapi penjelasan nomor petunjuk langkah demi langkah dengan tulisan besar dan jelas agar Bapak/Ibu staf dan pimpinan dapat mengikutinya dengan tenang dan nyaman.
    </div>

    <table style="width: 85%; margin: 30px auto 0 auto; border-top: 2px solid #e2e8f0; padding-top: 12px; font-size: 8.5pt; color: #64748b;">
        <tr>
            <td style="width: 50%;"><strong>Perusahaan:</strong> PT. Pedia Technology Indonesia</td>
            <td style="width: 50%; text-align: right;"><strong>Tanggal Rilis:</strong> September 2026</td>
        </tr>
        <tr>
            <td><strong>Sistem:</strong> CRM Analyst Web Platform</td>
            <td style="text-align: right;"><strong>Versi Dokumen:</strong> 2.0 (Resmi)</td>
        </tr>
    </table>
</div>

<div class="page-break"></div>

{{-- ═════════════════════════════════════════════════════════════
     HALAMAN 2: DAFTAR ISI & BAGAN ALUR KERJA UTAMA
     ═════════════════════════════════════════════════════════════ --}}
<div class="doc-header">
    <table style="margin-bottom: 0;">
        <tr>
            <td style="font-weight: bold; font-size: 10.5pt; color: #1e40af;">BUKU PANDUAN CRM ANALYST — PT. PEDIA TECHNOLOGY INDONESIA</td>
            <td style="text-align: right; font-size: 8.5pt; color: #64748b;">Ringkasan Alur & Peran</td>
        </tr>
    </table>
</div>

<h2>Daftar Isi Buku Panduan</h2>
<table class="table-striped" style="font-size: 9pt; margin-bottom: 15px;">
    <tr>
        <td style="width: 80%;"><strong>Bagan Alur Transaksi Utama (6 Tahapan)</strong></td>
        <td style="text-align: right;">Halaman 2</td>
    </tr>
    <tr>
        <td><strong>BAB 1: Panduan Tim Sales (Pemasaran)</strong><br>
            <span style="font-size: 8pt; color: #64748b;">Login, Profil & Tanda Tangan, Input Klien, Buat RFQ, Unduh Penawaran, Upload PO</span>
        </td>
        <td style="text-align: right;">Halaman 3 – 5</td>
    </tr>
    <tr>
        <td><strong>BAB 2: Panduan Tim Admin (Purchasing & Operasional)</strong><br>
            <span style="font-size: 8pt; color: #64748b;">Cek Antrean, Pilih Vendor, Isi Harga Modal (HPP), Hitung Margin & Ongkir, Ajukan ke Leader</span>
        </td>
        <td style="text-align: right;">Halaman 6</td>
    </tr>
    <tr>
        <td><strong>BAB 3: Panduan Tim Leader (Pimpinan & Persetujuan)</strong><br>
            <span style="font-size: 8pt; color: #64748b;">Tinjau Keuntungan, Setujui Margin, Wewenang Backup HPP saat Admin Cuti, Approval PO Menjadi GOAL</span>
        </td>
        <td style="text-align: right;">Halaman 7</td>
    </tr>
    <tr>
        <td><strong>BAB 4: Tanya Jawab Sering Terjadi (FAQ) & Layanan Bantuan</strong></td>
        <td style="text-align: right;">Halaman 8</td>
    </tr>
</table>

<h2>Bagan Alur Transaksi CRM (Dari Awal Sampai Berhasil)</h2>
<p style="font-size: 9pt;">Setiap penawaran barang/jasa di PT. Pedia Technology Indonesia bergerak melewati 6 tahapan terintegrasi:</p>

<table class="table-bordered" style="font-size: 8pt; margin-top: 8px;">
    <thead>
        <tr>
            <th style="width: 8%; text-align: center;">Tahap</th>
            <th style="width: 22%;">Status di Sistem</th>
            <th style="width: 22%;">Penanggung Jawab</th>
            <th style="width: 48%;">Tindakan yang Dilakukan</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="text-align: center; font-weight: bold; background: #eff6ff;">1</td>
            <td><span class="badge badge-amber">Pending Admin</span></td>
            <td><strong>Tim Sales</strong></td>
            <td>Mendaftarkan klien dan memasukkan daftar barang yang diminta pembeli.</td>
        </tr>
        <tr>
            <td style="text-align: center; font-weight: bold; background: #fffbeb;">2</td>
            <td><span class="badge badge-amber">Pending Leader</span></td>
            <td><strong>Tim Admin</strong></td>
            <td>Menghubungi vendor, mengisi harga modal (HPP), ongkir, dan margin keuntungan.</td>
        </tr>
        <tr>
            <td style="text-align: center; font-weight: bold; background: #fef3c7;">3</td>
            <td><span class="badge badge-green">Approved</span></td>
            <td><strong>Leader / Pimpinan</strong></td>
            <td>Mengecek persentase keuntungan. Jika cocok, Leader klik <strong>Setujui (Approve)</strong>.</td>
        </tr>
        <tr>
            <td style="text-align: center; font-weight: bold; background: #f0fdf4;">4</td>
            <td><span class="badge badge-blue">Quotation Sent</span></td>
            <td><strong>Tim Sales</strong></td>
            <td>Sales mengunduh PDF resmi (berstempel basah & bertanda tangan) lalu mengirim ke pembeli.</td>
        </tr>
        <tr>
            <td style="text-align: center; font-weight: bold; background: #faf5ff;">5</td>
            <td><span class="badge badge-purple">PO Pending Admin/Leader</span></td>
            <td><strong>Sales & Admin</strong></td>
            <td>Pembeli setuju dan menerbitkan PO. Sales mengunggah berkas PO untuk diverifikasi.</td>
        </tr>
        <tr>
            <td style="text-align: center; font-weight: bold; background: #dcfce7;">6</td>
            <td><span class="badge badge-green">GOAL (Sukses)</span></td>
            <td><strong>Leader / Pimpinan</strong></td>
            <td>Leader menyetujui berkas PO. Penjualan sah dan omzet masuk ke target bulanan sales.</td>
        </tr>
    </tbody>
</table>

<div class="page-break"></div>

{{-- ═════════════════════════════════════════════════════════════
     HALAMAN 3: BAB 1 - SALES (LOGIN & PROFIL TTD)
     ═════════════════════════════════════════════════════════════ --}}
<div class="doc-header">
    <table style="margin-bottom: 0;">
        <tr>
            <td style="font-weight: bold; font-size: 10.5pt; color: #1e40af;">BAB 1: PANDUAN TIM SALES (PEMASARAN)</td>
            <td style="text-align: right; font-size: 8.5pt; color: #64748b;">1.1 Login & 1.2 Profil TTD</td>
        </tr>
    </table>
</div>

<h2>1.1 Cara Masuk ke Aplikasi (Login)</h2>
<p style="font-size: 8.5pt;">Buka browser Chrome atau Edge di komputer Bapak/Ibu, lalu buka alamat web CRM perusahaan. Tampilan layar masuk asli seperti di bawah ini:</p>

<div class="img-container">
    @if($step1)
        <img src="{{ $step1 }}" alt="Layar Login Asli CRM">
    @endif
    <div class="img-caption">Foto 1.1: Tangkapan Layar Asli Halaman Masuk (Login) CRM Analyst</div>
</div>

<table class="table-bordered" style="font-size: 8.5pt;">
    <thead>
        <tr>
            <th style="width: 25%;">Kolom / Tombol</th>
            <th style="width: 75%;">Petunjuk Pengisian untuk Bapak/Ibu</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><strong>Work Email or Username</strong></td>
            <td>Ketik alamat email resmi atau username Anda (contoh: <code>ade@crm.com</code>).</td>
        </tr>
        <tr>
            <td><strong>Password</strong></td>
            <td>Ketik kata sandi akun Anda (bisa klik ikon mata di sebelah kanan untuk melihat ketikan).</td>
        </tr>
        <tr>
            <td><strong>Tombol "Sign In" (Biru)</strong></td>
            <td>Klik tombol warna <strong>BIRU</strong> bertuliskan <strong>"Sign In to Workspace"</strong> untuk masuk ke dashboard.</td>
        </tr>
    </tbody>
</table>

<h2>1.2 Mengatur Jabatan & Tanda Tangan Digital Sales</h2>
<p style="font-size: 8.5pt;">Sebelum membuat penawaran pertama kali, Bapak/Ibu <strong>wajib</strong> mengisi jabatan dan tanda tangan di menu <strong>Profile</strong> (klik foto/nama di sidebar kiri bawah).</p>

<div class="img-container">
    @if($step2)
        <img src="{{ $step2 }}" alt="Layar Profil Asli dan Tanda Tangan">
    @endif
    <div class="img-caption">Foto 1.2: Tangkapan Layar Asli Menu Profil & Kotak Tanda Tangan Digital</div>
</div>

<table class="table-bordered" style="font-size: 8.5pt;">
    <thead>
        <tr>
            <th style="width: 25%;">Bagian di Layar</th>
            <th style="width: 75%;">Cara Pengisian</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><strong>Job Title (Jabatan)</strong></td>
            <td>Ketik nama jabatan resmi Anda (contoh: <code>Senior Account Executive</code> atau <code>Account Manager</code>). Jabatan ini otomatis tercetak tepat di bawah nama Anda pada surat penawaran resmi.</td>
        </tr>
        <tr>
            <td><strong>Digital Signature Canvas</strong></td>
            <td>Tahan klik kiri mouse lalu goreskan tanda tangan Anda di kotak putih. Jika salah, klik tombol <strong>"Clear"</strong> lalu ulangi. Pilihan ketebalan: pilih <strong>"Super Kereng"</strong> agar tanda tangan terlihat tegas saat dicetak.</td>
        </tr>
        <tr>
            <td><strong>Tombol "Simpan"</strong></td>
            <td>Klik tombol simpan untuk menyimpan profil dan tanda tangan Anda ke sistem.</td>
        </tr>
    </tbody>
</table>

<div class="page-break"></div>

{{-- ═════════════════════════════════════════════════════════════
     HALAMAN 4: BAB 1 - SALES (KLIEN & RFQ)
     ═════════════════════════════════════════════════════════════ --}}
<div class="doc-header">
    <table style="margin-bottom: 0;">
        <tr>
            <td style="font-weight: bold; font-size: 10.5pt; color: #1e40af;">BAB 1: PANDUAN TIM SALES (PEMASARAN)</td>
            <td style="text-align: right; font-size: 8.5pt; color: #64748b;">1.3 Customer & 1.4 RFQ</td>
        </tr>
    </table>
</div>

<h2>1.3 Mendaftarkan Pelanggan / Klien Baru</h2>
<p style="font-size: 8.5pt;">Setiap ada pembeli baru, daftarkan datanya melalui menu: <strong>Customers ➔ + Tambah Customer</strong>.</p>

<div class="img-container">
    @if($step3)
        <img src="{{ $step3 }}" alt="Form Tambah Klien Asli">
    @endif
    <div class="img-caption">Foto 1.3: Tangkapan Layar Asli Formulir Pendaftaran Customer Baru</div>
</div>

<table class="table-bordered" style="font-size: 8.5pt;">
    <thead>
        <tr>
            <th style="width: 25%;">Bagian Form</th>
            <th style="width: 75%;">Petunjuk Pengisian</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><strong>Company Name & Brand</strong></td>
            <td>Ketik nama resmi perusahaan pembeli (contoh: <code>PT. Sumber Makmur Sejahtera</code>) dan nama merk/brand jika ada.</td>
        </tr>
        <tr>
            <td><strong>Narahubung (PIC)</strong></td>
            <td>Ketik nama pejabat/kontak yang Anda hubungi, jabatan, email, dan nomor HP/WhatsApp aktif.</td>
        </tr>
        <tr>
            <td><strong>Alamat Perusahaan</strong></td>
            <td>Ketik alamat lengkap perusahaan pembeli beserta pilihan Provinsi, Kota, dan Kecamatan untuk kop surat penawaran dan pengiriman barang.</td>
        </tr>
        <tr>
            <td><strong>Tombol Simpan</strong></td>
            <td>Klik tombol warna <strong>BIRU</strong> di bagian bawah untuk menyimpan data customer.</td>
        </tr>
    </tbody>
</table>

<h2>1.4 Membuat Permintaan Penawaran Harga (RFQ) Baru</h2>
<p style="font-size: 8.5pt;">Setelah customer terdaftar, buat penawaran barang melalui menu: <strong>RFQ ➔ + Buat RFQ</strong>.</p>

<div class="img-container">
    @if($step4)
        <img src="{{ $step4 }}" alt="Formulir Buat RFQ Asli">
    @endif
    <div class="img-caption">Foto 1.4: Tangkapan Layar Asli Formulir Pengajuan RFQ Baru</div>
</div>

<table class="table-bordered" style="font-size: 8.5pt;">
    <thead>
        <tr>
            <th style="width: 25%;">Kolom Form</th>
            <th style="width: 75%;">Cara Pengisian</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><strong>Pilih Customer</strong></td>
            <td>Pilih nama perusahaan pembeli dari kotak pilihan yang tersedia.</td>
        </tr>
        <tr>
            <td><strong>Daftar Barang (Items)</strong></td>
            <td>Ketik nama barang beserta spesifikasi teknisnya (contoh: <code>Laptop Asus Vivobook 14 Core i5</code>) dan jumlah unit (Qty) yang diminta.</td>
        </tr>
        <tr>
            <td><strong>Catatan Khusus (Notes)</strong></td>
            <td>Ketik klausul garansi, ketersediaan stok, atau ketentuan pengiriman yang diminta pembeli.</td>
        </tr>
        <tr>
            <td><strong>Tombol Ajukan RFQ</strong></td>
            <td>Klik tombol warna <strong>BIRU</strong> untuk mengirimkan permintaan ke Tim Admin agar dicarikan modalnya.</td>
        </tr>
    </tbody>
</table>

<div class="page-break"></div>

{{-- ═════════════════════════════════════════════════════════════
     HALAMAN 5: BAB 1 - SALES (UNDUH QUOTATION & UPLOAD PO)
     ═════════════════════════════════════════════════════════════ --}}
<div class="doc-header">
    <table style="margin-bottom: 0;">
        <tr>
            <td style="font-weight: bold; font-size: 10.5pt; color: #1e40af;">BAB 1: PANDUAN TIM SALES (PEMASARAN)</td>
            <td style="text-align: right; font-size: 8.5pt; color: #64748b;">1.5 Unduh Penawaran & 1.6 Upload PO</td>
        </tr>
    </table>
</div>

<h2>1.5 Mengunduh Surat Penawaran Resmi (Quotation PDF)</h2>
<p style="font-size: 8.5pt;">Ketika status RFQ sudah <span class="badge badge-green">Approved</span>, surat resmi penawaran harga sudah siap diunduh dan dikirimkan ke pembeli.</p>

<div class="img-container">
    @if($step7)
        <img src="{{ $step7 }}" alt="Layar Unduh Quotation Asli">
    @endif
    <div class="img-caption">Foto 1.5: Tangkapan Layar Asli Halaman Detail RFQ Disetujui (Approved)</div>
</div>

<table class="table-bordered" style="font-size: 8.5pt;">
    <thead>
        <tr>
            <th style="width: 25%;">Tombol Tindakan</th>
            <th style="width: 75%;">Fungsi & Petunjuk</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><strong>Download Quotation (PDF)</strong></td>
            <td>Klik tombol warna <strong>BIRU</strong> ini. File PDF resmi akan terunduh otomatis ke laptop Anda. Berkas ini siap langsung dikirimkan ke pembeli.</td>
        </tr>
        <tr>
            <td><strong>Tandai Sudah Dikirim</strong></td>
            <td>Klik tombol warna <strong>HIJAU</strong> ini setelah Anda selesai mengirimkan surat penawaran ke pembeli agar sistem mencatat statusnya.</td>
        </tr>
        <tr>
            <td><strong>Tanda Tangan & Stempel</strong></td>
            <td>Dokumen penawaran otomatis sudah memiliki <strong>tanda tangan digital Anda dan stempel resmi PT. Pedia Technology Indonesia</strong> secara rapi tanpa perlu print-scan manual!</td>
        </tr>
    </tbody>
</table>

<h2>1.6 Mengunggah Berkas Purchase Order (PO) dari Klien</h2>
<p style="font-size: 8.5pt;">Kabar gembira! Pembeli setuju dan menerbitkan dokumen PO resmi. Buka nomor RFQ tersebut, lalu unggah berkas PO pada form di bagian bawah halaman detail.</p>

<div class="img-container">
    @if($step8)
        <img src="{{ $step8 }}" alt="Layar Upload PO Asli">
    @endif
    <div class="img-caption">Foto 1.6: Tangkapan Layar Asli Area Unggah Dokumen Purchase Order (PO)</div>
</div>

<table class="table-bordered" style="font-size: 8.5pt;">
    <thead>
        <tr>
            <th style="width: 25%;">Bagian Form</th>
            <th style="width: 75%;">Instruksi Pengisian</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><strong>Nomor PO Klien</strong></td>
            <td>Ketik nomor PO yang tertulis pada lembar pesanan resmi dari pembeli.</td>
        </tr>
        <tr>
            <td><strong>File Dokumen PO</strong></td>
            <td>Pilih berkas PDF atau foto hasil scan PO dari pembeli di komputer Anda.</td>
        </tr>
        <tr>
            <td><strong>Tombol Upload & Ajukan</strong></td>
            <td>Klik tombol warna <strong>BIRU</strong> untuk mengirimkan berkas PO ke meja Admin dan Leader untuk persetujuan akhir.</td>
        </tr>
    </tbody>
</table>

<div class="page-break"></div>

{{-- ═════════════════════════════════════════════════════════════
     HALAMAN 6: BAB 2 - ADMIN (MODAL & VENDOR)
     ═════════════════════════════════════════════════════════════ --}}
<div class="doc-header">
    <table style="margin-bottom: 0;">
        <tr>
            <td style="font-weight: bold; font-size: 10.5pt; color: #1e40af;">BAB 2: PANDUAN TIM ADMIN (PURCHASING & OPERASIONAL)</td>
            <td style="text-align: right; font-size: 8.5pt; color: #64748b;">Pengisian Modal & Vendor</td>
        </tr>
    </table>
</div>

<h2>2.1 Tugas Pokok Tim Admin</h2>
<p style="font-size: 8.5pt;">Tim Admin bertugas menghubungi supplier/vendor rekanan, memasukkan Harga Modal (HPP), memperkirakan biaya kirim (ongkir), dan menetapkan margin keuntungan.</p>

<h2>2.2 Cara Mengisi Harga Modal (HPP) & Memilih Vendor</h2>
<p style="font-size: 8.5pt;">Buka menu <strong>RFQ</strong>, pilih yang berstatus <span class="badge badge-amber">Pending Admin</span>, lalu klik tombol <strong>"Isi Harga / Form Harga"</strong>.</p>

<div class="img-container">
    @if($step5)
        <img src="{{ $step5 }}" alt="Form Pengisian Harga Modal Asli Admin">
    @endif
    <div class="img-caption">Foto 2.1: Tangkapan Layar Asli Formulir Penetapan Vendor, Harga Modal (HPP), dan Margin</div>
</div>

<table class="table-bordered" style="font-size: 8.5pt;">
    <thead>
        <tr>
            <th style="width: 25%;">Bagian Form</th>
            <th style="width: 75%;">Petunjuk Khusus Tim Admin</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><strong>Pilih Vendor / Supplier</strong></td>
            <td>Pilih nama distributor atau vendor rekanan yang menyanggupi stok barang tersebut.</td>
        </tr>
        <tr>
            <td><strong>Harga Modal (HPP)</strong></td>
            <td>Ketik angka harga beli dari vendor (tanpa titik). Sistem otomatis memformat ke bentuk rupiah.</td>
        </tr>
        <tr>
            <td><strong>Margin (%)</strong></td>
            <td>Ketik persentase keuntungan yang ditargetkan (misal: ketik <code>15</code> untuk 15%). Sistem langsung menghitung harga jual akhir ke pelanggan.</td>
        </tr>
        <tr>
            <td><strong>Estimasi Ongkos Kirim</strong></td>
            <td>Isi kolom ongkir jika ada biaya pengiriman dari vendor ke lokasi pembeli agar modal riil perusahaan tidak terpotong.</td>
        </tr>
        <tr>
            <td><strong>Tombol "Simpan & Teruskan"</strong></td>
            <td>Klik tombol warna <strong>HIJAU</strong> di bawah untuk mengajukan penawaran ke meja Leader guna disetujui.</td>
        </tr>
    </tbody>
</table>

<div class="alert-box alert-warning">
    <strong>⚠️ CATATAN UNTUK TIM ADMIN:</strong><br>
    Bila ada beberapa item barang dari vendor yang berbeda, Bapak/Ibu dapat memilih vendor yang berbeda pada tiap baris barang sebelum menyimpan form harga.
</div>

<div class="page-break"></div>

{{-- ═════════════════════════════════════════════════════════════
     HALAMAN 7: BAB 3 - LEADER (APPROVAL MARGIN & GOAL)
     ═════════════════════════════════════════════════════════════ --}}
<div class="doc-header">
    <table style="margin-bottom: 0;">
        <tr>
            <td style="font-weight: bold; font-size: 10.5pt; color: #1e40af;">BAB 3: PANDUAN LEADER / PIMPINAN</td>
            <td style="text-align: right; font-size: 8.5pt; color: #64748b;">Approval Margin & GOAL</td>
        </tr>
    </table>
</div>

<h2>3.1 Meninjau & Menyetujui Margin Penawaran</h2>
<p style="font-size: 8.5pt;">Leader adalah penentu kelayakan keuntungan perusahaan. Saat ada RFQ dengan status <span class="badge badge-amber">Pending Leader</span>, pimpinan dapat meninjau rincian biaya.</p>

<div class="img-container">
    @if($step6)
        <img src="{{ $step6 }}" alt="Layar Approval Leader Asli">
    @endif
    <div class="img-caption">Foto 3.1: Tangkapan Layar Asli Tinjauan Keuntungan dan Tombol Persetujuan Leader</div>
</div>

<table class="table-bordered" style="font-size: 8.5pt;">
    <thead>
        <tr>
            <th style="width: 25%;">Bagian Layar</th>
            <th style="width: 75%;">Fungsi & Petunjuk Tindakan Pimpinan</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><strong>Ringkasan Margin</strong></td>
            <td>Pimpinan dapat melihat rincian: Total Modal (HPP), Total Nilai Jual, serta persentase profit kotor yang didapat perusahaan.</td>
        </tr>
        <tr>
            <td><strong>Tombol Setujui (Approve)</strong></td>
            <td>Klik tombol warna <strong>HIJAU</strong> bertuliskan <strong>"Setujui Margin (Approve)"</strong> jika margin sudah layak. Penawaran langsung terbit dan Sales dapat mengunduh dokumen PDF.</td>
        </tr>
        <tr>
            <td><strong>Tombol Minta Revisi</strong></td>
            <td>Klik tombol warna <strong>KUNING</strong> bertuliskan <strong>"Minta Revisi ke Admin"</strong> jika margin dirasa terlalu tipis atau modal vendor masih bisa dinegosiasikan ulang.</td>
        </tr>
    </tbody>
</table>

<div class="alert-box alert-info">
    <strong>💡 WEWENANG BACKUP LEADER:</strong><br>
    Bila staf Admin berhalangan masuk (cuti/sakit), Bapak/Ibu Leader <strong>memiliki wewenang langsung</strong> untuk membuka RFQ yang berstatus <em>Pending Admin</em>, menginputkan harga modal vendor sendiri, dan langsung memprosesnya tanpa hambatan operasional.
</div>

<h2>3.2 Persetujuan Akhir Purchase Order Menjadi GOAL (Closing Sah)</h2>
<p style="font-size: 8.5pt;">Ketika Sales telah mengunggah berkas PO dari pembeli, status RFQ menjadi <span class="badge badge-purple">PO Received (Pending Leader)</span>. Leader melakukan pengesahan akhir transaksi.</p>

<div class="img-container">
    @if($step9)
        <img src="{{ $step9 }}" alt="Layar Persetujuan Akhir PO Menjadi GOAL Asli">
    @endif
    <div class="img-caption">Foto 3.2: Tangkapan Layar Asli Persetujuan Akhir PO Menjadi GOAL</div>
</div>

<table class="table-bordered" style="font-size: 8.5pt;">
    <thead>
        <tr>
            <th style="width: 25%;">Elemen Layar</th>
            <th style="width: 75%;">Instruksi Tindakan</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><strong>Cek Berkas PO Terlampir</strong></td>
            <td>Klik berkas PO pembeli yang terlampir untuk memastikan kesesuaian nilai transaksi, item barang, serta tanda tangan pemesan.</td>
        </tr>
        <tr>
            <td><strong>Tombol Setujui Menjadi GOAL</strong></td>
            <td>Klik tombol warna <strong>HIJAU BESAR</strong> bertuliskan <strong>"Setujui PO & Jadikan GOAL"</strong>. Transaksi resmi dinyatakan sah (**GOAL**)! Nilai penjualan otomatis langsung tercatat ke target sales bulan ini.</td>
        </tr>
    </tbody>
</table>

<div class="page-break"></div>

{{-- ═════════════════════════════════════════════════════════════
     HALAMAN 8: BAB 4 - FAQ & BANTUAN TEKNIS
     ═════════════════════════════════════════════════════════════ --}}
<div class="doc-header">
    <table style="margin-bottom: 0;">
        <tr>
            <td style="font-weight: bold; font-size: 10.5pt; color: #1e40af;">BAB 4: TANYA JAWAB UMUM & BANTUAN TEKNIS</td>
            <td style="text-align: right; font-size: 8.5pt; color: #64748b;">Penyelesaian Kendala Sehari-hari</td>
        </tr>
    </table>
</div>

<h2>Pertanyaan yang Sering Diajukan (FAQ)</h2>

<div class="alert-box alert-info">
    <strong>1. Bagaimana jika saya lupa kata sandi (password)?</strong><br>
    Bapak/Ibu tidak perlu cemas. Segera hubungi Tim Administrator CRM kantor. Akun Anda dapat dibantu reset kata sandi baru dalam waktu kurang dari 1 menit.
</div>

<div class="alert-box alert-warning">
    <strong>2. Kenapa tanda tangan saya belum keluar di lembar penawaran PDF?</strong><br>
    Pastikan Bapak/Ibu sudah menyimpan tanda tangan di menu <strong>Profile</strong> (lihat Halaman 3 Foto 1.2). Setelah tersimpan di profil, unduh ulang file surat penawarannya agar tanda tangan terbaru otomatis menempel rapi.
</div>

<div class="alert-box alert-success">
    <strong>3. Klien minta perubahan harga atau jumlah barang setelah surat dikirim, apa solusinya?</strong><br>
    Hubungi Admin atau Leader untuk melakukan klik tombol <strong>"Minta Revisi"</strong> pada nomor RFQ tersebut. Status akan kembali ke Admin sehingga harga atau jumlah barang bisa disesuaikan kembali dengan persetujuan pimpinan.
</div>

<div class="alert-box alert-info">
    <strong>4. Apakah sistem CRM ini bisa dibuka lewat HP atau Tablet?</strong><br>
    Bisa sekali. Tampilan CRM Analyst sudah disesuaikan agar tetap rapi, nyaman dibaca, dan mudah dipencet saat dibuka melalui Google Chrome di Handphone maupun iPad/Tablet.
</div>

<h2>Layanan Bantuan & Dukungan Pengguna</h2>
<p style="font-size: 8.5pt;">Jika Bapak/Ibu mengalami kesulitan teknis atau ada langkah yang kurang jelas, silakan hubungi tim pendukung kami:</p>

<table class="table-bordered" style="font-size: 9pt; width: 92%; margin: 12px auto;">
    <tr>
        <td style="width: 35%; background: #f8fafc;"><strong>Perusahaan:</strong></td>
        <td><strong>PT. Pedia Technology Indonesia</strong></td>
    </tr>
    <tr>
        <td style="background: #f8fafc;"><strong>Narahubung Teknis:</strong></td>
        <td>Tim Administrator CRM Pedia</td>
    </tr>
    <tr>
        <td style="background: #f8fafc;"><strong>Nomor Telepon Kantor:</strong></td>
        <td>021-3971-2155</td>
    </tr>
    <tr>
        <td style="background: #f8fafc;"><strong>Alamat Email Bantuan:</strong></td>
        <td>support@pedia-technology.co.id</td>
    </tr>
    <tr>
        <td style="background: #f8fafc;"><strong>Jam Layanan:</strong></td>
        <td>Senin – Jumat, Pukul 08.30 – 17.30 WIB</td>
    </tr>
</table>

<div style="text-align: center; margin-top: 30px; color: #64748b; font-size: 8.5pt;">
    — Selamat Bekerja & Sukses Selalu untuk Seluruh Tim PT. Pedia Technology Indonesia —
</div>

{{-- Footer on all pages --}}
<div class="doc-footer">
    Buku Panduan Pengguna CRM Analyst — PT. Pedia Technology Indonesia © 2026
</div>

</body>
</html>
