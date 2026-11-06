<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Rfq;
use App\Models\RfqItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RfqPaymentTermDpTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private Customer $customer;
    private Rfq $rfq;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'role' => 'Super Admin',
            'status' => 'Active',
        ]);

        $this->customer = Customer::create([
            'company_name' => 'PT Testing Sinergi',
            'customer_code' => 'TEST-001',
            'status' => 'Active',
            'created_by' => $this->adminUser->id,
        ]);

        $this->rfq = Rfq::create([
            'rfq_number' => 'RFQ-DP-2026-001',
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->company_name,
            'customer_code' => $this->customer->customer_code,
            'sales_id' => $this->adminUser->id,
            'sales_name' => $this->adminUser->name,
            'rfq_date' => now()->toDateString(),
            'status' => Rfq::STATUS_PENDING_ADMIN,
            'payment_term_type' => 'tempo',
            'payment_term_days' => 14,
            'payment_term_dp_percent' => null,
        ]);

        RfqItem::create([
            'rfq_id' => $this->rfq->id,
            'product_name' => 'Industrial Fan Unit',
            'qty' => 2,
            'unit' => 'Unit',
            'hpp' => 1000000,
            'margin_type' => 'percentage',
            'margin_value' => 20,
            'selling_price' => 1200000,
            'final_price' => 1200000,
            'total_price' => 2400000,
        ]);
    }

    public function test_rfq_model_formatted_text_with_dp_and_without_dp(): void
    {
        // 1. With DP 50% and 14 days
        $this->rfq->payment_term_type = 'tempo';
        $this->rfq->payment_term_days = 14;
        $this->rfq->payment_term_dp_percent = 50;
        $this->assertTrue($this->rfq->hasPaymentTermDp());
        $this->assertSame(50, $this->rfq->getPaymentTermDpPercent());
        $this->assertSame(
            'Sistem pembayaran DP 50% sisanya 14 Hari setelah barang diterima',
            $this->rfq->getPaymentTermFormattedText()
        );
        $this->assertStringContainsString('50％', $this->rfq->getPaymentTermFormattedJapaneseText());
        $this->assertStringContainsString('14日以内', $this->rfq->getPaymentTermFormattedJapaneseText());

        // 2. With DP 50% and 30 days (Leader Rian request)
        $this->rfq->payment_term_days = 30;
        $this->assertSame(
            'Sistem pembayaran DP 50% sisanya 30 Hari setelah barang diterima',
            $this->rfq->getPaymentTermFormattedText()
        );
        $this->assertStringContainsString('30日以内', $this->rfq->getPaymentTermFormattedJapaneseText());

        // 3. Without DP (DP null or 0) -> Standard existing template
        $this->rfq->payment_term_dp_percent = null;
        $this->assertFalse($this->rfq->hasPaymentTermDp());
        $this->assertSame(
            'Sistem pembayaran 30 hari setelah invoice diterima',
            $this->rfq->getPaymentTermFormattedText()
        );

        $this->rfq->payment_term_days = 14;
        $this->assertSame(
            'Sistem pembayaran 14 hari setelah invoice diterima',
            $this->rfq->getPaymentTermFormattedText()
        );

        // 4. CBD remains Cash Before Delivery
        $this->rfq->payment_term_type = 'cbd_b2b';
        $this->assertSame(
            'Sistem pembayaran Cash Before Delivery',
            $this->rfq->getPaymentTermFormattedText()
        );
    }

    public function test_pdf_and_preview_views_render_dp_terms_accurately(): void
    {
        $this->rfq->update([
            'status' => Rfq::STATUS_APPROVED,
            'payment_term_type' => 'tempo',
            'payment_term_days' => 14,
            'payment_term_dp_percent' => 50,
        ]);

        $this->actingAs($this->adminUser);

        // Preview quotation HTML
        $previewResp = $this->get(route('rfq.preview_quotation', $this->rfq));
        $previewResp->assertOk();
        $previewResp->assertSee('Sistem pembayaran DP 50% sisanya 14 Hari setelah barang diterima');

        // Without DP in preview
        $this->rfq->update(['payment_term_dp_percent' => null]);
        $previewResp2 = $this->get(route('rfq.preview_quotation', $this->rfq));
        $previewResp2->assertOk();
        $previewResp2->assertSee('Sistem pembayaran 14 hari setelah invoice diterima');

        // Interactive modal POST preview from calculation form
        $item = $this->rfq->items->first();
        $postPreviewResp = $this->post(route('rfq.preview_draft', $this->rfq), [
            'payment_term_type' => 'tempo',
            'payment_term_days' => 21,
            'payment_term_dp_percent' => 40,
            'tax_type' => 'exclude',
            'items' => [
                $item->id => [
                    'product_name' => $item->product_name,
                    'qty' => $item->qty,
                    'unit' => $item->unit,
                    'hpp' => '1.000.000',
                    'margin_type' => 'percentage',
                    'margin_value' => '20',
                    'final_price' => '1.200.000',
                    'total_price' => '2.400.000',
                ]
            ]
        ]);
        $postPreviewResp->assertOk();
        $postPreviewResp->assertSee('Sistem pembayaran DP 40% sisanya 21 Hari setelah barang diterima');

        // Test actual PDF Download Generation
        $pdfResp = $this->get(route('rfq.download_quotation', $this->rfq));
        $pdfResp->assertOk();
        $this->assertEquals('application/pdf', $pdfResp->headers->get('content-type'));
    }

    public function test_submit_price_controller_saves_dp_percentage(): void
    {
        $this->actingAs($this->adminUser);

        $item = $this->rfq->items->first();

        // Submit with DP 50% and 30 days tempo
        $postData = [
            'payment_term_type' => 'tempo',
            'payment_term_days' => 30,
            'payment_term_dp_percent' => 50,
            'tax_type' => 'exclude',
            'items' => [
                $item->id => [
                    'product_name' => $item->product_name,
                    'qty' => $item->qty,
                    'unit' => $item->unit,
                    'hpp' => '1.000.000',
                    'margin_type' => 'percentage',
                    'margin_value' => '20',
                    'final_price' => '1.200.000',
                    'total_price' => '2.400.000',
                ]
            ]
        ];

        $resp = $this->post(route('rfq.submit_price', $this->rfq), $postData);
        $resp->assertRedirect();

        $this->rfq->refresh();
        $this->assertSame('tempo', $this->rfq->payment_term_type);
        $this->assertSame(30, $this->rfq->payment_term_days);
        $this->assertSame(50, $this->rfq->payment_term_dp_percent);
        $this->assertSame(
            'Sistem pembayaran DP 50% sisanya 30 Hari setelah barang diterima',
            $this->rfq->getPaymentTermFormattedText()
        );

        // Submit price clearing DP
        $postDataNoDp = $postData;
        $postDataNoDp['payment_term_dp_percent'] = '';
        $postDataNoDp['payment_term_days'] = 14;

        $resp2 = $this->post(route('rfq.submit_price', $this->rfq), $postDataNoDp);
        $resp2->assertRedirect();

        $this->rfq->refresh();
        $this->assertNull($this->rfq->payment_term_dp_percent);
        $this->assertSame(14, $this->rfq->payment_term_days);
        $this->assertSame(
            'Sistem pembayaran 14 hari setelah invoice diterima',
            $this->rfq->getPaymentTermFormattedText()
        );
    }
}
