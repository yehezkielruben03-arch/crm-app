<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\Customer;
use App\Models\Notification;
use App\Models\Rfq;
use App\Models\User;
use App\Services\PdfItemParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = PurchaseOrder::with(['customer', 'sales']);

        if (!$user->isAdminOrAbove()) {
            $query->where('sales_id', $user->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('po_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', fn($c) => $c->where('company_name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('po_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('po_date', '<=', $request->date_to);
        }

        $purchaseOrders = $query->orderByDesc('created_at')->paginate(10)->withQueryString();

        return view('purchase-orders.index', compact('purchaseOrders'));
    }

    public function create(Request $request)
    {
        abort_if(!auth()->user()->hasPermission('CRUD'), 403);

        $user = auth()->user();
        $customers = $user->isAdminOrAbove()
            ? Customer::where('status', 'Active')->orderBy('company_name')->get()
            : Customer::where('sales_id', $user->id)->where('status', 'Active')->orderBy('company_name')->get();

        $rfq = null;
        if ($request->has('rfq_id')) {
            $rfq = Rfq::with('items')->findOrFail($request->rfq_id);
            if (!$user->isAdminOrAbove() && $rfq->sales_id !== $user->id) {
                abort(403);
            }
        }

        return view('purchase-orders.create', compact('customers', 'rfq'));
    }

    public function store(Request $request)
    {
        abort_if(!auth()->user()->hasPermission('CRUD'), 403);

        $validated = $request->validate([
            'customer_id'    => 'required|exists:customers,id',
            'po_date'        => 'required|date',
            'due_date'       => 'nullable|date|after_or_equal:po_date',
            'notes'          => 'nullable|string',
            'items'          => 'required|array|min:1',
            'items.*.item_name'  => 'required|string',
            'items.*.quantity'   => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'file'           => 'nullable|file|mimes:pdf|max:5120',
            'rfq_id'         => 'nullable|exists:rfqs,id',
        ]);

        $customer = Customer::findOrFail($validated['customer_id']);
        if (!auth()->user()->isAdminOrAbove()) {
            abort_if($customer->sales_id !== auth()->id(), 403, 'Anda tidak berhak membuat PO untuk Customer ini.');
        }

        $subtotal = 0;
        foreach ($validated['items'] as $item) {
            $subtotal += $item['quantity'] * $item['unit_price'];
        }

        $tax_amount = $subtotal * 0.11;

        try {
            DB::beginTransaction();

            $rfqRef = null;
            if ($validated['rfq_id'] ?? null) {
                $rfq = Rfq::findOrFail($validated['rfq_id']);
                $rfqRef = $rfq->rfq_number;
                $rfq->update(['status' => Rfq::STATUS_GOAL]);
            }

            $poData = [
                'po_number'     => PurchaseOrder::generatePoNumber(),
                'customer_id'   => $validated['customer_id'],
                'sales_id'      => auth()->id(),
                'po_date'       => $validated['po_date'],
                'due_date'      => $validated['due_date'] ?? null,
                'notes'         => $validated['notes'] ?? null,
                'subtotal'      => $subtotal,
                'tax_amount'    => $tax_amount,
                'grand_total'   => $subtotal + $tax_amount,
                'rfq_id'        => $validated['rfq_id'] ?? null,
                'rfq_reference' => $rfqRef,
                'status'        => PurchaseOrder::STATUS_PENDING,
            ];

            if ($request->hasFile('file')) {
                $poData['file_path'] = $request->file('file')->store('po-documents', 'public');
            }

            $po = PurchaseOrder::create($poData);

            foreach ($validated['items'] as $item) {
                $itemSubtotal = $item['quantity'] * $item['unit_price'];
                $po->items()->create([
                    'item_name'  => $item['item_name'],
                    'quantity'   => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal'   => $itemSubtotal,
                ]);
            }

            DB::commit();

            return redirect()->route('po.index')
                ->with('success', "PO {$po->po_number} berhasil dibuat!");
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->route('po.index')
                ->with('error', 'Gagal membuat PO: ' . $e->getMessage());
        }
    }

    public function show(PurchaseOrder $po)
    {
        // Keamanan: Sales hanya bisa lihat PO miliknya
        if (!auth()->user()->isAdminOrAbove() && $po->sales_id !== auth()->id()) {
            abort(403);
        }
        $po->load(['customer', 'sales', 'approver']);
        return view('purchase-orders.show', compact('po'));
    }

    public function edit(PurchaseOrder $po)
    {
        abort_if(!auth()->user()->hasPermission('CRUD'), 403);
        if (!auth()->user()->isAdminOrAbove() && $po->sales_id !== auth()->id()) {
            abort(403);
        }

        $user = auth()->user();
        $customers = $user->isAdminOrAbove()
            ? Customer::where('status', 'Active')->orderBy('company_name')->get()
            : Customer::where('sales_id', $user->id)->where('status', 'Active')->orderBy('company_name')->get();

        $po->load('items');
        return view('purchase-orders.edit', compact('po', 'customers'));
    }

    public function update(Request $request, PurchaseOrder $po)
    {
        // Hanya Admin/Super Admin yang bisa edit PO saat Revisi
        if ($po->isRevisi()) {
            abort_if(!auth()->user()->isAdminOrAbove(), 403, 'Hanya Admin yang dapat mengedit PO dalam status Revisi.');
        } else {
            abort_if(!auth()->user()->hasPermission('CRUD'), 403);
            if (!auth()->user()->isAdminOrAbove() && $po->sales_id !== auth()->id()) {
                abort(403);
            }
        }

        $validated = $request->validate([
            'customer_id'    => 'required|exists:customers,id',
            'po_date'        => 'required|date',
            'due_date'       => 'nullable|date|after_or_equal:po_date',
            'notes'          => 'nullable|string',
            'items'          => 'required|array|min:1',
            'items.*.item_name'  => 'required|string',
            'items.*.quantity'   => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'file'           => 'nullable|file|mimes:pdf|max:5120',
        ]);

        $customer = Customer::findOrFail($validated['customer_id']);
        if (!auth()->user()->isAdminOrAbove()) {
            abort_if($customer->sales_id !== auth()->id(), 403, 'Anda tidak berhak mengubah PO ke Customer ini.');
        }

        $subtotal = 0;
        foreach ($validated['items'] as $item) {
            $subtotal += $item['quantity'] * $item['unit_price'];
        }

        $tax_amount = $subtotal * 0.11;

        $updateData = [
            'customer_id' => $validated['customer_id'],
            'po_date'     => $validated['po_date'],
            'due_date'    => $validated['due_date'] ?? null,
            'notes'       => $validated['notes'] ?? null,
            'subtotal'    => $subtotal,
            'tax_amount'  => $tax_amount,
            'grand_total' => $subtotal + $tax_amount,
        ];

        try {
            DB::beginTransaction();

            // Jika Admin selesai merevisi, kembalikan ke Pending dan kirim notifikasi ke Sales
            if ($po->isRevisi()) {
                $updateData['status'] = PurchaseOrder::STATUS_PENDING;
                $updateData['change_request_reason'] = null;

                Notification::send(
                    userId:  $po->sales_id,
                    type:    'po_revised',
                    title:   'PO Telah Direvisi',
                    message: "PO {$po->po_number} telah selesai direvisi oleh " . auth()->user()->name . " dan kembali ke status Pending.",
                    link:    route('po.show', $po)
                );
            }

            if ($request->hasFile('file')) {
                if ($po->file_path) {
                    \Storage::disk('public')->delete($po->file_path);
                }
                $updateData['file_path'] = $request->file('file')->store('po-documents', 'public');
            }

            $po->update($updateData);

            // Recreate items
            $po->items()->delete();
            foreach ($validated['items'] as $item) {
                $itemSubtotal = $item['quantity'] * $item['unit_price'];
                $po->items()->create([
                    'item_name'  => $item['item_name'],
                    'quantity'   => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal'   => $itemSubtotal,
                ]);
            }

            DB::commit();

            return redirect()->route('po.index')
                ->with('success', "PO {$po->po_number} berhasil diupdate!");
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->route('po.index')
                ->with('error', 'Gagal memperbarui PO: ' . $e->getMessage());
        }
    }

    public function destroy(PurchaseOrder $po)
    {
        abort(403, 'PO tidak dapat dihapus. Gunakan fitur Request Perubahan jika ada perubahan yang diperlukan.');
    }

    public function parsePdf(Request $request)
    {
        abort_if(!auth()->user()->hasPermission('CRUD'), 403);

        $request->validate([
            'file' => 'required|file|mimes:pdf|max:5120',
        ]);

        try {
            $rawItems = (new PdfItemParser())->parse($request->file('file')->getPathname());

            $items = array_map(fn($item) => [
                'item_name'  => $item['product_name'],
                'quantity'   => $item['qty'],
                'unit'       => $item['unit'] ?? 'pcs',
                'unit_price' => $item['unit_price'] ?? 0,
            ], $rawItems);

            return response()->json([
                'success' => true,
                'items'   => $items,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses PDF: ' . $e->getMessage(),
            ], 422);
        }
    }

    public function downloadPdf(PurchaseOrder $po)
    {
        abort_if(!auth()->user()->hasPermission('CRUD'), 403);

        if (!$po->file_path) {
            abort(404, 'File tidak ditemukan.');
        }

        return \Storage::disk('public')->download($po->file_path, $po->po_number . '.pdf');
    }

    public function requestChange(Request $request, PurchaseOrder $po)
    {
        // Hanya Sales, hanya untuk PO miliknya, hanya jika status Pending
        abort_if(!auth()->user()->isSales(), 403);
        abort_if($po->sales_id !== auth()->id(), 403);
        abort_if(!$po->isPending(), 422, 'Hanya PO dengan status Pending yang dapat diajukan perubahan.');

        $request->validate([
            'change_request_reason' => 'required|string|min:10',
        ]);

        $po->update([
            'status'               => PurchaseOrder::STATUS_REVISI,
            'change_request_reason' => $request->change_request_reason,
        ]);

        // Notifikasi semua Admin
        $admins = User::whereIn('role', ['Admin', 'Admin Purchase', 'Super Admin'])->get();
        foreach ($admins as $admin) {
            Notification::send(
                userId:  $admin->id,
                type:    'po_change_request',
                title:   'Permintaan Perubahan PO 🔄',
                message: "Sales " . auth()->user()->name . " meminta perubahan untuk PO {$po->po_number} ({$po->customer->company_name}). Alasan: {$request->change_request_reason}",
                link:    route('po.edit', $po)
            );
        }

        return redirect()->route('po.show', $po)
            ->with('success', "Permintaan perubahan untuk PO {$po->po_number} telah dikirim ke Admin.");
    }

    public function approve(PurchaseOrder $po)
    {
        abort_if(!auth()->user()->hasPermission('Approval'), 403);
        abort_if(!$po->isPending(), 422, 'PO ini tidak dalam status Pending.');

        $po->update([
            'status'      => PurchaseOrder::STATUS_GOAL,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        Notification::send(
            userId:  $po->sales_id,
            type:    'po_approved',
            title:   'PO GOAL ✅',
            message: "PO {$po->po_number} untuk {$po->customer->company_name} telah Goal oleh " . auth()->user()->name . ".",
            link:    route('po.show', $po)
        );

        return redirect()->route('po.index')
            ->with('success', "PO {$po->po_number} berhasil set menjadi Goal!");
    }

    public function reject(Request $request, PurchaseOrder $po)
    {
        abort_if(!auth()->user()->hasPermission('Approval'), 403);
        abort_if(!$po->isPending(), 422, 'PO ini tidak dalam status Pending.');

        $request->validate([
            'rejection_reason' => 'required|string|min:10',
        ]);

        $po->update([
            'status'           => PurchaseOrder::STATUS_TIDAK_GOAL,
            'rejection_reason' => $request->rejection_reason,
            'approved_by'      => auth()->id(),
            'approved_at'      => now(),
        ]);

        Notification::send(
            userId:  $po->sales_id,
            type:    'po_rejected',
            title:   'PO TIDAK GOAL ❌',
            message: "PO {$po->po_number} dinyatakan Tidak Goal oleh " . auth()->user()->name . ". Alasan: {$request->rejection_reason}",
            link:    route('po.show', $po)
        );

        return redirect()->route('po.index')
            ->with('success', "PO {$po->po_number} diset menjadi Tidak Goal.");
    }
}
