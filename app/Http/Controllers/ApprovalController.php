<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
    public function index()
    {
        // Get all customers waiting for approval using the same business rules as the rest of the workflow.
        $pendingCustomers = Customer::with('sales')
            ->whereIn('status', Customer::pendingApprovalStatuses())
            ->orderBy('created_at', 'desc')
            ->get();

        // Get all POs that need approval (Pending)
        $pendingPOs = PurchaseOrder::with(['customer', 'sales'])
            ->where('status', PurchaseOrder::STATUS_PENDING)
            ->orderBy('created_at', 'desc')
            ->get();

        // Get all POs that need revision (Revisi)
        $revisiPOs = PurchaseOrder::with(['customer', 'sales'])
            ->where('status', PurchaseOrder::STATUS_REVISI)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('approvals.index', compact('pendingCustomers', 'pendingPOs', 'revisiPOs'));
    }
}
