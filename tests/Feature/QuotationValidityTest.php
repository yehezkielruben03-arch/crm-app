<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerContact;
use App\Models\Rfq;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class QuotationValidityTest extends TestCase
{
    use RefreshDatabase;

    private User $sales;
    private User $admin;
    private Customer $customer;
    private CustomerContact $contact;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::create([
            'name'     => 'Admin User',
            'username' => 'admin_user',
            'email'    => 'admin@crm.com',
            'password' => Hash::make('password123'),
            'role'     => 'Super Admin',
            'status'   => 'Active',
            'phone'    => '081234567890',
        ]);

        $this->sales = User::create([
            'name'     => 'Ade Zulvida',
            'username' => 'sales_ade',
            'email'    => 'ade@pedia-technology.co.id',
            'password' => Hash::make('password123'),
            'role'     => 'Sales',
            'status'   => 'Active',
            'phone'    => '081234567899',
        ]);

        $this->customer = Customer::create([
            'customer_type' => 'PT',
            'company_name'  => 'Yoshino Indonesia',
            'company_code'  => 'PTI202610050000001',
            'address'       => 'Kawasan GIIC Blok CF No.01 Deltamas',
            'status'        => Customer::STATUS_ACTIVE,
            'sales_id'      => $this->sales->id,
            'created_by'    => $this->admin->id,
        ]);

        $this->contact = CustomerContact::create([
            'customer_id' => $this->customer->id,
            'name'        => 'Bu Kristin',
            'position'    => 'Purchasing Manager',
            'phone'       => '081299887766',
            'is_primary'  => true,
        ]);
    }

    public function test_validity_date_skips_weekends_and_adds_three_business_days(): void
    {
        // Senin 5 Okt 2026 -> Kamis 8 Okt 2026
        $rfqMon = new Rfq(['rfq_date' => '2026-10-05']);
        $this->assertEquals('08 Oct 2026', $rfqMon->formatted_valid_until);

        // Selasa 6 Okt 2026 -> Jumat 9 Okt 2026
        $rfqTue = new Rfq(['rfq_date' => '2026-10-06']);
        $this->assertEquals('09 Oct 2026', $rfqTue->formatted_valid_until);

        // Rabu 7 Okt 2026 -> Senin 12 Okt 2026 (Sabtu & Minggu dilewati)
        $rfqWed = new Rfq(['rfq_date' => '2026-10-07']);
        $this->assertEquals('12 Oct 2026', $rfqWed->formatted_valid_until);

        // Kamis 8 Okt 2026 -> Selasa 13 Okt 2026 (Sabtu & Minggu dilewati)
        $rfqThu = new Rfq(['rfq_date' => '2026-10-08']);
        $this->assertEquals('13 Oct 2026', $rfqThu->formatted_valid_until);

        // Jumat 9 Okt 2026 -> Rabu 14 Okt 2026 (Sabtu & Minggu dilewati)
        $rfqFri = new Rfq(['rfq_date' => '2026-10-09']);
        $this->assertEquals('14 Oct 2026', $rfqFri->formatted_valid_until);

        // Sabtu 10 Okt 2026 -> Rabu 14 Okt 2026
        $rfqSat = new Rfq(['rfq_date' => '2026-10-10']);
        $this->assertEquals('14 Oct 2026', $rfqSat->formatted_valid_until);

        // Minggu 11 Okt 2026 -> Rabu 14 Okt 2026
        $rfqSun = new Rfq(['rfq_date' => '2026-10-11']);
        $this->assertEquals('14 Oct 2026', $rfqSun->formatted_valid_until);
    }

    public function test_quotation_preview_and_pdf_render_three_business_day_validity(): void
    {
        // Dibuat Jumat 2 Okt 2026 -> +3 hari kerja adalah Rabu 7 Okt 2026
        $rfq = Rfq::create([
            'rfq_number'          => '261002-0001',
            'customer_id'         => $this->customer->id,
            'customer_name'       => $this->customer->company_name,
            'customer_contact_id' => $this->contact->id,
            'sales_id'            => $this->sales->id,
            'sales_name'          => $this->sales->name,
            'status'              => Rfq::STATUS_APPROVED,
            'type'                => 'Non Projek',
            'rfq_date'            => '2026-10-02',
        ]);

        $rfq->items()->create([
            'product_name'       => 'Item Uji',
            'qty'                => 1,
            'unit'               => 'Unit',
            'hpp'                => 100000,
            'margin'             => 20,
            'price_after_margin' => 120000,
        ]);

        $this->assertEquals('07 Oct 2026', $rfq->formatted_valid_until);

        // Uji Web Preview
        $response = $this->actingAs($this->sales)->get(route('rfq.preview_quotation', $rfq));
        $response->assertStatus(200);
        $response->assertSee('Berlaku s/d tgl :');
        $response->assertSee('07 Oct 2026');

        // Uji Download PDF
        $pdfResponse = $this->actingAs($this->sales)->get(route('rfq.download_quotation', $rfq));
        $pdfResponse->assertStatus(200);
        $pdfResponse->assertHeader('content-type', 'application/pdf');
    }
}
