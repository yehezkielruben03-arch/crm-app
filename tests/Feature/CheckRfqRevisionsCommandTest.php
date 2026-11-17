<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\PurchaseOrder;
use App\Models\Rfq;
use App\Models\RfqItem;
use App\Models\RfqPriceHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckRfqRevisionsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_check_rfq_revisions_command_runs_cleanly(): void
    {
        $sales = User::factory()->create([
            'name'   => 'Yudi Siswanto',
            'role'   => 'Sales',
            'status' => 'Active',
        ]);

        $customer = Customer::create([
            'company_name' => 'PT Wooin Electronics Indonesia',
            'sales_id'     => $sales->id,
            'status'       => 'Active',
        ]);

        $rfq = Rfq::create([
            'rfq_number'    => '261028-0020',
            'customer_id'   => $customer->id,
            'customer_name' => $customer->company_name,
            'sales_id'      => $sales->id,
            'sales_name'    => $sales->name,
            'rfq_date'      => '2026-10-28',
            'status'        => Rfq::STATUS_GOAL,
        ]);

        RfqItem::create([
            'rfq_id'             => $rfq->id,
            'product_name'       => 'Electronic Part A',
            'qty'                => 10,
            'unit'               => 'Pcs',
            'hpp'                => 1800000,
            'margin'             => 15,
            'price_after_margin' => 2070000,
            'validity_days'      => 3,
        ]);

        RfqPriceHistory::create([
            'rfq_id'       => $rfq->id,
            'version'      => 1,
            'history_data' => [
                [
                    'product_name'       => 'Electronic Part A',
                    'qty'                => 10,
                    'price_after_margin' => 2070000,
                ],
            ],
            'created_by'   => $sales->id,
            'approved_at'  => now(),
        ]);

        $po = PurchaseOrder::create([
            'po_number'   => 'PO-2026-0020',
            'customer_id' => $customer->id,
            'sales_id'    => $sales->id,
            'rfq_id'      => $rfq->id,
            'po_date'     => '2026-11-13',
            'status'      => PurchaseOrder::STATUS_GOAL,
            'subtotal'    => 20700000,
            'tax_amount'  => 2277000,
            'grand_total' => 22977000,
            'approved_at' => now(),
        ]);

        $this->artisan('crm:check-rfq-revision', [
            '--month' => 11,
            '--year'  => 2026,
            '--rfq'   => '261028-0020',
        ])
            ->expectsOutputToContain('261028-0020')
            ->assertExitCode(0);

        // Test mode audit seluruh RFQ di bulan berjalan
        $this->artisan('crm:check-rfq-revision', [
            '--month' => 11,
            '--year'  => 2026,
        ])
            ->expectsOutputToContain('PEDIA CRM - AUDIT FORENSIK UPDATE & REVISI SETIAP RFQ')
            ->assertExitCode(0);
    }
}
