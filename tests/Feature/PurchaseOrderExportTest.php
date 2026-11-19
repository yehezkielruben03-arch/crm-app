<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\PurchaseOrder;
use App\Models\Rfq;
use App\Models\RfqItem;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

class PurchaseOrderExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function loadSpreadsheetFromResponse($response): Spreadsheet
    {
        $response->assertOk();
        $file = $response->baseResponse->getFile();
        $this->assertFileExists($file->getPathname());

        return IOFactory::load($file->getPathname());
    }

    public function test_all_four_roles_can_export_monthly_po_excel(): void
    {
        $roles = ['Sales', 'Admin', 'Leader', 'Super Admin'];

        foreach ($roles as $role) {
            $user = User::factory()->create([
                'name'   => "User {$role}",
                'role'   => $role,
                'status' => 'Active',
            ]);

            $response = $this->actingAs($user)->get(route('po.export', [
                'month' => 11,
                'year'  => 2026,
            ]));

            $response->assertOk();
            $this->assertStringContainsString(
                '.xlsx',
                (string) $response->headers->get('content-disposition')
            );
        }

        $unauthorized = User::factory()->create([
            'name'   => 'No Access User',
            'role'   => 'Viewer',
            'status' => 'Active',
        ]);

        $this->actingAs($unauthorized)
            ->get(route('po.export', ['month' => 11, 'year' => 2026]))
            ->assertForbidden();
    }

    public function test_sales_export_has_four_columns_joins_multi_items_protects_bundling_and_blocks_idor(): void
    {
        $salesAde = User::factory()->create(['name' => 'Ade Zulvida', 'role' => 'Sales', 'status' => 'Active']);
        $salesYudi = User::factory()->create(['name' => 'Yudi Siswanto', 'role' => 'Sales', 'status' => 'Active']);

        $custChuhatsu = Customer::create(['company_name' => 'PT Chuhatsu Indonesia', 'sales_id' => $salesAde->id, 'status' => 'Active']);
        $custPenjalindo = Customer::create(['company_name' => 'PT Penjalindo Nusantara', 'sales_id' => $salesAde->id, 'status' => 'Active']);
        $custWooin = Customer::create(['company_name' => 'PT Wooin Electronics', 'sales_id' => $salesYudi->id, 'status' => 'Active']);

        // 1. PO Multi-Item (Exclude PPN = 58.300.000, Grand Total +PPN = 64.713.000)
        $poMulti = PurchaseOrder::create([
            'po_number'   => 'PO-2026-0019',
            'customer_id' => $custChuhatsu->id,
            'sales_id'    => $salesAde->id,
            'po_date'     => '2026-11-10',
            'status'      => PurchaseOrder::STATUS_GOAL,
            'subtotal'    => 58300000,
            'tax_amount'  => 6413000,
            'grand_total' => 64713000,
            'approved_at' => '2026-11-10 10:00:00',
        ]);
        $poMulti->items()->createMany([
            ['item_name' => 'Smart TV 50 Inch', 'quantity' => 1, 'unit_price' => 30000000, 'subtotal' => 30000000],
            ['item_name' => 'Smart TV 43 Inch', 'quantity' => 1, 'unit_price' => 25000000, 'subtotal' => 25000000],
            ['item_name' => 'Braket Swivel', 'quantity' => 2, 'unit_price' => 1650000, 'subtotal' => 3300000],
        ]);

        // 2. PO Bundling / PC Rakitan (simulasi bug legacy dobel hitung: subtotal 54,6 jt -> harus auto-heal ke 45,5 jt & anak part tidak bocor)
        $rfqBundle = Rfq::create([
            'rfq_number'    => '261103-0044',
            'customer_id'   => $custPenjalindo->id,
            'customer_name' => $custPenjalindo->company_name,
            'sales_id'      => $salesAde->id,
            'sales_name'    => $salesAde->name,
            'rfq_date'      => '2026-11-03',
            'status'        => Rfq::STATUS_GOAL,
        ]);
        $parentItem = RfqItem::create([
            'rfq_id'             => $rfqBundle->id,
            'is_bundle'          => true,
            'product_name'       => 'Access Point Ruijie RG-AP880-AR',
            'qty'                => 5,
            'unit'               => 'Unit',
            'hpp'                => 8027100,
            'margin'             => 13.37,
            'price_after_margin' => 9100000,
        ]);
        RfqItem::create([
            'rfq_id'             => $rfqBundle->id,
            'parent_id'          => $parentItem->id,
            'product_name'       => 'Part Internal AP Unit',
            'qty'                => 1,
            'unit'               => 'Unit',
            'hpp'                => 7000000,
            'margin'             => 14,
            'price_after_margin' => 8000000,
        ]);
        RfqItem::create([
            'rfq_id'             => $rfqBundle->id,
            'parent_id'          => $parentItem->id,
            'product_name'       => 'Part Injector POE',
            'qty'                => 1,
            'unit'               => 'Unit',
            'hpp'                => 1027100,
            'margin'             => 7,
            'price_after_margin' => 1100000,
        ]);

        $poBundle = PurchaseOrder::create([
            'po_number'   => 'PO-2026-0010',
            'customer_id' => $custPenjalindo->id,
            'sales_id'    => $salesAde->id,
            'rfq_id'      => $rfqBundle->id,
            'po_date'     => '2026-11-11',
            'status'      => PurchaseOrder::STATUS_GOAL,
            'subtotal'    => 54600000, // legacy inflated
            'tax_amount'  => 6006000,
            'grand_total' => 60606000,
            'approved_at' => '2026-11-11 10:00:00',
        ]);
        $poBundle->items()->createMany([
            ['item_name' => 'Access Point Ruijie RG-AP880-AR', 'quantity' => 5, 'unit_price' => 9100000, 'subtotal' => 45500000],
            ['item_name' => 'Part Internal AP Unit', 'quantity' => 1, 'unit_price' => 8000000, 'subtotal' => 8000000],
        ]);

        // 3. PO bulan lain (Oktober) -> tidak boleh ikut
        PurchaseOrder::create([
            'po_number'   => 'PO-2026-0001',
            'customer_id' => $custChuhatsu->id,
            'sales_id'    => $salesAde->id,
            'po_date'     => '2026-10-20',
            'status'      => PurchaseOrder::STATUS_GOAL,
            'subtotal'    => 9999999,
            'tax_amount'  => 0,
            'grand_total' => 9999999,
            'approved_at' => '2026-10-20 10:00:00',
        ]);

        // 4. PO status Pending -> tidak boleh ikut
        PurchaseOrder::create([
            'po_number'   => 'PO-2026-0099',
            'customer_id' => $custChuhatsu->id,
            'sales_id'    => $salesAde->id,
            'po_date'     => '2026-11-15',
            'status'      => PurchaseOrder::STATUS_PENDING,
            'subtotal'    => 5000000,
            'tax_amount'  => 550000,
            'grand_total' => 5550000,
        ]);

        // 5. PO milik Sales lain (Yudi) -> tidak boleh bocor meski Ade coba bypass sales_id (Anti-IDOR)
        $poYudi = PurchaseOrder::create([
            'po_number'   => 'PO-2026-0020',
            'customer_id' => $custWooin->id,
            'sales_id'    => $salesYudi->id,
            'po_date'     => '2026-11-13',
            'status'      => PurchaseOrder::STATUS_GOAL,
            'subtotal'    => 20700000,
            'tax_amount'  => 2277000,
            'grand_total' => 22977000,
            'approved_at' => '2026-11-13 10:00:00',
        ]);
        $poYudi->items()->create([
            'item_name'  => 'Switch Cisco 24 Port',
            'quantity'   => 1,
            'unit_price' => 20700000,
            'subtotal'   => 20700000,
        ]);

        // Ade mencoba mengirim sales_id milik Yudi
        $response = $this->actingAs($salesAde)->get(route('po.export', [
            'month'    => 11,
            'year'     => 2026,
            'sales_id' => $salesYudi->id,
        ]));

        $spreadsheet = $this->loadSpreadsheetFromResponse($response);

        // Hanya 1 sheet milik Ade Zulvida
        $this->assertSame(1, $spreadsheet->getSheetCount());
        $sheet = $spreadsheet->getSheet(0);
        $this->assertSame('Ade Zulvida', $sheet->getTitle());

        // Cek persis 4 kolom polos: Tanggal | Nama PT | Nama Item | Harga Exclude
        $this->assertSame('Tanggal', $sheet->getCell('A4')->getValue());
        $this->assertSame('Nama PT', $sheet->getCell('B4')->getValue());
        $this->assertSame('Nama Item', $sheet->getCell('C4')->getValue());
        $this->assertSame('Harga Exclude', $sheet->getCell('D4')->getValue());
        $this->assertNull($sheet->getCell('E4')->getValue());

        // Row 5: PO Multi-Item (10/11/2026)
        $this->assertSame('10/11/2026', $sheet->getCell('A5')->getValue());
        $this->assertSame('PT Chuhatsu Indonesia', $sheet->getCell('B5')->getValue());
        $this->assertSame('Smart TV 50 Inch + Smart TV 43 Inch + Braket Swivel', $sheet->getCell('C5')->getValue());
        $this->assertEquals(58300000, $sheet->getCell('D5')->getValue());

        // Row 6: PO Bundling (11/11/2026) -> child parts tidak muncul & harga auto-heal ke 45.500.000
        $this->assertSame('11/11/2026', $sheet->getCell('A6')->getValue());
        $this->assertSame('PT Penjalindo Nusantara', $sheet->getCell('B6')->getValue());
        $this->assertSame('Access Point Ruijie RG-AP880-AR', $sheet->getCell('C6')->getValue());
        $this->assertEquals(45500000, $sheet->getCell('D6')->getValue());

        // Row 7: Baris TOTAL pakai formula asli Excel =SUM(D5:D6)
        $this->assertSame('=SUM(D5:D6)', $sheet->getCell('D7')->getValue());
        $this->assertEquals(103800000, $sheet->getCell('D7')->getCalculatedValue());
    }

    public function test_leader_admin_superadmin_get_multi_sheet_and_can_filter_single_sales(): void
    {
        $leader = User::factory()->create(['name' => 'Rian Leader', 'role' => 'Leader', 'status' => 'Active']);
        $salesAde = User::factory()->create(['name' => 'Ade Zulvida', 'role' => 'Sales', 'status' => 'Active']);
        $salesYudi = User::factory()->create(['name' => 'Yudi Siswanto', 'role' => 'Sales', 'status' => 'Active']);

        $custA = Customer::create(['company_name' => 'PT Asama Indonesia', 'sales_id' => $salesAde->id, 'status' => 'Active']);
        $custB = Customer::create(['company_name' => 'PT Wooin Electronics', 'sales_id' => $salesYudi->id, 'status' => 'Active']);

        $poA = PurchaseOrder::create([
            'po_number'   => 'PO-2026-0003',
            'customer_id' => $custA->id,
            'sales_id'    => $salesAde->id,
            'po_date'     => '2026-11-04',
            'status'      => PurchaseOrder::STATUS_GOAL,
            'subtotal'    => 18120000,
            'tax_amount'  => 1993200,
            'grand_total' => 20113200,
        ]);
        $poA->items()->create(['item_name' => 'Laptop Lenovo Slim 3', 'quantity' => 2, 'unit_price' => 9060000, 'subtotal' => 18120000]);

        $poB = PurchaseOrder::create([
            'po_number'   => 'PO-2026-0020',
            'customer_id' => $custB->id,
            'sales_id'    => $salesYudi->id,
            'po_date'     => '2026-11-13',
            'status'      => PurchaseOrder::STATUS_GOAL,
            'subtotal'    => 20700000,
            'tax_amount'  => 2277000,
            'grand_total' => 22977000,
        ]);
        $poB->items()->create(['item_name' => 'Server Rack 42U', 'quantity' => 1, 'unit_price' => 20700000, 'subtotal' => 20700000]);

        // 1. Mode Semua Sales -> Multi-Sheet (Rekap Semua Sales + Ade Zulvida + Yudi Siswanto)
        $responseAll = $this->actingAs($leader)->get(route('po.export', [
            'month' => 11,
            'year'  => 2026,
        ]));

        $bookAll = $this->loadSpreadsheetFromResponse($responseAll);
        $this->assertSame(['Rekap Semua Sales', 'Ade Zulvida', 'Yudi Siswanto'], $bookAll->getSheetNames());

        // Sheet Rekap Semua Sales
        $rekap = $bookAll->getSheetByName('Rekap Semua Sales');
        $this->assertSame(['Tanggal', 'Sales', 'Nama PT', 'Nama Item', 'Harga Exclude'], [
            $rekap->getCell('A4')->getValue(),
            $rekap->getCell('B4')->getValue(),
            $rekap->getCell('C4')->getValue(),
            $rekap->getCell('D4')->getValue(),
            $rekap->getCell('E4')->getValue(),
        ]);
        $this->assertSame('=SUM(E5:E6)', $rekap->getCell('E7')->getValue());
        $this->assertEquals(38820000, $rekap->getCell('E7')->getCalculatedValue());

        // Sheet per Sales tetap 4 kolom polos
        $adeSheet = $bookAll->getSheetByName('Ade Zulvida');
        $this->assertSame('Harga Exclude', $adeSheet->getCell('D4')->getValue());
        $this->assertEquals(18120000, $adeSheet->getCell('D6')->getCalculatedValue());

        $yudiSheet = $bookAll->getSheetByName('Yudi Siswanto');
        $this->assertEquals(20700000, $yudiSheet->getCell('D6')->getCalculatedValue());

        // 2. Filter 1 Sales spesifik
        $responseYudi = $this->actingAs($leader)->get(route('po.export', [
            'month'    => 11,
            'year'     => 2026,
            'sales_id' => $salesYudi->id,
        ]));

        $bookYudi = $this->loadSpreadsheetFromResponse($responseYudi);
        $this->assertSame(['Yudi Siswanto'], $bookYudi->getSheetNames());
        $this->assertEquals(20700000, $bookYudi->getSheet(0)->getCell('D6')->getCalculatedValue());
    }

    public function test_empty_month_formula_injection_sanitization_and_validation(): void
    {
        $admin = User::factory()->create(['name' => 'Admin Purchase', 'role' => 'Admin', 'status' => 'Active']);
        $sales = User::factory()->create(['name' => 'Ade Zulvida', 'role' => 'Sales', 'status' => 'Active']);

        // 1. Bulan kosong (0 PO) tidak crash
        $emptyResp = $this->actingAs($admin)->get(route('po.export', [
            'month' => 1,
            'year'  => 2025,
        ]));
        $emptyBook = $this->loadSpreadsheetFromResponse($emptyResp);
        $this->assertSame(1, $emptyBook->getSheetCount());
        $this->assertEquals(0, $emptyBook->getSheet(0)->getCell('E6')->getValue());

        // 2. Sanitasi Excel Formula Injection
        $evilCustomer = Customer::create([
            'company_name' => "=CMD|' /C calc'!A0",
            'sales_id'     => $sales->id,
            'status'       => 'Active',
        ]);
        $poEvil = PurchaseOrder::create([
            'po_number'   => 'PO-2026-0077',
            'customer_id' => $evilCustomer->id,
            'sales_id'    => $sales->id,
            'po_date'     => '2026-11-18',
            'status'      => PurchaseOrder::STATUS_GOAL,
            'subtotal'    => 1000000,
            'tax_amount'  => 110000,
            'grand_total' => 1110000,
        ]);
        $poEvil->items()->create([
            'item_name'  => '+1+1',
            'quantity'   => 1,
            'unit_price' => 1000000,
            'subtotal'   => 1000000,
        ]);

        $evilResp = $this->actingAs($sales)->get(route('po.export', [
            'month' => 11,
            'year'  => 2026,
        ]));
        $evilBook = $this->loadSpreadsheetFromResponse($evilResp);
        $evilSheet = $evilBook->getSheet(0);

        $this->assertSame(DataType::TYPE_STRING, $evilSheet->getCell('B5')->getDataType());
        $this->assertSame("'" . $evilCustomer->company_name, $evilSheet->getCell('B5')->getValue());
        $this->assertStringStartsWith("'=", (string) $evilSheet->getCell('B5')->getValue());
        $this->assertSame(DataType::TYPE_STRING, $evilSheet->getCell('C5')->getDataType());
        $this->assertSame("'+1+1", $evilSheet->getCell('C5')->getValue());

        // 3. Validasi input bulan tidak valid
        $this->actingAs($admin)
            ->get(route('po.export', ['month' => 13, 'year' => 2026]))
            ->assertSessionHasErrors('month');
    }

    public function test_export_button_renders_on_pages_for_all_four_roles(): void
    {
        $sales = User::factory()->create(['name' => 'Ade Sales', 'role' => 'Sales', 'status' => 'Active']);
        $admin = User::factory()->create(['name' => 'Admin User', 'role' => 'Admin', 'status' => 'Active']);
        $leader = User::factory()->create(['name' => 'Leader User', 'role' => 'Leader', 'status' => 'Active']);
        $superAdmin = User::factory()->create(['name' => 'Super User', 'role' => 'Super Admin', 'status' => 'Active']);

        // Sales melihat tombol Export PO Excel di History Penjualan tanpa dropdown Sales
        $this->actingAs($sales)
            ->get(route('sales.history'))
            ->assertOk()
            ->assertSee('Export PO Excel')
            ->assertDontSee('Semua Sales (Multi-Sheet');

        // Admin, Leader, Super Admin melihat tombol Export PO Excel + dropdown Semua Sales di Purchase Orders & Analytics
        foreach ([$admin, $leader, $superAdmin] as $privilegedUser) {
            $this->actingAs($privilegedUser)
                ->get(route('po.index'))
                ->assertOk()
                ->assertSee('Export PO Excel')
                ->assertSee('Semua Sales (Multi-Sheet');

            $this->actingAs($privilegedUser)
                ->get(route('analytics.index'))
                ->assertOk()
                ->assertSee('Export PO Excel')
                ->assertSee('Semua Sales (Multi-Sheet');
        }
    }
}
