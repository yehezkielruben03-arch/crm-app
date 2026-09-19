# 📋 MASTER DOKUMEN BLACKBOX TESTING & USER ACCEPTANCE TESTING (UAT)
**Aplikasi:** CRM Analyst B2B (PT Pedia Technology Indonesia)  
**Lingkup Uji:** Localhost & Staging Hosting (`https://dev.pedia-technology.co.id`)  
**Metodologi:** Blackbox Testing (Functional, Negative/Security, Logic, Business Flow)  
**Total Skenario:** 62 Test Cases (16 Modul Utama)

---

## 👥 AKUN PENGUJIAN (TEST CREDENTIALS)
Gunakan 4 akun berikut sesuai role untuk menguji hak akses dan alur kerja antar divisi:

| Role | Nama Pengguna | Email | Password Localhost | Password Hosting | Hak Akses Utama |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Sales Marketing** | Ade Zulvida | `ade@crm.com` | `password123` | `Pedia551%` | Input Klien, Buat RFQ Barang, Revisi Qty, Upload PO Klien *(HPP Hidden)* |
| **Admin Purchase** | Admin Purchase | `admin@crm.com` | `password123` | `password123` | Master Vendor, Portal Mainpower, Hitung HPP, Buat PO Vendor, Direct RFQ Projek |
| **Leader / Manager**| Laras | `laras@crm.com` | `password123` | `password123` | Approval HPP, Reject Revisi, Approval PO/Goal, Laporan Analitik Tim |
| **Super Admin** | Ruben | `ruben@crm.com` | `password123` | `password123` | Full Master Akses, Manajemen User, Migrasi Customer Sales Resign |

---

## 📑 DAFTAR ISI MODUL PENGUJIAN
1. [Modul 01: Autentikasi, Keamanan RBAC & Proteksi URL](#modul-01-autentikasi-keamanan-rbac--proteksi-url)
2. [Modul 02: Manajemen Customer, Validasi Duplikasi & Multi-Alamat](#modul-02-manajemen-customer-validasi-duplikasi--multi-alamat)
3. [Modul 03: Fitur Spesial Inline PIC AJAX (Tanpa Refresh Halaman)](#modul-03-fitur-spesial-inline-pic-ajax-tanpa-refresh-halaman)
4. [Modul 04: Master Vendor & Integrasi Supplier](#modul-04-master-vendor--integrasi-supplier)
5. [Modul 05: Portal Tarif Mainpower (Kompensasi SDM & BPJS)](#modul-05-portal-tarif-mainpower-kompensasi-sdm--bpjs)
6. [Modul 06: Pembuatan RFQ Multi-Item (3 Kategori Blok Barang/Jasa/Material)](#modul-06-pembuatan-rfq-multi-item-3-kategori-blok-barangjasamaterial)
7. [Modul 07: Kalkulator HPP Admin (Auto-Ongkir Pedia, Vendor Margin & Ceiling)](#modul-07-kalkulator-hpp-admin-auto-ongkir-pedia-vendor-margin--ceiling)
8. [Modul 08: Approval & Penolakan HPP oleh Leader (Catatan Revisi)](#modul-08-approval--penolakan-hpp-oleh-leader-catatan-revisi)
9. [Modul 09: Penerbitan Surat Penawaran (Quotation PDF & Web Preview)](#modul-09-penerbitan-surat-penawaran-quotation-pdf--web-preview)
10. [Modul 10: Siklus Super Revisi Sales (Update Qty, Hapus/Tambah Item)](#modul-10-siklus-super-revisi-sales-update-qty-hapustambah-item)
11. [Modul 11: Admin Review Revisi & Snapshot Histori HPP Versi 2](#modul-11-admin-review-revisi--snapshot-histori-hpp-versi-2)
12. [Modul 12: Siklus PO Klien, Verifikasi Berkas & Transisi GOAL (Won)](#modul-12-siklus-po-klien-verifikasi-berkas--transisi-goal-won)
13. [Modul 13: Direct RFQ Projek Khusus Admin & Leader (Instalasi / Jasa)](#modul-13-direct-rfq-projek-khusus-admin--leader-instalasi--jasa)
14. [Modul 14: Purchase Order (PO Pemesanan Barang ke Vendor)](#modul-14-purchase-order-po-pemesanan-barang-ke-vendor)
15. [Modul 15: Realtime Polling Notifikasi & Alert Lonceng](#modul-15-realtime-polling-notifikasi--alert-lonceng)
16. [Modul 16: Analytics, KPI Sales Target & Migrasi Portofolio Klien](#modul-16-analytics-kpi-sales-target--migrasi-portofolio-klien)

---

### MODUL 01: Autentikasi, Keamanan RBAC & Proteksi URL

| ID | Skenario Uji | Langkah Aksi | Data Input | Hasil yang Diharapkan | Status |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **AUTH-01** | Login Berhasil (Sales) | Buka `/login`, isi form | `ade@crm.com` / `password123` | Redirect ke `/dashboard`, nama Sales muncul di navbar, token sesi aktif. | [x] PASS |
| **AUTH-02** | Login Berhasil (Admin) | Buka `/login`, isi form | `admin@crm.com` / `password123` | Redirect ke Dashboard Admin, menu Vendor & Mainpower muncul di sidebar. | [x] PASS |
| **AUTH-03** | Login Berhasil (Leader) | Buka `/login`, isi form | `laras@crm.com` / `password123` | Redirect ke Dashboard Leader, grid KPI simetris & action center aktif. | [x] PASS |
| **AUTH-04** | Validasi Email / Password Salah | Buka `/login`, isi salah | `salah@crm.com` / `ngawur123` | Tampil error: *"Email atau Password yang Anda masukkan salah."* | [x] PASS |
| **AUTH-05** | Rate Limiting (Brute Force) | Coba login salah 5x cepat | Form submit 5x | Muncul error rate limiter: *"Too many login attempts. Please try again..."* | [x] PASS |
| **AUTH-06** | Logout & Session Flush | Klik avatar -> **Logout** | - | Sesi terhapus, redirect ke `/login`. Klik tombol Back browser tidak bisa masuk. | [x] PASS |
| **AUTH-07** | Anti-Stuck Back Button (bfcache) | Login sukses -> Klik tombol **Back** browser | - | Tombol login kembali ke label normal (*bukan stuck di "Authenticating..."*). | [x] PASS |
| **AUTH-08** | RBAC URL Tampering: Sales vs Vendor | Login sebagai Sales -> Buka URL: `/vendors` | URL langsung | **Dicegat 403 Forbidden** (Sales dilarang akses database vendor supplier). | [x] PASS |
| **AUTH-09** | RBAC URL Tampering: Sales vs Mainpower | Login sebagai Sales -> Buka URL: `/mainpowers` | URL langsung | **Dicegat 403 Forbidden** (Sales dilarang intip portal tarif tenaga kerja). | [ ] |
| **AUTH-10** | RBAC URL Tampering: Sales vs Users | Login sebagai Sales -> Buka URL: `/users` | URL langsung | **Dicegat 403 Forbidden** (Sales dilarang mengelola user). | [ ] |

---

### MODUL 02: Manajemen Customer, Validasi Duplikasi & Multi-Alamat

| ID | Skenario Uji | Langkah Aksi | Data Input | Hasil yang Diharapkan | Status |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **CUST-01** | Tambah Customer Baru | Menu **Customers** -> **Add Customer** | `Jiper Niper Indonesia`, Alamat Delta Silicon, Ongkir: `Rp 40.000` | Data tersimpan, status `Prospect`, PIC & alamat billing/shipping tersimpan. | [x] PASS |
| **CUST-02** | Deteksi Duplikasi Realtime (AJAX) | Ketik nama perusahaan yang mirip saat create customer | Ketik: `Jiper Niper` | Muncul kotak peringatan realtime: Amber (kemiripan nama) & Merah (duplikasi exact). | [x] PASS |
| **CUST-03** | Penolakan Exact Match | Submit perusahaan dengan nama persis sama yang sudah ada | Nama: `PT Jiper Niper Indonesia` | Form ditolak dengan pesan validasi: *"Perusahaan dengan nama yang sama sudah terdaftar!"*. | [ ] |
| **CUST-04** | Multi-Alamat (Billing & Shipping) | Buka form Customer -> Isi Alamat Billing & Shipping | Kantor Pusat & Gudang Pabrik | Alamat penagihan & pengiriman tersimpan dan siap dipilih di transaksi. | [x] PASS |
| **CUST-05** | API Wilayah Dependen | Pilih Provinsi -> Kota -> Kecamatan -> Kelurahan | Jawa Barat -> Kab. Bekasi -> Cikarang Selatan -> Ciantra | Dropdown berantai terisi dinamis dan kode pos terisi otomatis tanpa reload. | [x] PASS |
| **CUST-06** | Approval Customer & Kode Perusahaan | Login Leader -> Buka Customer -> Klik **Approve** | Customer `Jiper Niper Indonesia` | Customer di-approve jadi `Active`, otomatis terbit Company Code unik (`PTI202609180000001`). | [x] PASS |
| **CUST-07** | Soft Delete Customer | Klik ikon Trash pada salah satu customer | Customer percobaan | Customer diarsipkan & pindah ke tab **Trash**, tidak muncul di pilihan buat RFQ. | [x] PASS |
| **CUST-08** | Restore Customer dari Trash | Buka menu `/customers/trash` -> Klik **Restore** | Customer di Trash | Customer aktif kembali ke tabel utama beserta seluruh histori datanya. | [x] PASS |
| **CUST-09** | Export Data Customer ke Excel | Klik tombol **Export Excel** di list customer | - | File `.xlsx` terunduh rapi berisi seluruh data customer dan PIC dengan styling korporat, freeze pane, status badges, dan format teks protektif. | [x] PASS |
| **CUST-10** | Import Customer via Excel | Klik **Import Excel** -> Upload file template / database pelanggan vakum | File excel customer | Halaman preview menampilkan data dan mapping kolom Sales sebelum di-commit ke database; formula Excel di-evaluasi akurat. | [x] PASS |

---

### MODUL 03: Fitur Spesial Inline PIC AJAX (Tanpa Refresh Halaman)

| ID | Skenario Uji | Langkah Aksi | Data Input | Hasil yang Diharapkan | Status |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **PIC-01** | Tambah PIC Inline saat Buat RFQ | Di form `/rfqs/create`, klik **"+ Tambah Kontak Baru"** | Nama: `Bpk. Hendra`, Jabatan: `Purchasing Manager`, Telp: `08123456789` | Modal pop-up muncul, setelah simpan: kontak langsung terpilih di dropdown tanpa halaman reload! | [x] PASS |
| **PIC-02** | Multi-PIC per Customer | Buka detail Customer -> Tab Kontak -> Tambah PIC ke-2 | Nama: `Ibu Siska (Finance)` | Tersimpan 2 PIC: 1 Primary CP, 1 Secondary CP. | [x] PASS |

---

### MODUL 04: Master Vendor & Integrasi Supplier

| ID | Skenario Uji | Langkah Aksi | Data Input | Hasil yang Diharapkan | Status |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **VND-01** | Tambah Vendor Baru (Admin) | Login Admin -> Menu **Vendors** -> **Add Vendor** | Nama PT Vendor, PIC, NPWP, Bank, Rekening, Kategori: `Hardware & IT` | Vendor tersimpan, status Active, muncul di list vendor. | [x] PASS |
| **VND-02** | Filter Kategori Vendor | Filter list vendor berdasarkan kategori | Kategori: `CCTV & Access Control` | Tabel hanya menampilkan vendor yang sesuai kategori. | [x] PASS |
| **VND-03** | Edit & Nonaktifkan Vendor | Klik Edit Vendor -> Ubah status jadi Inactive | Status: Inactive | Vendor tidak muncul di pilihan dropdown form kalkulasi HPP RFQ baru. | [x] PASS |

---

### MODUL 05: Portal Tarif Mainpower (Kompensasi SDM & BPJS)

| ID | Skenario Uji | Langkah Aksi | Data Input | Hasil yang Diharapkan | Status |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **MP-01** | Buka Portal Mainpower | Login Admin Purchase -> Buka menu `/mainpowers` | - | Halaman portal tarif teknisi terbuka (HTTP 200). | [x] PASS |
| **MP-02** | Update Formula Tarif SDM | Ubah Gaji Pokok Harian, Uang Makan, BPJS Kesehatan, BPJS TK | Gaji: Rp 200.000, BPJS Kes: 4%, BPJS TK: 5.7%, Lembur/jam | Nilai Total Rate Harian terhitung otomatis sesuai formula internal Pedia. | [x] PASS |
| **MP-03** | Verifikasi Accessor BPJS | Simpan tarif dan refresh halaman | - | Tidak terjadi crash PHP 8.2 (`bpjs_kes` accessor aman tanpa error 500). | [x] PASS |

---

### MODUL 06: Pembuatan RFQ Multi-Item (3 Kategori Blok Barang/Jasa/Material)

| ID | Skenario Uji | Langkah Aksi | Data Input | Hasil yang Diharapkan | Status |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **RFQ-01** | Akses Form RFQ (Sales) | Login Sales -> Buka `/rfqs/create` | - | Form RFQ terbuka rapi dengan pencarian customer interaktif. | [ ] |
| **RFQ-02** | Auto-Suggest Customer | Ketik 3 huruf nama PT di kolom Customer | Ketik: `Sumber` | Dropdown ajax menampilkan saran PT yang cocok beserta alamatnya. | [ ] |
| **RFQ-03** | Generator Nomor RFQ Standar | Pilih customer & klik generate/submit | - | Terbit nomor RFQ dengan format resmi `YYMMDD-XXXX` (contoh: `260918-0001`). | [ ] |
| **RFQ-04** | Tambah Item Blok 1: Hardware | Klik **Tambah Item** -> Pilih kategori **Hardware** | Nama: `Server Rack 42U`, Qty: `2`, Satuan: `Unit`, Target: `Rp 15.000.000` | Item hardware tersimpan di blok hardware. | [ ] |
| **RFQ-05** | Tambah Item Blok 2: Jasa | Klik **Tambah Item** -> Pilih kategori **Jasa** | Nama: `Jasa Penarikan Kabel UTP Cat6`, Qty: `10`, Satuan: `Titik` | Item jasa tersimpan di blok jasa. | [ ] |
| **RFQ-06** | Tambah Item Blok 3: Material | Klik **Tambah Item** -> Pilih kategori **Material** | Nama: `Pipa Conduit 20mm & Klem`, Qty: `50`, Satuan: `Batang` | Item material tersimpan di blok material. | [ ] |
| **RFQ-07** | Upload Berkas Lampiran RFQ | Upload file PDF spesifikasi teknis dari klien | File `TOR_Klien.pdf` (< 5MB) | Berkas ter-upload di storage, link file muncul di detail RFQ. | [ ] |
| **RFQ-08** | Status Awal Pasca-Submit | Klik tombol **Submit RFQ** | - | Status RFQ otomatis: **"Pending Admin"** (menunggu hitung HPP). | [ ] |
| **RFQ-09** | **KERAHASIAAN MODAL (Crucial!)** | Login sebagai Sales -> Buka RFQ yang baru dibuat | - | **Kolom HPP, Vendor, Margin, dan Harga Beli SAMA SEKALI TIDAK TAMPIL untuk Sales!** | [ ] |

---

### MODUL 07: Kalkulator HPP Admin (Auto-Ongkir Pedia, Vendor Margin & Ceiling)

| ID | Skenario Uji | Langkah Aksi | Data Input | Hasil yang Diharapkan | Status |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **HPP-01** | Proteksi Form HPP dari Sales | Login Sales -> Coba buka `/rfqs/{id}/price` secara paksa | URL langsung | **Dicegat 403 Forbidden!** Sales dilarang akses form kalkulasi HPP. | [x] PASS |
| **HPP-02** | Buka Form HPP (Admin) | Login Admin Purchase -> Buka RFQ tadi -> Klik **"Input HPP / Kalkulasi Harga"** | - | Halaman Form HPP 3 Blok terbuka menampilkan item Hardware, Jasa, Material. | [x] PASS |
| **HPP-03** | Auto-Fill Ongkir Pedia | Cek baris `Ongkir dari Pedia` di form HPP | - | Otomatis terisi nilai `Rp 150.000` (ditarik dari data profile Customer). | [x] PASS |
| **HPP-04** | Input Modal Hardware & Margin % | Pilih Vendor, isi Harga Modal: `Rp 10.000.000`, Margin: `20%` | Modal: 10jt, Margin: 20% | Harga jual terhitung otomatis: `Rp 12.000.000` + ongkir terdistribusi. | [x] PASS |
| **HPP-05** | Input Modal Jasa dari Mainpower | Pilih sumber Mainpower Pedia untuk item Jasa | Tarif harian MP | Nilai HPP Jasa terisi otomatis dari tarif Portal Mainpower. | [x] PASS |
| **HPP-06** | Margin Nominal (Rupiah Langsung) | Pada item Material, isi Margin Nominal bukan % | Margin Nominal: `Rp 25.000` | Harga jual menjadi Harga Modal + Rp 25.000. | [x] PASS |
| **HPP-07** | Fitur Pembulatan (Ceiling) | Masukkan harga modal yang menghasilkan angka ganjil (misal: Rp 1.234.567) lalu aktifkan Pembulatan | Centang Pembulatan Ribuan | Harga jual dibulatkan ke atas menjadi `Rp 1.235.000` atau kelipatan yang rapi. | [x] PASS |
| **HPP-08** | Submit HPP ke Leader | Klik tombol **"Submit HPP ke Leader"** | - | Status RFQ berubah otomatis menjadi **"Pending Leader"**. | [x] PASS |
| **HPP-09** | Snapshot Histori Versi 1 | Cek database tabel `rfq_price_histories` | - | Tercatat snapshot HPP Versi 1 lengkap dengan rincian harga per item. | [x] PASS |

---

### MODUL 08: Approval & Penolakan HPP oleh Leader (Catatan Revisi)

| ID | Skenario Uji | Langkah Aksi | Data Input | Hasil yang Diharapkan | Status |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **APP-01** | Antrian Approval Leader | Login Leader (`laras@crm.com`) -> Buka menu `/approvals` | - | RFQ yang disubmit Admin muncul di antrian *"Menunggu Approval"*. | [ ] |
| **APP-02** | Skenario Reject (Minta Revisi) | Klik tombol **Reject / Minta Revisi** | Catatan: *"Margin item hardware terlalu tipis, tolong nego vendor turun 5%"* | Status RFQ berubah jadi **"Revision Required"** (atau `Pending Admin`). | [ ] |
| **APP-03** | Tampilan Kotak Catatan Revisi | Login Admin Purchase -> Buka RFQ yang ditolak tadi | - | Muncul **Alert Box Amber khusus**: menampilkan teks alasan revisi dari Leader! | [ ] |
| **APP-04** | Admin Perbaiki HPP & Resubmit | Admin ubah harga vendor sesuai arahan -> Klik Submit lagi | Harga disesuaikan | Status RFQ kembali menjadi **"Pending Leader"**. | [ ] |
| **APP-05** | Skenario Approve HPP | Leader buka RFQ -> Klik tombol hijau **"Approve HPP"** | - | Status RFQ berubah menjadi **"Approved"** (Siap cetak Quotation). | [ ] |

---

### MODUL 09: Penerbitan Surat Penawaran (Quotation PDF & Web Preview)

| ID | Skenario Uji | Langkah Aksi | Data Input | Hasil yang Diharapkan | Status |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **QUO-01** | Web Preview Quotation | Buka RFQ Approved -> Klik **"Preview Quotation"** | - | Tampilan Web Penawaran resmi terbuka dengan Kop Surat Pedia Technology. | [ ] |
| **QUO-02** | Nomor Quotation Resmi | Cek nomor penawaran di dokumen | - | Terbit nomor otomatis (contoh: `QUO/PT/2026/09/XXXX`). | [ ] |
| **QUO-03** | Footer Bilingual & T&C | Scroll ke bawah halaman preview | - | Termuat Terms & Condition bilingual (Indonesia & English), rekening bank resmi PT. | [ ] |
| **QUO-04** | Kerahasiaan Modal di PDF | Cek seluruh isi tabel preview/PDF | - | **Hanya memuat Harga Jual ke Klien. Kolom HPP & Margin 100% GHAIB dari dokumen!** | [ ] |
| **QUO-05** | Download PDF Quotation | Klik tombol **"Download PDF"** | - | File `.pdf` terunduh dengan layout halaman A4 yang presisi dan rapi. | [ ] |
| **QUO-06** | Tandai Penawaran Terkirim | Klik tombol **"Mark Quotation Sent to Client"** | - | Status RFQ berubah menjadi **"Quotation Sent"**. | [ ] |

---

### MODUL 10: Siklus Super Revisi Sales (Update Qty, Hapus/Tambah Item)

| ID | Skenario Uji | Langkah Aksi | Data Input | Hasil yang Diharapkan | Status |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **REV-01** | Akses Form Revisi Qty | Login Sales -> Buka RFQ berstatus Quotation Sent -> Klik **"Revisi Penawaran / QTY"** | - | Halaman `/rfqs/{id}/revisi-qty` terbuka. | [ ] |
| **REV-02** | Ubah Kuantiti Item | Ubah Qty Item 1 dari `2` menjadi `4` unit | Qty: 4 | Kuantiti ter-update. | [ ] |
| **REV-03** | Hapus Item yang Batal | Klik ikon Hapus pada item material | - | Item material terhapus dari daftar penawaran. | [ ] |
| **REV-04** | Tambah Item Baru di Revisi | Klik **Tambah Item Baru** di form revisi | Item: `Patch Cord Cat6 3m`, Qty: `10` | Item baru bertambah ke dalam draf revisi. | [ ] |
| **REV-05** | Wajib Isi Catatan Revisi | Kosongkan field Alasan Revisi -> Klik Simpan | Field kosong | Sistem memblokir submit: *"Alasan revisi wajib diisi!"*. | [ ] |
| **REV-06** | Submit Super Revisi | Isi alasan: *"Klien minta tambah 2 unit server dan kabel patch cord"* -> Submit | Alasan revisi valid | Status RFQ otomatis berbalik ke **"Pending Admin"** agar dihitung ulang. | [ ] |

---

### MODUL 11: Admin Review Revisi & Snapshot Histori HPP Versi 2

| ID | Skenario Uji | Langkah Aksi | Data Input | Hasil yang Diharapkan | Status |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **REV2-01** | Admin Hitung Item Baru Revisi | Login Admin -> Buka RFQ -> Input HPP untuk item baru yang ditambah Sales | Harga vendor item baru | Total HPP dan margin baru terakumulasi. | [ ] |
| **REV2-02** | Snapshot Versi 2 Tercatat | Submit HPP revisi ke Leader | - | Di tabel `rfq_price_histories`, otomatis bertambah snapshot **Versi 2**! | [ ] |
| **REV2-03** | Approval Penawaran Revisi | Login Leader -> Approve HPP Versi 2 | - | Status RFQ kembali menjadi **"Approved"**. | [ ] |
| **REV2-04** | **Label Merah "REVISI" di Dokumen**| Download PDF Quotation hasil revisi | - | Di header dokumen Quotation otomatis muncul watermark / badge **"REVISI"**! | [ ] |

---

### MODUL 12: Siklus PO Klien, Verifikasi Berkas & Transisi GOAL (Won)

| ID | Skenario Uji | Langkah Aksi | Data Input | Hasil yang Diharapkan | Status |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **PO-01** | Sales Upload PO Klien | Login Sales -> Buka RFQ -> Bagian **Upload PO Klien** | File: `PO_Klien_Resmi.pdf`, No PO: `PO-CLIENT-2026-099`, Tgl PO | File tersimpan di storage, status RFQ beralih ke **"PO Received (Pending Admin)"**. | [ ] |
| **PO-02** | Admin Verifikasi Berkas PO | Login Admin Purchase -> Buka RFQ -> Cek file berkas -> Klik **"Verifikasi PO"** | - | Status RFQ berubah menjadi **"PO Received (Pending Leader)"**. | [ ] |
| **PO-03** | Leader Sahkan PO (Approve Goal)| Login Leader -> Buka RFQ -> Klik tombol **"Approve Goal / Sahkan Deal"** | - | Status RFQ resmi menjadi **"GOAL / Won Project"**! | [ ] |
| **PO-04** | Dampak ke Omset & Statistik | Cek Dashboard Sales & Analytics | - | Nilai transaksi RFQ otomatis masuk ke grafik revenue deal & win rate tim! | [ ] |

---

### MODUL 13: Direct RFQ Projek Khusus Admin & Leader (Instalasi / Jasa)

| ID | Skenario Uji | Langkah Aksi | Data Input | Hasil yang Diharapkan | Status |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **PRJ-01** | Akses Form RFQ Projek | Login Admin / Leader -> Buka URL `/rfqs/create-project` | - | Form RFQ Projek terbuka (bebas dari error routing wildcard 404). | [ ] |
| **PRJ-02** | Input Tenaga Kerja (Manpower) | Masukkan kebutuhan teknisi: 4 orang, 5 hari kerja, uang lembur | SDM 4 orang x 5 hari | Total biaya SDM terakumulasi akurat. | [ ] |
| **PRJ-03** | Input Alat & Operasional | Masukkan biaya sewa Scaffolding, Transportasi, Material Bantu | Biaya operasional | Total HPP Projek terhitung otomatis beserta margin yang diinginkan. | [ ] |
| **PRJ-04** | Simpan Direct RFQ Projek | Klik Simpan Projek | - | RFQ Projek tersimpan dengan nomor urut resmi dan status aktif. | [ ] |

---

### MODUL 14: Purchase Order (PO Pemesanan Barang ke Vendor)

| ID | Skenario Uji | Langkah Aksi | Data Input | Hasil yang Diharapkan | Status |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **POV-01** | Buat PO ke Supplier Vendor | Login Admin Purchase -> Menu **Purchase Orders** -> **Create PO** | Pilih Vendor, Pilih RFQ terkait, Qty & Harga Beli Modal | PO ke vendor terbuat dengan nomor resmi (contoh: `PO-PEDIA-2026-XXXX`). | [ ] |
| **POV-02** | Download PDF PO Vendor | Buka detail PO Vendor -> Klik **"Download PDF"** | - | Dokumen PO resmi PT Pedia Technology Indonesia ke supplier terunduh. | [ ] |
| **POV-03** | Approval PO Vendor oleh Leader | Login Leader -> Buka menu PO -> Klik **Approve PO** | - | Status PO menjadi Approved, siap dikirim ke supplier. | [ ] |

---

### MODUL 15: Realtime Polling Notifikasi & Alert Lonceng

| ID | Skenario Uji | Langkah Aksi | Data Input | Hasil yang Diharapkan | Status |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **NOTIF-01**| Notifikasi Masuk Otomatis | Sales submit RFQ baru -> Amati ikon lonceng akun Admin Purchase | - | Muncul badge angka merah `(1)`, dropdown memuat pemberitahuan RFQ baru. | [ ] |
| **NOTIF-02**| Endpoint Polling Lolos 200 | Buka Network Tab di DevTools browser -> Amati request background | `/api/notifications/poll` | Request merespon **HTTP 200** secara berkala tanpa memutus sesi user. | [ ] |
| **NOTIF-03**| Tandai Dibaca (Mark as Read) | Klik lonceng -> Klik notifikasi / **Mark All as Read** | - | Badge angka merah hilang, status notifikasi berubah menjadi sudah dibaca. | [ ] |

---

### MODUL 16: Analytics, KPI Sales Target & Migrasi Portofolio Klien

| ID | Skenario Uji | Langkah Aksi | Data Input | Hasil yang Diharapkan | Status |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **KPI-01** | Progress Bar Target Bulanan | Login Sales (`ade@crm.com`) -> Buka Dashboard | - | Muncul Progress Bar pencapaian omset terhadap target (Target Ade: Rp 500.000.000). | [ ] |
| **KPI-02** | Menu Analytics Tim Leader | Login Leader -> Buka menu `/analytics` | Filter tanggal bulan ini | Menampilkan Total Nilai RFQ, Win Rate Deal %, dan Rata-rata Persentase Margin. | [ ] |
| **MIG-01** | Nonaktifkan Akun Sales | Login Super Admin (`ruben@crm.com`) -> Menu **Users** -> Delete user `sales2@crm.com` | - | Akun dinonaktifkan (Soft Delete), user tidak bisa login lagi. | [ ] |
| **MIG-02** | **Migrasi Portofolio Customer** | Klik tombol **"Migrate Customers"** pada Sales yang nonaktif -> Pilih Sales penerima: `Ade Zulvida` | - | Seluruh daftar klien dan RFQ milik Sales lama otomatis berpindah ke Ade! | [ ] |

---

## 🚀 PANDUAN CARA MENJALANKAN UAT DI LOCALHOST

Untuk pengujian cepat dan lancar sebelum di-upload ulang:
1. **Jalankan 2 Browser Berdampingan (Split Screen):**
   * **Layar Kiri (Google Chrome Biasa):** Login sebagai **Sales (`ade@crm.com` / `password123`)**
   * **Layar Kanan (Chrome Incognito / Edge):** Login sebagai **Admin Purchase (`admin@crm.com` / `password123`)**
2. **Jalankan Skenario Transaksi Nyata:**
   * Mulai dari **Modul 02** (buat customer), **Modul 06** (buat RFQ multi-item), **Modul 07** (admin hitung HPP), ganti login kanan ke Leader untuk **Modul 08** (approve HPP), lalu ke **Modul 12** (upload PO dan GOAL).
3. Tandai setiap skenario dengan memberi centang `[x]` pada dokumen ini.
