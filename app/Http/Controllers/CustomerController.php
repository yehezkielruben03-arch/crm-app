<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\User;
use App\Exports\CustomerExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    public function suggestions(Request $request)
    {
        $field = $request->query('field');
        $search = $request->query('q');

        // Allow list of allowed fields to prevent SQL injection
        $allowedFields = ['industry', 'area_category', 'cp_position'];
        
        if (!in_array($field, $allowedFields)) {
            return response()->json([]);
        }

        $query = Customer::select($field)
            ->whereNotNull($field)
            ->where($field, '!=', '');

        if ($search) {
            $query->where($field, 'LIKE', "%{$search}%");
        }

        $results = $query->distinct()
            ->limit(10)
            ->pluck($field);

        return response()->json($results);
    }

    public function index(Request $request)
    {
        $user = $this->authUser();
        $query = Customer::with('sales');

        // Transparansi Data Customer: Semua Sales & Admin dapat melihat seluruh database customer
        if ($request->filled('sales_id')) {
            $query->where('sales_id', $request->sales_id);
        }

        // Fitur Search / Filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%")
                  ->orWhere('company_code', 'like', "%{$search}%")
                  ->orWhere('industry', 'like', "%{$search}%")
                  ->orWhere('region', 'like', "%{$search}%");
            });
        }

        // Filter berdasarkan Status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $customers = $query->orderByDesc('created_at')->paginate(10)->withQueryString();
        $salesList = User::whereIn('role', ['Sales', 'Sales Marketing'])->get();

        return view('customers.index', compact('customers', 'salesList'));
    }

    public function show(Customer $customer)
    {
        $user = $this->authUser();

        // Hanya role Sales/Admin yang bisa akses
        if (!$user->isAdminOrAbove() && !$user->isSales()) {
            abort(403, 'Anda tidak memiliki akses ke profil customer ini.');
        }

        // Sales diperbolehkan melihat profil customer sales lain (transparan)
        if (!$user->isAdminOrAbove() && !$user->isSales()) {
            abort(403, 'Anda tidak memiliki akses ke profil customer ini.');
        }

        // Eager load relasi 'sales' pada $customer
        $customer->load('sales', 'billingAddresses', 'shippingAddresses');

        // Ambil data PO (Berjalan & Riwayat)
        $activePOs  = $customer->purchaseOrders()->whereIn('status', ['Pending', 'Revisi'])->orderByDesc('po_date')->get();
        $historyPOs = $customer->purchaseOrders()->whereIn('status', ['Goal', 'Tidak Goal'])->orderByDesc('po_date')->get();

        // Ambil data RFQ (Berjalan & Riwayat)
        $activeRFQs  = $customer->rfqs()->whereNotIn('status', [\App\Models\Rfq::STATUS_GOAL, \App\Models\Rfq::STATUS_CANCELLED])->orderByDesc('rfq_date')->get();
        $historyRFQs = $customer->rfqs()->whereIn('status', [\App\Models\Rfq::STATUS_GOAL, \App\Models\Rfq::STATUS_CANCELLED])->orderByDesc('rfq_date')->get();

        return view('customers.show', compact('customer', 'activePOs', 'historyPOs', 'activeRFQs', 'historyRFQs'));
    }

    public function create()
    {
        abort_if(!$this->authUser()->hasPermission('CRUD') && !$this->authUser()->isSales() && !$this->authUser()->isLeader(), 403);

        $salesList = User::whereIn('role', ['Sales', 'Sales Marketing'])->get();

        return view('customers.create', compact('salesList'));
    }

    public function store(Request $request)
    {
        abort_if(!$this->authUser()->hasPermission('CRUD') && !$this->authUser()->isSales() && !$this->authUser()->isLeader(), 403);

        // Validasi: pastikan data yang masuk bersih dan lengkap
        $validated = $request->validate([
            'company_name'  => 'required|string|max:150',
            'brand_name'    => 'nullable|string|max:150',
            'customer_type' => 'nullable|in:PT,CV,Perorangan,Pemerintah,Perusahaan',
            'industry'      => 'nullable|string|max:100',
            'company_scale' => 'nullable|in:Enterprise,Medium,SME',
            'area_category' => 'nullable|string|max:50',
            'region'        => 'nullable|string|max:100',
            'country'       => 'nullable|string|max:100',
            'province'      => 'nullable|string|max:100',
            'city'          => 'nullable|string|max:100',
            'district'      => 'nullable|string|max:100',
            'village'       => 'nullable|string|max:100',
            'postal_code'   => 'nullable|string|max:10',
            'address'       => 'nullable|string',
            'phone'         => 'nullable|string|max:30',
            'email'         => 'nullable|email|max:100',
            'website'       => 'nullable|url|max:200',
            'npwp'          => 'nullable|string|max:30',
            'nib'           => 'nullable|string|max:30',
            'status'        => 'required|in:Prospect,Active,Inactive,Blacklist,Pending,Lead,Rejected',
            'sales_id'      => 'nullable|exists:users,id',
            'notes'         => 'nullable|string',
            'cp_name'       => 'nullable|string|max:100',
            'cp_position'   => 'nullable|string|max:100',
            'cp_email'      => 'nullable|email|max:100',
            'cp_phone'      => 'nullable|string|max:30',
            'division'      => 'nullable|string|max:100',
            'office_phone'      => 'nullable|string|max:30',
            'whatsapp'          => 'nullable|string|max:30',
            'preferred_contact' => 'nullable|in:Phone,WhatsApp,Email',
            'ongkir_pedia'      => 'nullable|numeric|min:0',
            // Billing & shipping addresses
            'billing_addresses'           => 'nullable|array',
            'billing_addresses.*.label'   => 'nullable|string|max:100',
            'billing_addresses.*.address' => 'nullable|string',
            'billing_addresses.*.city'    => 'nullable|string|max:100',
            'billing_addresses.*.province'=> 'nullable|string|max:100',
            'billing_addresses.*.postal_code' => 'nullable|string|max:10',
            'billing_addresses.*.country' => 'nullable|string|max:100',
            'shipping_addresses'           => 'nullable|array',
            'shipping_addresses.*.label'   => 'nullable|string|max:100',
            'shipping_addresses.*.address' => 'nullable|string',
            'shipping_addresses.*.city'    => 'nullable|string|max:100',
            'shipping_addresses.*.province'=> 'nullable|string|max:100',
            'shipping_addresses.*.postal_code' => 'nullable|string|max:10',
            'shipping_addresses.*.country' => 'nullable|string|max:100',
        ]);

        // Strip PT or CV from company name if present (case insensitive)
        $cleanName = preg_replace('/^(PT\.?|CV\.?)\s+|\s*,?\s*(PT\.?|CV\.?)$/i', '', $validated['company_name']);
        $inputName = trim($cleanName);
        $validated['company_name'] = $inputName;

        // ============================================================
        // ALGORITMA SMART EXACT MATCH (Mencegah Duplikasi Trivial)
        // ============================================================
        $existingCustomers = Customer::select('id', 'company_name', 'email', 'sales_id')->with('sales')->get();
        $inputCleanMatch = strtolower(preg_replace('/[^a-z0-9]/i', '', $inputName));

        foreach ($existingCustomers as $existing) {
            // Cek duplikasi email
            if (!empty($validated['email']) && strtolower($validated['email']) === strtolower($existing->email)) {
                $ownerName = $existing->sales ? $existing->sales->name : 'Admin';
                return back()
                    ->withInput()
                    ->withErrors([
                        'email' => "Perusahaan dengan email \"{$existing->email}\" sudah terdaftar dan dipegang oleh Sales \"{$ownerName}\".",
                    ]);
            }

            // Bandingkan nama yang sudah dibersihkan (tanpa PT/CV dan tanpa spasi/simbol)
            $existingClean = trim(preg_replace('/^(PT\.?|CV\.?)\s+|\s*,?\s*(PT\.?|CV\.?)$/i', '', $existing->company_name));
            $existingCleanMatch = strtolower(preg_replace('/[^a-z0-9]/i', '', $existingClean));

            if ($inputCleanMatch === $existingCleanMatch) {
                $ownerName = $existing->sales ? $existing->sales->name : 'Admin';
                // Nama sudah ditemukan di database â€” tolak pendaftaran
                return back()
                    ->withInput()
                    ->withErrors([
                        'company_name' => "Perusahaan dengan nama yang sama (\"" . $existing->company_name . "\") sudah terdaftar dan dipegang oleh Sales \"{$ownerName}\". Anda tidak bisa mendaftarkan perusahaan yang sama.",
                    ]);
            }
        }

        $validated['created_by'] = Auth::id();
        $validated['sales_id'] = !empty($validated['sales_id']) ? (int) $validated['sales_id'] : null;

        // Kalau yang input adalah Sales:
        // 1. Assign otomatis ke dirinya sendiri
        // 2. Status wajib Pending (tunggu approval Admin)
        // 3. Kode perusahaan BELUM dibuat (baru dibuat setelah Admin Approve)
        // Kalau yang input adalah Sales:
        // 1. Assign otomatis ke dirinya sendiri
        // 2. Status wajib Pending (tunggu approval Admin)
        // 3. Kode perusahaan BELUM dibuat (baru dibuat setelah Admin Approve)
        // 4. NPWP dan NIB HANYA BISA DIINPUT OLEH ADMIN
        if (!$this->authUser()->isAdminOrAbove()) {
            $validated['sales_id']     = Auth::id();
            $validated['status']       = Customer::STATUS_PROSPECT;
            $validated['company_code'] = null; // Kode dibuat saat Admin approve
            unset($validated['npwp'], $validated['nib']); // Khusus Admin

            // Notifikasi ke semua Admin/Super Admin
            $admins = User::whereIn('role', ['Admin', 'Admin Purchase', 'Super Admin'])->get();
            foreach ($admins as $admin) {
                \App\Jobs\CreateNotification::dispatch([
                    'user_id' => $admin->id,
                    'title'   => 'Approval Customer Baru',
                    'message' => $this->authUser()->name . ' menginput "' . $inputName . '" dan butuh persetujuan Anda.',
                    'type'    => 'warning',
                    'link'    => route('approvals.index'),
                ]);
            }
        } else {
            // Admin input langsung: gunakan owner sales yang tersedia, atau fallback ke owner default
            $validated['sales_id'] = Customer::resolveSalesOwner($validated['sales_id']);
            $validated['company_code'] = Customer::generateCompanyCode();
        }

        try {
            DB::beginTransaction();

            $customer = Customer::create($validated);

            // Simpan billing addresses
            if ($request->has('billing_addresses')) {
                foreach ($request->billing_addresses as $ba) {
                    if (!empty(array_filter($ba))) {
                        $customer->billingAddresses()->create($ba);
                    }
                }
            }

            // Simpan shipping addresses
            if ($request->has('shipping_addresses')) {
                foreach ($request->shipping_addresses as $sa) {
                    if (!empty(array_filter($sa))) {
                        $customer->shippingAddresses()->create($sa);
                    }
                }
            }

            // Simpan daftar kontak person (1:N)
            if ($request->has('contacts')) {
                foreach ($request->contacts as $contactData) {
                    if (empty(trim($contactData['name'] ?? ''))) continue;

                    $customer->contacts()->create([
                        'name'              => trim($contactData['name']),
                        'position'          => $contactData['position']    ?? null,
                        'phone'             => $contactData['phone']       ?? null,
                        'office_phone'      => $contactData['office_phone'] ?? null,
                        'whatsapp'          => $contactData['whatsapp']    ?? null,
                        'email'             => $contactData['email']       ?? null,
                        'division'          => $contactData['division']    ?? null,
                        'preferred_contact' => $contactData['preferred_contact'] ?? null,
                        'is_primary'        => isset($contactData['is_primary']),
                    ]);
                }
            }

            DB::commit();

            $msg = $this->authUser()->isAdminOrAbove()
                ? "Customer {$inputName} berhasil ditambahkan!"
                : "Customer {$inputName} berhasil dikirim dan menunggu persetujuan Admin.";

            return redirect()->route('customers.index')->with('success', $msg);
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->route('customers.index')
                ->with('error', 'Gagal menyimpan customer: ' . $e->getMessage());
        }
    }

    public function edit(Customer $customer)
    {
        abort_if(!$this->authUser()->hasPermission('CRUD') && !$this->authUser()->isSales() && !$this->authUser()->isLeader(), 403);

        // Sales hanya boleh edit customer miliknya sendiri
        if (!$this->authUser()->isAdminOrAbove() && $customer->sales_id !== Auth::id()) {
            abort(403, 'Anda tidak berhak mengedit customer ini.');
        }

        $salesList = User::whereIn('role', ['Sales', 'Sales Marketing'])->get();

        // Load billing & shipping addresses AND contacts for the form
        $customer->load('billingAddresses', 'shippingAddresses', 'contacts');

        return view('customers.edit', compact('customer', 'salesList'));

    }

    public function update(Request $request, Customer $customer)
    {
        abort_if(!$this->authUser()->hasPermission('CRUD') && !$this->authUser()->isSales() && !$this->authUser()->isLeader(), 403);

        if (!$this->authUser()->isAdminOrAbove() && $customer->sales_id !== Auth::id()) {
            abort(403, 'Anda tidak berhak mengedit customer ini.');
        }

        $validated = $request->validate([
            'company_name'  => 'required|string|max:150',
            'brand_name'    => 'nullable|string|max:150',
            'customer_type' => 'nullable|in:PT,CV,Perorangan,Pemerintah,Perusahaan',
            'industry'      => 'nullable|string|max:100',
            'company_scale' => 'nullable|in:Enterprise,Medium,SME',
            'area_category' => 'nullable|string|max:50',
            'region'        => 'nullable|string|max:100',
            'country'       => 'nullable|string|max:100',
            'province'      => 'nullable|string|max:100',
            'city'          => 'nullable|string|max:100',
            'district'      => 'nullable|string|max:100',
            'village'       => 'nullable|string|max:100',
            'postal_code'   => 'nullable|string|max:10',
            'address'       => 'nullable|string',
            'phone'         => 'nullable|string|max:30',
            'email'         => 'nullable|email|max:100',
            'website'       => 'nullable|url|max:200',
            'npwp'          => 'nullable|string|max:30',
            'nib'           => 'nullable|string|max:30',
            'status'        => 'required|in:Prospect,Active,Inactive,Blacklist,Pending,Lead,Rejected',
            'sales_id'      => 'nullable|exists:users,id',
            'notes'         => 'nullable|string',
            'cp_name'       => 'nullable|string|max:100',
            'cp_position'   => 'nullable|string|max:100',
            'cp_email'      => 'nullable|email|max:100',
            'cp_phone'      => 'nullable|string|max:30',
            'division'      => 'nullable|string|max:100',
            'office_phone'      => 'nullable|string|max:30',
            'whatsapp'          => 'nullable|string|max:30',
            'preferred_contact' => 'nullable|in:Phone,WhatsApp,Email',
            'ongkir_pedia'      => 'nullable|numeric|min:0',

            // Billing & shipping addresses
            'billing_addresses'           => 'nullable|array',
            'billing_addresses.*.label'   => 'nullable|string|max:100',
            'billing_addresses.*.address' => 'nullable|string',
            'billing_addresses.*.city'    => 'nullable|string|max:100',
            'billing_addresses.*.province'=> 'nullable|string|max:100',
            'billing_addresses.*.postal_code' => 'nullable|string|max:10',
            'billing_addresses.*.country' => 'nullable|string|max:100',
            'shipping_addresses'           => 'nullable|array',
            'shipping_addresses.*.label'   => 'nullable|string|max:100',
            'shipping_addresses.*.address' => 'nullable|string',
            'shipping_addresses.*.city'    => 'nullable|string|max:100',
            'shipping_addresses.*.province'=> 'nullable|string|max:100',
            'shipping_addresses.*.postal_code' => 'nullable|string|max:10',
            'shipping_addresses.*.country' => 'nullable|string|max:100',
        ]);

        // Strip PT or CV from company name if present (case insensitive)
        $cleanName = preg_replace('/^(PT\.?|CV\.?)\s+|\s*,?\s*(PT\.?|CV\.?)$/i', '', $validated['company_name']);
        $validated['company_name'] = trim($cleanName);

        // Sales tidak bisa pindah-pindah ownership customer, manipulasi status, atau ubah NPWP/NIB
        if (!$this->authUser()->isAdminOrAbove()) {
            unset($validated['sales_id'], $validated['status'], $validated['npwp'], $validated['nib']);
        }

        try {
            DB::beginTransaction();

            $customer->update($validated);

            // Sync billing addresses: hapus existing, simpan ulang
            if ($request->has('billing_addresses')) {
                $customer->billingAddresses()->delete();
                foreach ($request->billing_addresses as $ba) {
                    if (!empty(array_filter($ba))) {
                        $customer->billingAddresses()->create($ba);
                    }
                }
            }

            // Sync shipping addresses
            if ($request->has('shipping_addresses')) {
                $customer->shippingAddresses()->delete();
                foreach ($request->shipping_addresses as $sa) {
                    if (!empty(array_filter($sa))) {
                        $customer->shippingAddresses()->create($sa);
                    }
                }
            }

            // Gap #9: Sync contacts (Daftar Kontak Person 1:N)
            // Algoritma: Delete-then-recreate yang aman.
            // 1. Kumpulkan semua ID kontak yang dikirim dari form
            // 2. Hapus kontak yang sudah tidak ada di form
            // 3. Update yang sudah ada, atau create yang baru
            if ($request->has('contacts')) {
                $submittedIds = collect($request->contacts)
                    ->pluck('id')
                    ->filter()
                    ->values();

                // Hapus kontak yang dihilangkan user dari form
                $customer->contacts()->whereNotIn('id', $submittedIds)->delete();

                foreach ($request->contacts as $contactData) {
                    // Bersihkan: abaikan baris yang nama-nya kosong
                    if (empty(trim($contactData['name'] ?? ''))) {
                        continue;
                    }

                    $payload = [
                        'name'              => trim($contactData['name']),
                        'position'          => $contactData['position']    ?? null,
                        'phone'             => $contactData['phone']       ?? null,
                        'office_phone'      => $contactData['office_phone'] ?? null,
                        'whatsapp'          => $contactData['whatsapp']    ?? null,
                        'email'             => $contactData['email']       ?? null,
                        'division'          => $contactData['division']    ?? null,
                        'preferred_contact' => $contactData['preferred_contact'] ?? null,
                        'is_primary'        => isset($contactData['is_primary']),
                    ];

                    if (!empty($contactData['id'])) {
                        // Kontak lama: UPDATE
                        $customer->contacts()->where('id', $contactData['id'])->update($payload);
                    } else {
                        // Kontak baru: CREATE
                        $customer->contacts()->create($payload);
                    }
                }
            }

            DB::commit();


            return redirect()->route('customers.index')
                ->with('success', "Data {$customer->company_name} berhasil diperbarui!");
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->route('customers.index')
                ->with('error', 'Gagal memperbarui customer: ' . $e->getMessage());
        }
    }
    public function destroy(Customer $customer)
    {
        abort_if(!$this->authUser()->isAdminOrAbove(), 403);

        $name = $customer->company_name;
        $customer->delete();

        return redirect()->route('customers.index')
            ->with('success', "Customer {$name} berhasil diarsipkan.");
    }

    public function trash()
    {
        abort_if(!$this->authUser()->isAdminOrAbove(), 403);

        $customers = Customer::onlyTrashed()
            ->with('sales')
            ->orderByDesc('deleted_at')
            ->paginate(10);

        return view('customers.trash', compact('customers'));
    }

    public function restore(int $id)
    {
        abort_if(!$this->authUser()->isAdminOrAbove(), 403);

        $customer = Customer::onlyTrashed()->findOrFail($id);
        $customer->restore();

        return redirect()->route('customers.trash')
            ->with('success', "Customer {$customer->company_name} berhasil dipulihkan.");
    }

    public function export()
    {
        abort_if(!$this->authUser()->hasPermission('CRUD') && !$this->authUser()->isSales() && !$this->authUser()->isLeader(), 403);

        return Excel::download(
            new CustomerExport,
            'customers-' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    public function previewImport(Request $request)
    {
        abort_if(!$this->authUser()->hasPermission('CRUD') && !$this->authUser()->isLeader(), 403, 'Anda tidak memiliki akses untuk import data master.');

        $request->validate([
            'file' => 'required|mimes:xlsx,csv,xls|max:5120',
        ]);

        try {
            // Simpan file sementara
            $file = $request->file('file');
            $fileName = 'import_' . auth()->id() . '_' . time() . '.' . $file->getClientOriginalExtension();
            
            // Simpan langsung ke path yang diinginkan untuk menghindari perbedaan disk 'local' di Laravel 11
            $destinationPath = storage_path('app/temp/imports');
            $file->move($destinationPath, $fileName);
            $path = 'temp/imports/' . $fileName;
            $fullPath = storage_path('app/' . $path);

            // Cari baris header secara dinamis (baca 15 baris pertama saja untuk performa)
            $rawArray = \Maatwebsite\Excel\Facades\Excel::toArray(new class implements \Maatwebsite\Excel\Concerns\ToArray, \Maatwebsite\Excel\Concerns\WithLimit {
                public function limit(): int { return 15; }
                public function array(array $array) {}
            }, $fullPath);
            
            $sheetData = $rawArray[0] ?? [];
            $headerRowIndex = 1;
            
            foreach ($sheetData as $index => $row) {
                // Konversi row menjadi string gabungan untuk dicek
                $rowString = strtolower(implode(' ', array_map('strval', $row)));
                if (strpos($rowString, 'nama perusahaan') !== false || strpos($rowString, 'company name') !== false || strpos($rowString, 'perusahaan') !== false || strpos($rowString, 'nama_perusahaan') !== false) {
                    $headerRowIndex = $index + 1; // Index 0-based, Excel row 1-based
                    break;
                }
            }

            // Baca header dari baris yang terdeteksi
            $headingArray = \Maatwebsite\Excel\Facades\Excel::toArray(new \Maatwebsite\Excel\HeadingRowImport($headerRowIndex), $fullPath);
            $headings = $headingArray[0][0] ?? [];

            // Kolom standar yang tidak perlu di-map ke Sales
            $knownHeaders = [
                'no', 'nama_perusahaan', 'kawasan', 'kota', 'date_last_request', 
                'status_aktivitas', 'catatan', 'pic', 'no_telp', 'kolom_14', 
                'brand', 'jenis_pelanggan', 'industri', 'skala_perusahaan', 
                'kategori_area', 'nib', 'divisi', 'alamat', 'provinsi', 
                'kode_pos', 'negara', 'telp', 'telepon_kantor', 'whatsapp', 
                'preferred_contact', 'email', 'website', 'npwp', 'status', 
                'company_code', 'cp_nama', 'cp_jabatan', 'cp_email', 'cp_telepon',
                'nama_pt', 'perusahaan', 'nama_client', 'client', 'merek', 'brand_name',
                'jenis_perusahaan', 'tipe', 'sektor', 'bidang', 'bidang_usaha',
                'skala', 'kategori', 'region', 'area', 'wilayah', 'lokasi',
                'no_nib', 'departemen', 'alamat_lengkap', 'kabupaten', 'kodepos',
                'telepon', 'phone', 'telp_kantor', 'wa', 'no_wa', 'kontak_pilihan',
                'alamat_email', 'e_mail', 'situs', 'web', 'no_npwp', 'keterangan', 'notes',
                'nama_pic', 'contact_person', 'jabatan_pic', 'jabatan', 'email_pic', 'telp_pic', 'hp_pic'
            ];
            
            $unknownHeaders = array_diff($headings, $knownHeaders);
            // Buang yang kosong atau hanya berupa angka (ghost columns dari Excel yang terdeteksi sebagai index)
            $unknownHeaders = array_filter($unknownHeaders, function($header) {
                return !empty(trim($header)) && !is_numeric($header);
            });

            // Ambil data User untuk pilihan mapping
            $users = User::whereIn('role', ['Sales', 'Admin', 'Admin Purchase', 'Super Admin'])->get();

            return view('customers.import-mapping', compact('path', 'unknownHeaders', 'users', 'headerRowIndex'));
        } catch (\Throwable $e) {
            return back()->with('error', 'Terjadi kesalahan sistem: ' . $e->getMessage() . ' | File: ' . basename($e->getFile()) . ' | Baris: ' . $e->getLine());
        }
    }

    public function processImport(Request $request)
    {
        abort_if(!$this->authUser()->hasPermission('CRUD') && !$this->authUser()->isLeader(), 403, 'Anda tidak memiliki akses untuk import data master.');

        $request->validate([
            'file_path' => 'required|string',
            'mapping' => 'nullable|array'
        ]);

        $fullPath = storage_path('app/' . $request->file_path);
        if (!file_exists($fullPath)) {
            return redirect()->route('customers.index')->with('error', 'File import tidak ditemukan atau sudah kadaluarsa. Silakan upload ulang.');
        }

        try {
            $pendingBefore = Customer::whereIn('status', ['Prospect', 'Pending'])->count();
            
            // Ambil header_row dari request, default ke 1 jika tidak ada
            $headerRow = (int) $request->input('header_row', 1);

            // Buat instance importer dengan mapping dan headerRow
            $mapping = $request->mapping ?? [];
            $importer = new \App\Imports\CustomerImport($mapping, $headerRow);
            \Maatwebsite\Excel\Facades\Excel::import($importer, $fullPath);

            // Ambil laporan: baris mana saja yang gagal
            $failedRows    = $importer->getFailedRows();
            $failedCount   = count($failedRows);
            $importedCount = $importer->importedCount;

            // Hapus file temporary
            @unlink($fullPath);

            // Notifikasi ke Admin jika ada Customer Pending baru dari Sales
            $pendingAfter = Customer::whereIn('status', ['Prospect', 'Pending'])->count();
            $newPending   = $pendingAfter - $pendingBefore;

            if ($newPending > 0 && !$this->authUser()->isAdminOrAbove()) {
                $admins = User::whereIn('role', ['Admin', 'Admin Purchase', 'Super Admin'])->get();
                foreach ($admins as $admin) {
                    \App\Jobs\CreateNotification::dispatch([
                        'user_id' => $admin->id,
                        'title'   => 'Approval Bulk Customer Baru',
                        'message' => $this->authUser()->name . ' telah mengimport ' . $newPending . ' data customer yang butuh Approval.',
                        'type'    => 'warning',
                        'link'    => route('customers.index', ['status' => 'Prospect']),
                    ]);
                }
            }

            // Susun pesan laporan yang informatif sesuai kondisi hasil import
            if ($importedCount === 0 && $failedCount > 0) {
                $failedList = implode(', ', array_slice($failedRows, 0, 5));
                $msg = "Semua baris gagal di-import ({$failedCount} baris). Pastikan format benar atau data tidak duplikat. (Baris: {$failedList})";
                return redirect()->route('customers.index')->with('error', $msg);
            } elseif ($failedCount > 0) {
                $failedList = implode(', ', array_slice($failedRows, 0, 5));
                $msg = "Berhasil mengimport {$importedCount} data. Terdapat {$failedCount} baris yang gagal/dilewati (Baris: {$failedList} " . ($failedCount > 5 ? 'dll' : '') . ").";
                return redirect()->route('customers.index')->with('warning', $msg);
            }

            return redirect()->route('customers.index')->with('success', "Data Customer berhasil di-import ({$importedCount} data).");

        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            @unlink($fullPath);
            $failures = $e->failures();
            $errorMsg = "Gagal Import. Baris {$failures[0]->row()}: " . implode(', ', $failures[0]->errors());
            return redirect()->route('customers.index')->with('error', $errorMsg);
        } catch (\Exception $e) {
            @unlink($fullPath);
            return redirect()->route('customers.index')->with('error', 'Gagal memproses import. Pastikan format file sudah sesuai. ' . $e->getMessage());
        }
    }

    public function approve(Customer $customer)
    {
        abort_if(!$this->authUser()->isAdminOrAbove(), 403);

        if (!in_array($customer->status, Customer::pendingApprovalStatuses(), true)) {
            return redirect()->back()->with('error', 'Customer ini tidak berstatus Prospect.');
        }

        // Generate kode perusahaan di titik ini (bukan saat Sales input)
        $companyCode = $customer->company_code
            ?? Customer::generateCompanyCode();

        $customer->update([
            'status'       => 'Active',
            'company_code' => $companyCode,
            'approved_by'  => Auth::id(),
            'approved_at'  => now(),
        ]);

        // Kirim notifikasi ke Sales pemilik agar dia tahu customernya sudah disetujui
        if ($customer->sales_id) {
            \App\Jobs\CreateNotification::dispatch([
                'user_id' => $customer->sales_id,
                'title'   => 'Customer Disetujui! ✅',
                'message' => "Customer \"" . $customer->company_name . "\" (Kode: {$companyCode}) telah disetujui oleh " . $this->authUser()->name . ". Anda sekarang bisa membuat RFQ / PO untuk customer ini.",
                'type'    => 'success',
                'link'    => route('customers.show', $customer),
            ]);
        }

        return redirect()->back()->with('success', "Customer {$customer->company_name} berhasil di-approve (Status: Active, Kode: {$companyCode}).");
    }

    private function authUser(): User
    {
        /** @var User $user */
        $user = Auth::user();
        return $user;
    }

    public function checkDuplicate(Request $request)
    {
        $query = $request->input('q');
        $email = $request->input('email');

        if (empty($query) && empty($email)) {
            return response()->json([]);
        }

        if (!empty($query)) {
            // Bersihkan input: buang PT/CV di awal/akhir dan semua simbol non-alphanumeric
            $rawClean = trim(preg_replace('/^(PT\.?|CV\.?)\s+|\s*,?\s*(PT\.?|CV\.?)$/i', '', $query));
            $cleanInput = strtolower(preg_replace('/[^a-z0-9]/i', '', $rawClean));

            if (strlen($cleanInput) < 3) {
                return response()->json([]);
            }

            $all = Customer::with('sales:id,name')->get(['id', 'company_name', 'email', 'sales_id']);
            $results = collect();
            
            foreach ($all as $c) {
                $existingRaw = trim(preg_replace('/^(PT\.?|CV\.?)\s+|\s*,?\s*(PT\.?|CV\.?)$/i', '', $c->company_name));
                $existingClean = strtolower(preg_replace('/[^a-z0-9]/i', '', $existingRaw));
                
                $isExact = ($cleanInput === $existingClean);
                $isSimilar = str_contains($existingClean, $cleanInput) || str_contains($cleanInput, $existingClean);

                if ($isExact || $isSimilar) {
                    $results->push([
                        'id'           => $c->id,
                        'company_name' => $c->company_name,
                        'email'        => $c->email,
                        'owner'        => $c->sales?->name ?? 'Admin',
                        'is_exact'     => $isExact,
                    ]);
                }
            }

            // Urutkan exact match paling atas, lalu ambil hasil unik berdasarkan company_name
            $sorted = $results->sortByDesc('is_exact')->unique('company_name')->values();
            return response()->json($sorted);
        }

        if (!empty($email)) {
            $match = Customer::with('sales:id,name')
                ->where('email', $email)
                ->first(['id', 'company_name', 'email', 'sales_id']);
            if ($match) {
                return response()->json([[
                    'id'           => $match->id,
                    'company_name' => $match->company_name,
                    'email'        => $match->email,
                    'owner'        => $match->sales?->name ?? 'Admin',
                    'is_exact'     => true,
                ]]);
            }
        }

        return response()->json([]);
    }

    public function bulkApprove(Request $request)
    {
        abort_if(!$this->authUser()->isAdminOrAbove(), 403);

        $request->validate([
            'customer_ids'   => 'required|array',
            'customer_ids.*' => 'exists:customers,id',
        ]);

        $pendingCustomers = Customer::whereIn('id', $request->customer_ids)
            ->whereIn('status', Customer::pendingApprovalStatuses())
            ->get();

        $count = 0;
        foreach ($pendingCustomers as $customer) {
            $companyCode = $customer->company_code
                ?? Customer::generateCompanyCode();

            $customer->update([
                'status'       => 'Active',
                'company_code' => $companyCode,
                'approved_by'  => Auth::id(),
                'approved_at'  => now(),
            ]);

            // Notifikasi balik ke Sales pemilik masing-masing
            if ($customer->sales_id) {
                \App\Jobs\CreateNotification::dispatch([
                    'user_id' => $customer->sales_id,
                    'title'   => 'Customer Disetujui! ✅',
                    'message' => "Customer \"" . $customer->company_name . "\" (Kode: {$companyCode}) telah disetujui. Anda bisa langsung buat RFQ / PO sekarang.",
                    'type'    => 'success',
                    'link'    => route('customers.show', $customer),
                ]);
            }

            $count++;
        }

        return redirect()->back()->with('success', "Berhasil melakukan Bulk Approve untuk {$count} customer. Semua status diubah ke Active.");
    }

    public function storeContactAjax(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:150',
            'position' => 'nullable|string|max:100',
            'phone'    => 'nullable|string|max:20',
            'email'    => 'nullable|email|max:100',
        ]);

        $contact = $customer->contacts()->create($validated);

        return response()->json([
            'status'  => 'success',
            'contact' => $contact
        ]);
    }
}
