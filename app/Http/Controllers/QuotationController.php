<?php

namespace App\Http\Controllers;

use App\Models\Quotation;
use App\Models\Customer;
use App\Models\User;
use App\Models\Rfq;
use App\Models\Notification;
use App\Services\PdfItemParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class QuotationController extends Controller
{
    public function index(Request $request)
    {
        $user = $this->authUser();

        $query = Quotation::with(['customer', 'sales', 'rfq']);

        if (!$user->isAdminOrAbove()) {
            $query->where('sales_id', $user->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('quo_number', 'like', '%' . $search . '%')
                  ->orWhereHas('customer', fn($c) => $c->where('company_name', 'like', '%' . $search . '%'));
            });
        }

        $quotations = $query->orderByDesc('created_at')->paginate(10)->withQueryString();

        return view('quotations.index', compact('quotations'));
    }

    public function create(Request $request)
    {
        abort_if(!$this->authUser()->isAdminOrAbove(), 403, 'Hanya Admin yang bisa membuat Quotation.');

        $customers = Customer::where('status', 'Active')->orderBy('company_name')->get();
        $salesList = User::whereIn('role', ['Sales', 'Sales Marketing'])->where('status', 'Active')->orderBy('name')->get();

        $rfq = null;
        if ($request->has('rfq_id')) {
            $rfq = Rfq::with('items')->findOrFail($request->rfq_id);
        }

        return view('quotations.create', compact('customers', 'salesList', 'rfq'));
    }

    public function store(Request $request)
    {
        abort_if(!$this->authUser()->isAdminOrAbove(), 403);

        $validated = $request->validate([
            'customer_id'              => 'required|exists:customers,id',
            'sales_id'                 => 'required|exists:users,id',
            'rfq_id'                   => 'nullable|exists:rfqs,id',
            'notes'                    => 'nullable|string',
            'file'                     => 'nullable|file|mimes:pdf|max:5120',
            'items'                    => 'required|array|min:1',
            'items.*.product_name'     => 'required|string',
            'items.*.description'      => 'nullable|string',
            'items.*.qty'              => 'required|numeric|min:0.01',
            'items.*.unit'             => 'nullable|string|max:30',
            'items.*.unit_price'       => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $quo = Quotation::create([
                'quo_number'      => Quotation::generateQuoNumber(),
                'revision_number' => 0,
                'rfq_id'          => $validated['rfq_id'] ?? null,
                'customer_id'     => $validated['customer_id'],
                'sales_id'        => $validated['sales_id'],
                'created_by'      => Auth::id(),
                'status'          => Quotation::STATUS_DRAFT,
                'valid_until'     => now()->addDays(7)->format('Y-m-d'),
                'notes'           => $validated['notes'] ?? null,
            ]);

            if ($request->hasFile('file')) {
                $path = $request->file('file')->store('quotations', 'public');
                $quo->update(['file_path' => $path]);
            }

            foreach ($validated['items'] as $item) {
                $itemTotal = $item['unit_price'] * $item['qty'];
                $quo->items()->create([
                    'product_name' => $item['product_name'],
                    'description'  => $item['description'] ?? null,
                    'qty'          => $item['qty'],
                    'unit'         => $item['unit'] ?? null,
                    'unit_price'   => $item['unit_price'],
                    'total_price'  => $itemTotal,
                ]);
            }

            DB::commit();

            $quo->load('customer');

            Notification::send(
                userId: $quo->sales_id,
                type:   'quo_created',
                title:  'Quotation Baru',
                message: 'Quotation ' . $quo->quo_number . ' untuk ' . $quo->customer->company_name . ' telah dibuat oleh ' . $this->authUser()->name . '.',
                link:   route('quo.show', $quo)
            );

            return redirect()->route('quo.show', $quo)
                ->with('success', 'Quotation ' . $quo->quo_number . ' berhasil dibuat!');
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->route('quo.index')
                ->with('error', 'Gagal membuat Quotation: ' . $e->getMessage());
        }
    }

    public function show(Quotation $quotation)
    {
        $quo = $quotation->load(['customer', 'sales', 'items']);

        if (!$this->authUser()->isAdminOrAbove() && $quo->sales_id !== Auth::id()) {
            abort(403);
        }

        return view('quotations.show', compact('quo'));
    }

    public function edit(Quotation $quotation)
    {
        abort_if(!$this->authUser()->isAdminOrAbove(), 403);
        abort_if(!$quotation->isDraft(), 403, 'Quotation hanya bisa diedit saat status Draft.');

        $customers = Customer::where('status', 'Active')->orderBy('company_name')->get();
        $salesList = User::whereIn('role', ['Sales', 'Sales Marketing'])->where('status', 'Active')->orderBy('name')->get();

        $quotation->load('items');
        return view('quotations.edit', compact('quotation', 'customers', 'salesList'));
    }

    public function update(Request $request, Quotation $quotation)
    {
        abort_if(!$this->authUser()->isAdminOrAbove(), 403);
        abort_if(!$quotation->isDraft(), 403, 'Quotation hanya bisa diedit saat status Draft.');

        $validated = $request->validate([
            'customer_id'              => 'required|exists:customers,id',
            'sales_id'                 => 'required|exists:users,id',
            'notes'                    => 'nullable|string',
            'file'                     => 'nullable|file|mimes:pdf|max:5120',
            'items'                    => 'required|array|min:1',
            'items.*.product_name'     => 'required|string',
            'items.*.description'      => 'nullable|string',
            'items.*.qty'              => 'required|numeric|min:0.01',
            'items.*.unit'             => 'nullable|string|max:30',
            'items.*.unit_price'       => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $updateData = [
                'customer_id' => $validated['customer_id'],
                'sales_id'    => $validated['sales_id'],
                'notes'       => $validated['notes'] ?? null,
            ];

            if ($request->hasFile('file')) {
                if ($quotation->file_path) {
                    Storage::disk('public')->delete($quotation->file_path);
                }
                $updateData['file_path'] = $request->file('file')->store('quotations', 'public');
            }

            $quotation->update($updateData);

            $quotation->items()->delete();
            foreach ($validated['items'] as $item) {
                $itemTotal = $item['unit_price'] * $item['qty'];
                $quotation->items()->create([
                    'product_name' => $item['product_name'],
                    'description'  => $item['description'] ?? null,
                    'qty'          => $item['qty'],
                    'unit'         => $item['unit'] ?? null,
                    'unit_price'   => $item['unit_price'],
                    'total_price'  => $itemTotal,
                ]);
            }

            DB::commit();

            return redirect()->route('quo.show', $quotation)
                ->with('success', 'Quotation ' . $quotation->quo_number . ' berhasil diupdate!');
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->route('quo.show', $quotation)
                ->with('error', 'Gagal memperbarui Quotation: ' . $e->getMessage());
        }
    }

    public function destroy(Quotation $quotation)
    {
        abort_if(!$this->authUser()->isAdminOrAbove(), 403);
        abort_if(!$quotation->isDraft(), 403, 'Quotation hanya bisa dihapus saat status Draft.');

        $quoNumber = $quotation->quo_number;
        $quotation->delete();

        return redirect()->route('quo.index')
            ->with('success', 'Quotation ' . $quoNumber . ' berhasil diarsipkan.');
    }

    public function send(Quotation $quotation)
    {
        abort_if(!$this->authUser()->isAdminOrAbove(), 403);
        abort_if(!$quotation->isDraft(), 422, 'Hanya Quotation dengan status Draft yang bisa dikirim.');

        $quotation->load('customer');

        if (!$quotation->canTransitionTo(Quotation::STATUS_SENT)) {
            return redirect()->route('quo.show', $quotation)
                ->with('error', 'Quotation tidak bisa dikirim dari status saat ini.');
        }

        $quotation->update(['status' => Quotation::STATUS_SENT]);

        Notification::send(
            userId: $quotation->sales_id,
            type:   'quo_sent',
            title:  'Quotation Baru',
            message: 'Quotation ' . $quotation->quo_number . ' untuk ' . $quotation->customer->company_name . ' telah dikirim oleh ' . $this->authUser()->name . '.',
            link:   route('quo.show', $quotation)
        );

        return redirect()->route('quo.show', $quotation)
            ->with('success', 'Quotation ' . $quotation->quo_number . ' berhasil dikirim ke ' . $quotation->sales->name . '.');
    }

    public function approve(Quotation $quotation)
    {
        abort_if(!$this->authUser()->isLeader() && !$this->authUser()->isSuperAdmin(), 403);

        if (!$quotation->canTransitionTo(Quotation::STATUS_APPROVED)) {
            return redirect()->route('quo.show', $quotation)
                ->with('error', 'Quotation hanya bisa disetujui setelah status Sent.');
        }

        $quotation->update(['status' => Quotation::STATUS_APPROVED]);

        Notification::send(
            userId: $quotation->sales_id,
            type:   'success',
            title:  'Quotation Disetujui Leader',
            message: 'Quotation ' . $quotation->quo_number . ' telah disetujui oleh ' . $this->authUser()->name . '. Sales bisa melanjutkan ke GOAL.',
            link:   route('quo.show', $quotation)
        );

        return redirect()->route('quo.show', $quotation)
            ->with('success', 'Quotation ' . $quotation->quo_number . ' berhasil disetujui.');
    }

    public function revise(Request $request, Quotation $quotation)
    {
        abort_if(!$this->authUser()->isAdminOrAbove(), 403);
        abort_if(!$quotation->isSent(), 422, 'Hanya Quotation dengan status Sent yang bisa direvisi.');

        $quotation->load('items');

        $newRevision = Quotation::create([
            'quo_number'      => $quotation->quo_number,
            'revision_number' => $quotation->revision_number + 1,
            'rfq_id'          => $quotation->rfq_id,
            'customer_id'     => $quotation->customer_id,
            'sales_id'        => $quotation->sales_id,
            'created_by'      => Auth::id(),
            'status'          => Quotation::STATUS_DRAFT,
            'notes'           => $request->notes ?? $quotation->notes,
        ]);

        foreach ($quotation->items as $item) {
            $newRevision->items()->create([
                'product_name' => $item->product_name,
                'description'  => $item->description,
                'qty'          => $item->qty,
                'unit'         => $item->unit,
                'unit_price'   => $item->unit_price,
                'total_price'  => $item->total_price,
            ]);
        }

        Notification::send(
            userId: $quotation->sales_id,
            type:   'quo_revised',
            title:  'Quotation Direvisi',
            message: 'Revisi #' . $newRevision->revision_number . ' untuk ' . $quotation->quo_number . ' telah dibuat oleh ' . $this->authUser()->name . '.',
            link:   route('quo.show', $newRevision)
        );

        return redirect()->route('quo.edit', $newRevision)
            ->with('success', 'Revisi #' . $newRevision->revision_number . ' untuk ' . $quotation->quo_number . ' berhasil dibuat. Silakan edit item sesuai perubahan.');
    }

    /**
     * @deprecated v2.0 — Logika ini sudah dipindahkan ke RfqController::approveGoal().
     * Method ini masih dipertahankan sementara untuk backward-compatibility
     * dengan Quotation lama yang dibuat sebelum flow RFQ diperbarui.
     * Jangan gunakan untuk fitur baru. Hapus setelah semua data lama berhasil migrasi.
     *
     * @see RfqController::approveGoal()
     */
    public function processGoal(Request $request, Quotation $quotation)
    {
        abort_if(!$this->authUser()->isSales(), 403);
        abort_if(!$quotation->isSent(), 422, 'Hanya Quotation yang sudah Sent yang bisa diproses GOAL.');

        $validated = $request->validate([
            'po_file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'items'   => 'required|array',
            'items.*.qty' => 'required|numeric|min:0',
        ]);

        $rfq = $quotation->rfq;
        if (!$rfq) {
            return back()->with('error', 'Quotation ini tidak memiliki referensi RFQ.');
        }

        $poPath = $request->file('po_file')->store('purchase_orders', 'public');
        $rfq->po_file_path = $poPath;
        $rfq->status = Rfq::STATUS_PO_PENDING_ADMIN;
        $rfq->save();

        foreach ($validated['items'] as $itemId => $data) {
            $item = $quotation->items()->find($itemId);
            if ($item) {
                $item->qty = $data['qty'];
                $item->total_price = $item->unit_price * $data['qty'];
                $item->save();
            }
        }

        // Kirim notifikasi ke semua Admin Purchase
        $admins = User::whereIn('role', ['Admin', 'Admin Purchase'])->get();
        foreach ($admins as $admin) {
            Notification::send(
                userId: $admin->id,
                type:   'po_received',
                title:  'PO Diterima',
                message: 'Sales ' . $this->authUser()->name . ' telah mengupload PO untuk RFQ ' . $rfq->rfq_number . '. Menunggu verifikasi Anda.',
                link:   route('rfq.show', $rfq)
            );
        }

        return redirect()->route('quo.show', $quotation)
            ->with('success', 'Berhasil memproses GOAL dan mengirim Bukti PO ke Admin.');
    }

    public function downloadPdf(Quotation $quotation)
    {
        if (!$this->authUser()->isAdminOrAbove() && $quotation->sales_id !== Auth::id()) {
            abort(403);
        }
        abort_if(!$quotation->file_path, 404, 'File PDF tidak ditemukan.');

        $path = Storage::disk('public')->path($quotation->file_path);

        return response()->download(
            $path,
            'Quotation_' . $quotation->quo_number . '_Rev' . $quotation->revision_number . '.pdf'
        );
    }

    public function parsePdf(Request $request)
    {
        abort_if(!$this->authUser()->isAdminOrAbove(), 403);
        $request->validate([
            'file' => 'required|file|mimes:pdf|max:5120',
        ]);
        $path = $request->file('file')->store('temp_parsing', 'public');
        $fullPath = storage_path('app/public/' . $path);
        try {
            $items = (new PdfItemParser())->parse($fullPath);
            Storage::disk('public')->delete($path);
        } catch (\Exception $e) {
            Storage::disk('public')->delete($path);
            return response()->json(['error' => 'Gagal membaca PDF: ' . $e->getMessage()], 422);
        }
        return response()->json(['items' => $items]);
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
