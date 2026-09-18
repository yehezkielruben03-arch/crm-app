<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Notification;
use App\Models\Rfq;
use App\Models\RfqItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RfqController extends Controller
{
    public function index(Request $request)
    {
        $user = $this->authUser();
        $query = Rfq::with(['customer', 'sales', 'items']);

        if (!$user->isAdminOrAbove()) {
            $query->where('sales_id', $user->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('rfq_number', 'like', '%' . $search . '%')
                  ->orWhere('customer_name', 'like', '%' . $search . '%')
                  ->orWhere('sales_name', 'like', '%' . $search . '%')
                  ->orWhere('customer_code', 'like', '%' . $search . '%')
                  ->orWhere('type', 'like', '%' . $search . '%');
            });
        }

        $rfqs = $query->orderByDesc('created_at')->paginate(10)->withQueryString();

        return view('rfqs.index', compact('rfqs'));
    }

    public function createProject()
    {
        abort_if(!$this->authUser()->isSuperAdmin() && !$this->authUser()->isAdmin(), 403);
        $customers = Customer::where('status', 'Active')->orderBy('company_name')->get();
        return view('rfqs.create_project', compact('customers'));
    }

    public function storeProject(Request $request)
    {
        abort_if(!$this->authUser()->isSuperAdmin() && !$this->authUser()->isAdmin(), 403);

        $validated = $request->validate([
            'customer_id'             => 'required|exists:customers,id',
            'customer_contact_id'     => 'required|exists:customer_contacts,id',
            'need_date'               => 'nullable|date',
            'priority'                => 'nullable|string|in:Normal,Urgent,High Priority',
            'notes'                   => 'nullable|string',
            'items'                   => 'required|array|min:1',
            'items.*.category'        => 'nullable|string',
            'items.*.product_name'    => 'required|string',
            'items.*.qty'             => 'required|numeric|min:0.01',
            'items.*.unit'            => 'nullable|string|max:30',
            'items.*.detail_item'     => 'nullable|string',
            'items.*.description'     => 'nullable|string',
            'items.*.hpp'             => 'required|numeric|min:0',
            'items.*.ongkir_pedia'    => 'required|numeric|min:0',
            'items.*.ongkir_pelanggan'=> 'required|numeric|min:0',
            'items.*.margin'          => 'required|numeric|min:0',
            'items.*.ceiling'         => 'required|numeric|min:1',
            'items.*.validity_days'   => 'required|numeric|min:1',
        ]);

        $customer = Customer::with('sales')->findOrFail($validated['customer_id']);
        $salesId   = $customer->sales_id ?? Auth::id();
        $salesName = $customer->sales ? $customer->sales->name : $this->authUser()->name;

        try {
            DB::beginTransaction();

            $rfq = Rfq::create([
                'rfq_number'          => Rfq::generateRfqNumber(),
                'customer_id'         => $customer->id,
                'customer_name'       => $customer->company_name,
                'customer_code'       => $customer->company_code,
                'customer_contact_id' => $validated['customer_contact_id'],
                'need_date'           => $validated['need_date'] ?? null,
                'type'                => 'Projek',
                'sales_id'            => $salesId,
                'sales_name'          => $salesName,
                'rfq_date'            => now()->format('Y-m-d'),
                'notes'               => $validated['notes'] ?? null,
                'status'              => Rfq::STATUS_PENDING_LEADER,
            ]);

            foreach ($validated['items'] as $itemData) {
                $hpp              = $itemData['hpp'] ?? 0;
                $ongkir_pedia     = $itemData['ongkir_pedia'] ?? 0;
                $ongkir_pelanggan = $itemData['ongkir_pelanggan'] ?? 0;
                $marginPct        = (float) ($itemData['margin'] ?? 0);
                $ceiling          = (float) ($itemData['ceiling'] ?? 1);

                $item = $rfq->items()->create([
                    'category'           => $itemData['category'] ?? null,
                    'product_name'       => $itemData['product_name'],
                    'qty'                => $itemData['qty'],
                    'unit'               => $itemData['unit'] ?? null,
                    'detail_item'        => $itemData['detail_item'] ?? null,
                    'description'        => $itemData['description'] ?? null,
                    'hpp'                => $hpp,
                    'ongkir_pedia'       => $ongkir_pedia,
                    'ongkir_pelanggan'   => $ongkir_pelanggan,
                    'margin_type'        => 'percentage', // Default untuk projek
                    'margin_value'       => $marginPct,
                    'custom_ceiling'     => $ceiling,
                    'validity_days'      => $itemData['validity_days'] ?? 7,
                    'price_after_margin' => 0, // Akan dihitung ulang di bawah
                ]);

                // Gunakan fungsi sentral agar hitungan persis sama dengan Fase 4
                $item->price_after_margin = $item->calculatePriceAfterMargin();
                $item->save();
            }

            DB::commit();

            $this->clearDashboardCacheForSales($salesId);

        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->route('rfq.index')
                ->with('error', 'Gagal membuat RFQ Projek: ' . $e->getMessage());
        }

        // RFQ Projek dibuat Admin → minta approval Leader (sinkron, tanpa queue)
        $leaders = \App\Models\User::where('role', 'Leader')->get();
        foreach ($leaders as $leader) {
            \App\Models\Notification::send(
                userId:  $leader->id,
                type:    'warning',
                title:   'Approval Harga RFQ Projek',
                message: 'Admin telah membuat RFQ Projek ' . $rfq->rfq_number . ' dan menunggu persetujuan Anda.',
                link:    route('rfq.show', $rfq)
            );
        }

        return redirect()->route('rfq.show', $rfq)
            ->with('success', 'RFQ Projek ' . $rfq->rfq_number . ' berhasil dibuat dan diteruskan ke Leader untuk approval!');
    }

    public function create()
    {
        abort_if(!$this->authUser()->isAdminOrAbove() && !$this->authUser()->isSales(), 403);

        $user = $this->authUser();
        $customers = $user->isAdminOrAbove()
            ? Customer::where('status', 'Active')->orderBy('company_name')->get()
            : Customer::where('sales_id', $user->id)->where('status', 'Active')->orderBy('company_name')->get();

        return view('rfqs.create', compact('customers'));
    }

    public function store(Request $request)
    {
        abort_if(!$this->authUser()->isAdminOrAbove() && !$this->authUser()->isSales(), 403);

        $validated = $request->validate([
            'customer_id'             => 'required|exists:customers,id',
            'customer_contact_id'     => 'required|exists:customer_contacts,id',
            'need_date'               => 'nullable|date',
            'type'                    => 'required|string|in:Projek,Non Projek',
            'priority'                => 'nullable|string|in:Normal,Urgent,High Priority',
            'notes'                   => 'nullable|string',
            'attachment'              => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg|max:5120',
            'items'                   => 'required|array|min:1',
            'items.*.category'        => 'nullable|string',
            'items.*.product_name'    => 'required|string',
            'items.*.qty'             => 'required|numeric|min:0.01',
            'items.*.unit'            => 'nullable|string|max:30',
            'items.*.detail_item'     => 'nullable|string',
            'items.*.description'     => 'nullable|string',
            'items.*.hpp'             => 'nullable|numeric|min:0',
            'items.*.ongkir_pedia'    => 'nullable|numeric|min:0',
            'items.*.ongkir_pelanggan'=> 'nullable|numeric|min:0',
            'items.*.margin'          => 'nullable|numeric|min:0',
            'items.*.ceiling'         => 'nullable|numeric|min:1',
            'items.*.validity_days'   => 'nullable|numeric|min:1',
        ]);

        // NOTE: Sales & Admin sama-sama boleh membuat RFQ tipe Projek lewat form ini.

        $customer = Customer::with('sales')->findOrFail($validated['customer_id']);

        if (!$this->authUser()->isAdminOrAbove()) {
            abort_if($customer->sales_id !== Auth::id(), 403);
        }

        $salesId = $customer->sales_id ?? Auth::id();
        if (!$this->authUser()->isAdminOrAbove()) {
            $salesId = Auth::id();
        }
        $salesName = $customer->sales ? $customer->sales->name : $this->authUser()->name;

        // ─────────────────────────────────────────────────────────────────────
        // Validasi integritas data: cegah bug "KATEGORI TIDAK DISEBUTKAN"
        // Jika type=Projek, setiap item WAJIB punya category.
        // Jika type=Non Projek, paksa category=null agar tidak ada data bocor.
        // ─────────────────────────────────────────────────────────────────────
        if ($validated['type'] === 'Projek') {
            foreach ($validated['items'] as $idx => $item) {
                if (empty($item['category'])) {
                    return back()
                        ->withErrors(['items' => 'Setiap item pada RFQ Projek wajib memiliki kategori. Silakan lengkapi data item.'])
                        ->withInput();
                }
            }
        } else {
            // Non Projek: pastikan category selalu null (buang jika ada sisa dari form)
            foreach ($validated['items'] as &$item) {
                $item['category'] = null;
            }
            unset($item);
        }

        try {
            DB::beginTransaction();

            $attachmentPath = null;
            $attachmentName = null;
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $attachmentPath = $file->store('rfq_attachments', 'public');
                $attachmentName = $file->getClientOriginalName();
            }

            $rfq = Rfq::create([
                'rfq_number'           => Rfq::generateRfqNumber(),
                'customer_id'          => $customer->id,
                'customer_name'        => $customer->company_name,
                'customer_code'        => $customer->company_code,
                'customer_contact_id'  => $validated['customer_contact_id'],
                'need_date'            => $validated['need_date'] ?? null,
                'type'                 => $validated['type'],
                'sales_id'             => $salesId,
                'sales_name'           => $salesName,
                'rfq_date'             => now()->format('Y-m-d'),
                'notes'                => $validated['notes'] ?? null,
                'attachment_file_path' => $attachmentPath,
                'attachment_file_name' => $attachmentName,
                'status'               => Rfq::STATUS_PENDING_ADMIN,
            ]);

            foreach ($validated['items'] as $item) {
                $hpp              = (float)($item['hpp'] ?? 0);
                $ongkirPedia      = (float)($item['ongkir_pedia'] ?? 0);
                $ongkirPelanggan  = (float)($item['ongkir_pelanggan'] ?? 0);
                $marginPct        = (float)($item['margin'] ?? 25);      // input dalam % (cth: 25)
                $marginMultiplier = 1 + ($marginPct / 100);                // konversi ke multiplier: 1.25
                $ceiling          = (int)($item['ceiling'] ?? 10000);

                $basePrice         = $hpp + $ongkirPedia + $ongkirPelanggan;
                $priceAfterMargin  = $ceiling > 0 ? ceil(($basePrice * $marginMultiplier) / $ceiling) * $ceiling : 0;
                $cat               = !empty($item['category']) ? RfqItem::normalizeCategory($item['category']) : null;

                $rfq->items()->create([
                    'category'           => $cat,
                    'product_name'       => $item['product_name'],
                    'qty'                => $item['qty'],
                    'unit'               => $item['unit'] ?? null,
                    'detail_item'        => $item['detail_item'] ?? null,
                    'description'        => $item['description'] ?? null,
                    'hpp'                => $hpp,
                    'ongkir_pedia'       => $ongkirPedia,
                    'ongkir_pelanggan'   => $ongkirPelanggan,
                    'margin'             => $marginPct,
                    'ceiling'            => $ceiling,
                    'validity_days'      => (int)($item['validity_days'] ?? 7),
                    'price_after_margin' => $priceAfterMargin,
                ]);
            }

            DB::commit();

            $this->clearDashboardCacheForSales($rfq->sales_id);

        } catch (\Throwable $e) {
            DB::rollBack();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal membuat RFQ: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->route('rfq.index')
                ->with('error', 'Gagal membuat RFQ: ' . $e->getMessage());
        }

        // ============================================================
        // EVENT 1 — Sales selesai membuat RFQ (Non-Projek)
        // → Notifikasi + tanda merah di lonceng ADMIN PURCHASE
        // ============================================================
        try {
            $recipients = User::whereIn('role', [
                'Admin Purchase',
                'Admin',
                'Super Admin',
            ])->get();

            foreach ($recipients as $recipient) {
                Notification::send(
                    userId:  $recipient->id,
                    type:    'warning',
                    title:   'RFQ Baru Diterima',
                    message: 'RFQ Baru diterima dari Sales ' . $rfq->sales_name . '! (' . $rfq->rfq_number . ') untuk ' . $rfq->customer_name . '. Mohon segera diisi HPP.',
                    link:    route('rfq.show', $rfq)
                );
            }
        } catch (\Throwable $e) {
            \Log::warning('Gagal mengirim notifikasi RFQ baru: ' . $e->getMessage());
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success'  => true,
                'redirect' => route('rfq.show', $rfq),
                'message'  => 'RFQ ' . $rfq->rfq_number . ' berhasil dibuat!',
            ], 201);
        }

        return redirect()->route('rfq.show', $rfq)
            ->with('success', 'RFQ ' . $rfq->rfq_number . ' berhasil dibuat!');
    }

    public function show(Rfq $rfq)
    {
        if (!$this->authUser()->isAdminOrAbove() && $rfq->sales_id !== Auth::id()) {
            abort(403);
        }

        $rfq->load(['customer', 'sales', 'items']);

        return view('rfqs.show', compact('rfq'));
    }

    public function downloadAttachment(Rfq $rfq)
    {
        if (!$this->authUser()->isAdminOrAbove() && $rfq->sales_id !== Auth::id()) {
            abort(403, 'Anda tidak memiliki akses ke berkas RFQ ini.');
        }

        if (empty($rfq->attachment_file_path)) {
            abort(404, 'Berkas lampiran tidak ditemukan pada RFQ ini.');
        }

        $filename = $rfq->attachment_file_name ?: basename($rfq->attachment_file_path);

        // Prioritas 1: Storage disk public
        $disk = Storage::disk('public');
        if ($disk->exists($rfq->attachment_file_path)) {
            $path = $disk->path($rfq->attachment_file_path);
            $mime = function_exists('mime_content_type') ? @mime_content_type($path) : null;
            return response()->file($path, [
                'Content-Type'        => $mime ?: 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
            ]);
        }

        // Prioritas 2: Direct storage_path fallback
        $fallbackPath = storage_path('app/public/' . $rfq->attachment_file_path);
        if (file_exists($fallbackPath)) {
            $mime = function_exists('mime_content_type') ? @mime_content_type($fallbackPath) : null;
            return response()->file($fallbackPath, [
                'Content-Type'        => $mime ?: 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
            ]);
        }

        // Prioritas 3: Testing disk fallback jika ada
        $testPath = storage_path('framework/testing/disks/public/' . $rfq->attachment_file_path);
        if (file_exists($testPath)) {
            $mime = function_exists('mime_content_type') ? @mime_content_type($testPath) : null;
            return response()->file($testPath, [
                'Content-Type'        => $mime ?: 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
            ]);
        }

        abort(404, 'File fisik lampiran tidak ditemukan di server.');
    }

    public function edit(Rfq $rfq)
    {
        abort_if(!$this->authUser()->hasPermission('CRUD') && !$this->authUser()->isSales(), 403);
        if (!$this->authUser()->isAdminOrAbove() && $rfq->sales_id !== Auth::id()) {
            abort(403);
        }

        $allowedStatuses = [Rfq::STATUS_PENDING_ADMIN, Rfq::STATUS_APPROVED, Rfq::STATUS_QUOTATION_CREATED];
        abort_if(!in_array($rfq->status, $allowedStatuses), 403, 'RFQ hanya bisa diedit saat status Pending Admin, Approved, atau Quotation Created.');

        $user = $this->authUser();
        $customers = $user->isAdminOrAbove()
            ? Customer::where('status', 'Active')->orderBy('company_name')->get()
            : Customer::where('sales_id', $user->id)->where('status', 'Active')->orderBy('company_name')->get();

        $rfq->load('items');

        return view('rfqs.edit', compact('rfq', 'customers'));
    }

    public function update(Request $request, Rfq $rfq)
    {
        abort_if(!$this->authUser()->hasPermission('CRUD') && !$this->authUser()->isSales(), 403);
        if (!$this->authUser()->isAdminOrAbove() && $rfq->sales_id !== Auth::id()) {
            abort(403);
        }

        $allowedStatuses = [Rfq::STATUS_PENDING_ADMIN, Rfq::STATUS_APPROVED, Rfq::STATUS_QUOTATION_CREATED];
        abort_if(!in_array($rfq->status, $allowedStatuses), 403, 'RFQ hanya bisa diedit saat status Pending Admin, Approved, atau Quotation Created.');

        $validated = $request->validate([
            'customer_id'             => 'required|exists:customers,id',
            'customer_contact_id'     => 'required|exists:customer_contacts,id',
            'need_date'               => 'nullable|date',
            'type'                    => 'required|string|in:Projek,Non Projek',
            'priority'                => 'nullable|string|in:Normal,Urgent,High Priority',
            'notes'                   => 'nullable|string',
            'items'                   => 'required|array|min:1',
            'items.*.category'        => 'nullable|string',
            'items.*.product_name'    => 'required|string',
            'items.*.qty'             => 'required|numeric|min:0.01',
            'items.*.unit'            => 'nullable|string|max:30',
            'items.*.detail_item'     => 'nullable|string',
            'items.*.description'     => 'nullable|string',
            'items.*.hpp'             => 'nullable|numeric|min:0',
            'items.*.ongkir_pedia'    => 'nullable|numeric|min:0',
            'items.*.ongkir_pelanggan'=> 'nullable|numeric|min:0',
            'items.*.margin'          => 'nullable|numeric|min:1',
            'items.*.ceiling'         => 'nullable|numeric|min:1',
            'items.*.validity_days'   => 'nullable|numeric|min:1',
        ]);

        if (!$this->authUser()->isAdminOrAbove()) {
            $customer = Customer::findOrFail($validated['customer_id']);
            abort_if($customer->sales_id !== Auth::id(), 403);
        }

        if ($validated['customer_id'] !== $rfq->customer_id) {
            $newCustomer = Customer::with('sales')->findOrFail($validated['customer_id']);
            $rfq->customer_name = $newCustomer->company_name;
            $rfq->customer_code = $newCustomer->company_code;
            $rfq->sales_id      = $newCustomer->sales_id ?? Auth::id();
            $rfq->sales_name    = $newCustomer->sales ? $newCustomer->sales->name : $this->authUser()->name;

            if (!$this->authUser()->isAdminOrAbove()) {
                $rfq->sales_id   = Auth::id();
                $rfq->sales_name = $this->authUser()->name;
            }
        }

        $wasApproved = in_array($rfq->status, [Rfq::STATUS_APPROVED, Rfq::STATUS_QUOTATION_CREATED]);

        // ─────────────────────────────────────────────────────────────────────
        // Validasi integritas data: cegah bug "KATEGORI TIDAK DISEBUTKAN"
        // Jika type=Projek, setiap item WAJIB punya category.
        // Jika type=Non Projek, paksa category=null agar tidak ada data bocor.
        // ─────────────────────────────────────────────────────────────────────
        if ($validated['type'] === 'Projek') {
            foreach ($validated['items'] as $idx => $item) {
                if (empty($item['category'])) {
                    return back()
                        ->withErrors(['items' => 'Setiap item pada RFQ Projek wajib memiliki kategori. Silakan lengkapi data item.'])
                        ->withInput();
                }
            }
        } else {
            // Non Projek: pastikan category selalu null (buang jika ada sisa dari form)
            foreach ($validated['items'] as &$item) {
                $item['category'] = null;
            }
            unset($item);
        }

        try {
            DB::beginTransaction();

            $rfq->update([
                'customer_id'         => $validated['customer_id'],
                'customer_contact_id' => $validated['customer_contact_id'],
                'need_date'           => $validated['need_date'] ?? null,
                'type'                => $validated['type'],
                'notes'               => $validated['notes'] ?? null,
                'status'              => Rfq::STATUS_PENDING_ADMIN,
            ]);

            $rfq->items()->delete();
            foreach ($validated['items'] as $item) {
                $hpp              = (float)($item['hpp'] ?? 0);
                $ongkirPedia      = (float)($item['ongkir_pedia'] ?? 0);
                $ongkirPelanggan  = (float)($item['ongkir_pelanggan'] ?? 0);
                $marginPct        = (float)($item['margin'] ?? 25);      // input dalam % (cth: 25)
                $marginMultiplier = 1 + ($marginPct / 100);                // konversi ke multiplier: 1.25
                $ceiling          = (int)($item['ceiling'] ?? 10000);

                $basePrice         = $hpp + $ongkirPedia + $ongkirPelanggan;
                $priceAfterMargin  = $ceiling > 0 ? ceil(($basePrice * $marginMultiplier) / $ceiling) * $ceiling : 0;

                $rfq->items()->create([
                    'category'           => $item['category'] ?? null,
                    'product_name'       => $item['product_name'],
                    'qty'                => $item['qty'],
                    'unit'               => $item['unit'] ?? null,
                    'detail_item'        => $item['detail_item'] ?? null,
                    'description'        => $item['description'] ?? null,
                    'hpp'                => $hpp,
                    'ongkir_pedia'       => $ongkirPedia,
                    'ongkir_pelanggan'   => $ongkirPelanggan,
                    'margin'             => $marginPct,
                    'ceiling'            => $ceiling,
                    'validity_days'      => (int)($item['validity_days'] ?? 7),
                    'price_after_margin' => $priceAfterMargin,
                ]);
            }

            if ($wasApproved) {
                $admins = \App\Models\User::whereIn('role', ['Admin Purchase', 'Super Admin'])->get();
                foreach ($admins as $admin) {
                    \App\Models\Notification::send(
                        userId:  $admin->id,
                        type:    'info',
                        title:   'Quotation Direvisi oleh Sales',
                        message: 'Sales ' . $rfq->sales_name . ' telah merevisi item pada RFQ ' . $rfq->rfq_number . '. Mohon periksa kembali HPP/Ongkir.',
                        link:    route('rfq.show', $rfq)
                    );
                }
            }

            DB::commit();

            $this->clearDashboardCacheForSales($rfq->sales_id);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success'  => true,
                    'redirect' => route('rfq.show', $rfq),
                    'message'  => 'Quotation ' . $rfq->rfq_number . ' berhasil direvisi dan dikembalikan ke Admin Purchase untuk pengecekan harga baru!',
                ]);
            }

            return redirect()->route('rfq.show', $rfq)
                ->with('success', 'Quotation ' . $rfq->rfq_number . ' berhasil direvisi dan dikembalikan ke Admin Purchase untuk pengecekan harga baru!');
        } catch (\Throwable $e) {
            DB::rollBack();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal memperbarui RFQ: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->route('rfq.show', $rfq)
                ->with('error', 'Gagal memperbarui RFQ: ' . $e->getMessage());
        }
    }

    public function destroy(Rfq $rfq)
    {
        abort_if(!$this->authUser()->hasPermission('CRUD') && !$this->authUser()->isSales(), 403);
        if (!$this->authUser()->isAdminOrAbove() && $rfq->sales_id !== Auth::id()) {
            abort(403);
        }
        abort_if(!$rfq->isPendingAdmin(), 403, 'RFQ hanya bisa dihapus saat status Pending Admin.');

        $rfqNumber = $rfq->rfq_number;
        $salesId = $rfq->sales_id;
        $rfq->delete();

        if ($salesId) {
            $this->clearDashboardCacheForSales($salesId);
        }

        return redirect()->route('rfq.index')
            ->with('success', 'RFQ ' . $rfqNumber . ' berhasil diarsipkan.');
    }

    public function getCustomer(Customer $customer)
    {
        if (!$this->authUser()->isAdminOrAbove() && $customer->sales_id !== Auth::id()) {
            abort(403);
        }

        $primary = $customer->primaryContact();

        return response()->json([
            'company_name' => $customer->company_name,
            'company_code' => $customer->company_code,
            'address'      => $customer->address,
            'city'         => $customer->city,
            'province'     => $customer->province,
            'phone'        => $customer->phone,
            'email'        => $customer->email,
            'cp_name'      => $primary?->name ?? $customer->cp_name,
            'cp_position'  => $primary?->position ?? $customer->cp_position,
            'cp_email'     => $primary?->email ?? $customer->cp_email,
            'cp_phone'     => $primary?->phone ?? $customer->cp_phone,
            'contacts'     => $customer->contacts,
        ]);
    }

    private function clearDashboardCacheForSales(int $salesId): void
    {
        $ym = now()->format('Y-m');

        Cache::forget('dash:cust_stats:' . $salesId);
        Cache::forget('dash:quo_stats:' . $salesId);
        Cache::forget('dash:cust_health:' . $salesId);
        Cache::forget('dash:po_stats:' . $salesId);
        Cache::forget('dash:rev:' . $salesId . ':' . $ym);
        Cache::forget('dash:goal_cnt:' . $salesId . ':' . $ym);
        Cache::forget('dash:stats:' . $salesId . ':' . $ym);
        Cache::forget('dash:pipeline:' . $salesId);
        Cache::forget('dash:chart:' . $salesId);
        Cache::forget('dash:recent:' . $salesId);
        Cache::forget('dash:recent_quo:' . $salesId);
        Cache::forget('dash:admin:global');
        Cache::forget('dash:admin:rev:' . $ym);
        Cache::forget('dash:admin:chart');
        Cache::forget('dash:admin:perf:' . $ym);
        Cache::forget('dash:admin:recent_quo');
        Cache::forget('dash:admin:recent');
    }

    public function priceForm(Rfq $rfq)
    {
        abort_if(!$this->authUser()->hasPermission('CRUD') && !$this->authUser()->isAdminOrAbove(), 403);
        abort_if(in_array($rfq->status, [Rfq::STATUS_GOAL, Rfq::STATUS_PO_PENDING_ADMIN, Rfq::STATUS_PO_PENDING_LEADER]), 403, 'Harga tidak bisa diubah karena sudah masuk tahap PO atau GOAL.');
        abort_if(!$rfq->canBePriceSubmitted(), 403, 'Harga hanya bisa diisi ulang saat RFQ masih dalam tahap Pending Admin.');

        $rfq->load(['items', 'customer']);
        
        $vendors = \App\Models\Vendor::active()->orderBy('nama_vendor')->get();
        // Tarik rate Portal MP (MP + THR + BPJS KES + BPJS TK)
        $mp = \App\Models\Mainpower::first();
        $mpPediaRate = $mp ? ($mp->total > 0 ? (float)$mp->total : ($mp->mp + $mp->thr + $mp->bpjskes + $mp->bpjstk)) : 336179; // fallback

        $priceHistories = \App\Models\RfqPriceHistory::where('rfq_id', $rfq->id)
            ->with('creator:id,name')
            ->orderByDesc('version')
            ->get();

        return view('rfqs.price_form', compact('rfq', 'vendors', 'mpPediaRate', 'priceHistories'));
    }

    public function submitPrice(Request $request, Rfq $rfq)
    {
        abort_if(!$this->authUser()->hasPermission('CRUD') && !$this->authUser()->isAdminOrAbove(), 403);
        abort_if(in_array($rfq->status, [Rfq::STATUS_GOAL, Rfq::STATUS_PO_PENDING_ADMIN, Rfq::STATUS_PO_PENDING_LEADER]), 403, 'Harga tidak bisa diubah karena sudah masuk tahap PO atau GOAL.');
        abort_if(!$rfq->canBePriceSubmitted(), 403, 'Harga tidak bisa dikirim dari status saat ini.');

        $validated = $request->validate([
            'items'                    => 'required|array',
            'items.*.category'         => 'nullable|string',
            'items.*.vendor_id'        => 'nullable|exists:vendors,id',
            'items.*.product_name'     => 'required|string',
            'items.*.qty'              => 'required|numeric|min:1',
            'items.*.unit'             => 'nullable|string',
            'items.*.description'      => 'nullable|string',
            'items.*.detail_item'      => 'nullable|string|max:5000',
            'items.*.hpp'              => 'required|numeric|min:0',
            'items.*.ongkir_pedia'     => 'nullable|numeric|min:0',
            'items.*.biaya_kirim'      => 'nullable|numeric|min:0',
            'items.*.fee_eu'           => 'nullable|numeric|min:0',
            'items.*.margin_type'      => 'nullable|in:percentage,nominal',
            'items.*.margin_value'     => 'nullable|numeric|min:0',
            'items.*.custom_ceiling'   => 'nullable|numeric|min:0',
            'items.*.validity_days'    => 'nullable|integer|min:1',
        ]);

        $oldStatus = $rfq->status;
        \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $rfq) {
            // Snapshot History (Versioning)
            $currentData = $rfq->items()->get()->toArray();
            $lastVersion = \App\Models\RfqPriceHistory::where('rfq_id', $rfq->id)->max('version') ?? 0;
            
            if (count($currentData) > 0) {
                \App\Models\RfqPriceHistory::create([
                    'rfq_id'       => $rfq->id,
                    'version'      => $lastVersion + 1,
                    'history_data' => $currentData,
                    'created_by'   => $this->authUser()->id,
                ]);
            }

            foreach ($validated['items'] as $itemId => $data) {
                $item = $rfq->items()->find($itemId);
                if ($item) {
                    $item->category         = $data['category'] ?? $item->category;
                    $item->vendor_id        = $data['vendor_id'] ?? null;
                    $item->product_name     = $data['product_name'];
                    $item->qty              = $data['qty'];
                    $item->unit             = $data['unit'] ?? null;
                    $item->description      = $data['description'] ?? null;
                    $item->detail_item      = $data['detail_item'] ?? $item->detail_item;
                    $item->hpp              = $data['hpp'];
                    $item->ongkir_pedia     = (float) ($data['ongkir_pedia'] ?? $item->ongkir_pedia);
                    $item->biaya_kirim      = (float) ($data['biaya_kirim'] ?? 0);
                    $item->fee_eu           = (float) ($data['fee_eu'] ?? 0);
                    $item->margin_type      = $data['margin_type'] ?? 'percentage';
                    $item->margin_value     = (float) ($data['margin_value'] ?? 0);
                    $item->custom_ceiling   = (float) ($data['custom_ceiling'] ?? 0);
                    $item->validity_days    = (int) ($data['validity_days'] ?? ($item->validity_days ?: 7));

                    $item->price_after_margin = $item->calculatePriceAfterMargin();
                    $item->save();
                }
            }

            if ($rfq->canTransitionTo(Rfq::STATUS_PENDING_LEADER)) {
                $rfq->update(['status' => Rfq::STATUS_PENDING_LEADER]);
            }
        });

        if ($rfq->sales_id) {
            $this->clearDashboardCacheForSales($rfq->sales_id);

            $isRevision = $oldStatus !== Rfq::STATUS_PENDING_ADMIN;
            $actionWord = $isRevision ? 'diubah (revisi)' : 'diisi';

            // EVENT 4 — Admin mengubah/mengisi harga → notif ke dashboard SALES terkait
            \App\Models\Notification::send(
                $rfq->sales_id,
                'info',
                'Harga RFQ ' . ($isRevision ? 'Diubah' : 'Diisi') . ' Admin',
                'Harga untuk RFQ ' . $rfq->rfq_number . ' telah ' . $actionWord . ' oleh Admin Purchase. Menunggu persetujuan Leader.',
                route('rfq.show', $rfq)
            );

            // EVENT 2 — Admin selesai isi HPP/Ongkir/Margin → notif + tanda merah di lonceng LEADER untuk Approval
            $leaders = \App\Models\User::where('role', 'Leader')->get();
            foreach ($leaders as $leader) {
                \App\Models\Notification::send(
                    userId:  $leader->id,
                    type:    'warning',
                    title:   'RFQ Membutuhkan Approval',
                    message: 'RFQ ' . $rfq->rfq_number . ' membutuhkan Approval Harga! HPP, Ongkir, dan Margin telah diisi oleh Admin Purchase.',
                    link:    route('rfq.show', $rfq)
                );
            }
        }

        return redirect()->route('rfq.show', $rfq)
            ->with('success', 'Harga untuk RFQ ' . $rfq->rfq_number . ' berhasil disimpan dan diteruskan ke Leader untuk di-approve.');
    }

    public function approve(Rfq $rfq)
    {
        abort_if(!$this->authUser()->isLeader() && !$this->authUser()->isSuperAdmin(), 403);
        abort_if($rfq->status !== Rfq::STATUS_PENDING_LEADER, 403, 'RFQ hanya bisa di-approve saat status Pending Leader.');

        if (!$rfq->canTransitionTo(Rfq::STATUS_APPROVED)) {
            return redirect()->route('rfq.show', $rfq)
                ->with('error', 'RFQ ini tidak bisa disetujui dari status saat ini.');
        }

        $rfq->update(['status' => Rfq::STATUS_APPROVED]);

        if ($rfq->sales_id) {
            $this->clearDashboardCacheForSales($rfq->sales_id);
            // EVENT 3 — Leader Approve → notif + tanda merah di lonceng SALES terkait
            \App\Models\Notification::send(
                $rfq->sales_id,
                'success',
                'RFQ Di-Approve',
                'Quotation ' . $rfq->rfq_number . ' telah di-Approve dan siap di-download!',
                route('rfq.show', $rfq)
            );
        }

        return redirect()->route('rfq.show', $rfq)
            ->with('success', 'RFQ ' . $rfq->rfq_number . ' berhasil disetujui. Dokumen Quotation sudah bisa di-generate.');
    }

    public function reject(Request $request, Rfq $rfq)
    {
        abort_if(!$this->authUser()->isLeader() && !$this->authUser()->isSuperAdmin(), 403);
        abort_if($rfq->status !== Rfq::STATUS_PENDING_LEADER, 403, 'RFQ hanya bisa di-reject saat status Pending Leader.');

        if (!$rfq->canTransitionTo(Rfq::STATUS_PENDING_ADMIN)) {
            return redirect()->route('rfq.show', $rfq)
                ->with('error', 'RFQ ini tidak bisa dikembalikan ke Admin Purchase dari status saat ini.');
        }

        $rfq->update(['status' => Rfq::STATUS_PENDING_ADMIN]);

        if ($rfq->sales_id) {
            $this->clearDashboardCacheForSales($rfq->sales_id);
            // EVENT 3 — Leader Reject → notif + tanda merah di lonceng SALES terkait
            \App\Models\Notification::send(
                $rfq->sales_id,
                'error',
                'Quotation Ditolak',
                'Quotation ' . $rfq->rfq_number . ' ditolak oleh Leader. Harga perlu direvisi.',
                route('rfq.show', $rfq)
            );
        }

        return redirect()->route('rfq.show', $rfq)
            ->with('error', 'RFQ ' . $rfq->rfq_number . ' ditolak dan dikembalikan ke Admin Purchase.');
    }

    public function downloadQuotation(Rfq $rfq)
    {
        abort_if(!in_array($rfq->status, [Rfq::STATUS_APPROVED, Rfq::STATUS_QUOTATION_CREATED, Rfq::STATUS_GOAL]), 403, 'Quotation belum tersedia.');

        if ($rfq->status === Rfq::STATUS_APPROVED) {
            if (!$rfq->canTransitionTo(Rfq::STATUS_QUOTATION_CREATED)) {
                return redirect()->route('rfq.show', $rfq)
                    ->with('error', 'Quotation belum bisa di-generate dari status RFQ saat ini.');
            }

            $rfq->update(['status' => Rfq::STATUS_QUOTATION_CREATED]);
            if ($rfq->sales_id) {
                $this->clearDashboardCacheForSales($rfq->sales_id);
            }
        }

        $rfq->load(['customer', 'customerContact', 'items', 'sales']);
        
        $revisionCount = \App\Models\RfqPriceHistory::where('rfq_id', $rfq->id)->max('version') ?? 0;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('rfqs.pdf', compact('rfq', 'revisionCount'));

        $filename = str_replace('RFQ', 'QUO', $rfq->rfq_number) . '.pdf';

        return $pdf->download($filename);
    }

    public function previewQuotation(Rfq $rfq)
    {
        abort_if(!in_array($rfq->status, [Rfq::STATUS_APPROVED, Rfq::STATUS_QUOTATION_CREATED, Rfq::STATUS_GOAL]), 403, 'Quotation belum tersedia.');

        $rfq->load(['customer', 'customerContact', 'items', 'sales']);
        
        $revisionCount = \App\Models\RfqPriceHistory::where('rfq_id', $rfq->id)->max('version') ?? 0;

        return view('rfqs.preview_quotation', compact('rfq', 'revisionCount'));
    }

    public function editQty(Rfq $rfq)
    {
        abort_if(!$this->authUser()->isSales() && !$this->authUser()->hasPermission('CRUD'), 403);
        abort_if(!in_array($rfq->status, [Rfq::STATUS_APPROVED, Rfq::STATUS_QUOTATION_CREATED, Rfq::STATUS_GOAL]), 403, 'Revisi QTY hanya bisa dilakukan setelah di-approve.');

        $rfq->load('items');
        return view('rfqs.edit_qty', compact('rfq'));
    }

    public function updateQty(Request $request, Rfq $rfq)
    {
        abort_if(!$this->authUser()->isSales() && !$this->authUser()->hasPermission('CRUD'), 403);
        abort_if(!in_array($rfq->status, [Rfq::STATUS_APPROVED, Rfq::STATUS_QUOTATION_CREATED, Rfq::STATUS_GOAL]), 403);

        $request->validate([
            'items'                 => 'nullable|array',
            'items.*.id'            => 'required|exists:rfq_items,id',
            'items.*.product_name'  => 'required|string',
            'items.*.description'   => 'nullable|string',
            'items.*.qty'           => 'required|numeric|min:0.01',
            'items.*.unit'          => 'required|string',
            'new_items'             => 'nullable|array',
            'new_items.*.product_name' => 'required|string',
            'new_items.*.description'  => 'nullable|string',
            'new_items.*.qty'          => 'required|numeric|min:0.01',
            'new_items.*.unit'         => 'required|string',
            'deleted_items'         => 'nullable|array',
            'deleted_items.*'       => 'exists:rfq_items,id',
            'revision_notes'        => 'required|string',
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($request, $rfq) {
            if ($request->has('deleted_items')) {
                $rfq->items()->whereIn('id', $request->deleted_items)->delete();
            }

            if ($request->has('items')) {
                foreach ($request->items as $itemData) {
                    $item = $rfq->items()->find($itemData['id']);
                    if ($item) {
                        $item->update([
                            'product_name' => $itemData['product_name'],
                            'description'  => $itemData['description'],
                            'qty'          => $itemData['qty'],
                            'unit'         => $itemData['unit'],
                        ]);
                    }
                }
            }

            if ($request->has('new_items')) {
                foreach ($request->new_items as $newItem) {
                    $rfq->items()->create([
                        'category'     => $newItem['category'] ?? 'Hardware',
                        'product_name' => $newItem['product_name'],
                        'description'  => $newItem['description'] ?? null,
                        'qty'          => $newItem['qty'],
                        'unit'         => $newItem['unit'] ?? 'Unit',
                        'hpp'          => 0, // Wajib diisi Admin
                        'margin_type'  => 'percentage',
                        'price_after_margin' => 0,
                    ]);
                }
            }

            $rfq->update([
                'status' => Rfq::STATUS_PENDING_ADMIN,
                'revision_notes' => $request->revision_notes,
            ]);

            $admins = \App\Models\User::whereIn('role', ['Admin', 'Admin Purchase', 'Super Admin', 'Leader'])->get();
            foreach ($admins as $admin) {
                \App\Jobs\CreateNotification::dispatch([
                    'user_id' => $admin->id,
                    'title'   => 'Revisi RFQ ' . $rfq->rfq_number,
                    'message' => 'Sales ' . $this->authUser()->name . ' telah merevisi penawaran. Mohon cek ulang HPP.',
                    'type'    => 'warning',
                    'link'    => route('rfq.show', $rfq),
                ]);
            }
        });

        return redirect()->route('rfq.show', $rfq)
            ->with('success', 'Penawaran berhasil direvisi dan dikembalikan ke Admin Purchase.');
    }

    public function history(Request $request)
    {
        abort_if(!$this->authUser()->isSales(), 403, 'Hanya Sales yang dapat mengakses riwayat penjualan.');

        $query = Rfq::with(['customer', 'items'])
            ->where('sales_id', Auth::id())
            ->where('status', Rfq::STATUS_GOAL);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('rfq_number', 'like', '%' . $search . '%')
                  ->orWhereHas('customer', fn($c) => $c->where('company_name', 'like', '%' . $search . '%'));
            });
        }

        $rfqs = $query->orderByDesc('updated_at')->paginate(10)->withQueryString();

        return view('rfqs.history', compact('rfqs'));
    }

    public function verifyPo(Rfq $rfq)
    {
        abort_if(!$this->authUser()->isAdminOrAbove(), 403);
        abort_if($rfq->status !== Rfq::STATUS_PO_PENDING_ADMIN, 403, 'Hanya status PO Received (Pending Admin) yang bisa diverifikasi.');

        if (!$rfq->canTransitionTo(Rfq::STATUS_PO_PENDING_LEADER)) {
            return redirect()->route('rfq.show', $rfq)
                ->with('error', 'PO ini tidak bisa diverifikasi dari status saat ini.');
        }

        $rfq->update(['status' => Rfq::STATUS_PO_PENDING_LEADER]);

        if ($rfq->sales_id) {
            $this->clearDashboardCacheForSales($rfq->sales_id);
        }

        return redirect()->route('rfq.show', $rfq)
            ->with('success', 'PO untuk RFQ ' . $rfq->rfq_number . ' berhasil diverifikasi. Menunggu approval Leader.');
    }

    public function uploadPo(Request $request, Rfq $rfq)
    {
        abort_if(!$this->authUser()->isSales() && !$this->authUser()->isAdminOrAbove(), 403);
        abort_if(!in_array($rfq->status, [Rfq::STATUS_APPROVED, Rfq::STATUS_QUOTATION_CREATED]), 403, 'Upload PO hanya bisa dilakukan saat status Approved atau Quotation Created.');

        $request->validate([
            'po_file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ], [
            'po_file.required' => 'Bukti PO wajib diupload.',
            'po_file.max'      => 'Ukuran file terlalu besar (maksimal 5 MB).',
            'po_file.mimes'    => 'Format file tidak didukung. Gunakan PDF, JPG, JPEG, atau PNG.',
        ]);

        $poPath = $request->file('po_file')->store('purchase_orders', 'public');
        
        $rfq->update([
            'po_file_path' => $poPath,
            'status'       => Rfq::STATUS_PO_PENDING_ADMIN
        ]);

        if ($rfq->sales_id) {
            $this->clearDashboardCacheForSales($rfq->sales_id);
        }

        // Notif ke Admin
        $admins = User::whereIn('role', ['Admin', 'Admin Purchase'])->get();
        foreach ($admins as $admin) {
            \App\Models\Notification::send(
                $admin->id,
                'info',
                'PO Diterima',
                'Sales ' . $this->authUser()->name . ' mengupload PO untuk RFQ ' . $rfq->rfq_number . '. Menunggu verifikasi Anda.',
                route('rfq.show', $rfq)
            );
        }

        return redirect()->route('rfq.show', $rfq)
            ->with('success', 'Bukti PO berhasil diupload. Menunggu verifikasi Admin Purchase.');
    }

    public function approveGoal(Request $request, Rfq $rfq)
    {
        abort_if(!$this->authUser()->isLeader() && !$this->authUser()->isSuperAdmin(), 403);
        abort_if($rfq->status !== Rfq::STATUS_PO_PENDING_LEADER, 403, 'Hanya PO yang sudah diverifikasi Admin (Pending Leader) yang bisa diproses menjadi GOAL.');

        try {
            DB::beginTransaction();

            if (!$rfq->canTransitionTo(Rfq::STATUS_GOAL)) {
            return redirect()->route('rfq.show', $rfq)
                ->with('error', 'RFQ ini tidak bisa diproses menjadi GOAL dari status saat ini.');
        }

        $rfq->update(['status' => Rfq::STATUS_GOAL]);

            $rfq->load('items');
            $grandTotal = $rfq->items->sum(fn($i) => $i->qty * $i->price_after_margin);

            $po = \App\Models\PurchaseOrder::create([
                'po_number'   => \App\Models\PurchaseOrder::generatePoNumber(),
                'rfq_id'      => $rfq->id,
                'customer_id' => $rfq->customer_id,
                'sales_id'    => $rfq->sales_id,
                'po_date'     => now(),
                'status'      => \App\Models\PurchaseOrder::STATUS_APPROVED,
                'file_path'   => $rfq->po_file_path,
                'grand_total' => $grandTotal,
            ]);

            foreach ($rfq->items as $item) {
                $po->items()->create([
                    'item_name'  => $item->product_name,
                    'quantity'   => $item->qty,
                    'unit_price' => $item->price_after_margin,
                    'subtotal'   => $item->qty * $item->price_after_margin,
                ]);
            }

            DB::commit();

            if ($rfq->sales_id) {
                $this->clearDashboardCacheForSales($rfq->sales_id);
            }

        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()->route('quo.index')
                ->with('error', 'Gagal memproses GOAL: ' . $e->getMessage());
        }

        $admins = \App\Models\User::whereIn('role', ['Admin', 'Admin Purchase', 'Super Admin'])->get();
        foreach ($admins as $admin) {
            \App\Models\Notification::send(
                $admin->id,
                'success',
                'PO Baru (Dari GOAL Sales)',
                'Sales ' . $rfq->sales->name . ' telah GOAL untuk penawaran ' . $rfq->rfq_number . '. PO siap diproses.',
                route('po.show', $po)
            );
        }

        if ($rfq->sales_id) {
            $this->clearDashboardCacheForSales($rfq->sales_id);
        }
        return redirect()->route('quo.index')
            ->with('success', 'Yeay! Quotation ' . $rfq->rfq_number . ' resmi menjadi GOAL dan diteruskan ke Admin Purchase.');
    }

    private function authUser(): User
    {
        $user = Auth::user();
        if (!$user) {
            abort(403);
        }
        return $user;
    }
}
