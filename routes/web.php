<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\RfqController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\RegionController;

Route::get('/deploy', function () {
    if (request('key') !== 'crm2026') {
        abort(403, 'Key salah.');
    }
    $output = [];
    $exitCode = Artisan::call('migrate', ['--force' => true]);
    $output[] = 'Migrate: ' . ($exitCode === 0 ? 'OK' : 'GAGAL') . ' — ' . Artisan::output();

    $exitCode = Artisan::call('db:seed', ['--class' => 'RolePermissionSeeder', '--force' => true]);
    $output[] = 'Seed: ' . ($exitCode === 0 ? 'OK' : 'GAGAL') . ' — ' . Artisan::output();

    // Seed user + customer + rfq kalau ada param &seed=all
    if (request('seed') === 'all') {
        $exitCode = Artisan::call('db:seed', ['--class' => 'UserSeeder', '--force' => true]);
        $output[] = 'UserSeed: ' . ($exitCode === 0 ? 'OK' : 'GAGAL');

        $exitCode = Artisan::call('db:seed', ['--class' => 'CustomerSeeder', '--force' => true]);
        $output[] = 'CustomerSeed: ' . ($exitCode === 0 ? 'OK' : 'GAGAL');

        $exitCode = Artisan::call('db:seed', ['--class' => 'RfqSeeder', '--force' => true]);
        $output[] = 'RfqSeed: ' . ($exitCode === 0 ? 'OK' : 'GAGAL');
    }

    $exitCode = Artisan::call('storage:link', ['--force' => true]);
    $output[] = 'Storage Link: ' . ($exitCode === 0 ? 'OK' : 'OK (maybe already exists)') . ' — ' . Artisan::output();

    Artisan::call('optimize:clear');
    $output[] = 'Cache Clear: OK';

    echo '<pre>' . implode("\n\n", $output) . '</pre>';
    echo '<p style="color:green;font-weight:bold;font-size:18px;">✅ DEPLOYMENT SELESAI! HAPUS FILE routes/web.php atau komen route ini ya!</p>';
    exit;
});

Route::get('/force-sync', function () {
    $customers = \App\Models\Customer::all();
    $output = [];
    $count = 0;
    foreach ($customers as $c) {
        $cleanedNotes = false;
        $notes = $c->notes;
        if (!empty($notes) && (str_contains(strtoupper($notes), '=IF(') || str_contains(strtoupper($notes), 'TODAY()'))) {
            if (preg_match('/"([^"]+)"/', $notes, $matches)) {
                $c->updateQuietly(['notes' => trim($matches[1])]);
            } else {
                $c->updateQuietly(['notes' => null]);
            }
            $cleanedNotes = true;
        }

        $migratedContact = false;
        $cpName = $c->cp_name ?? '';
        if (!empty(trim($cpName))) {
            $exists = $c->contacts()->where('name', trim($cpName))->exists();
            if (!$exists) {
                $c->contacts()->create([
                    'name' => trim($cpName),
                    'position' => $c->cp_position ?? null,
                    'email' => $c->cp_email ?? null,
                    'phone' => $c->cp_phone ?? null,
                    'office_phone' => $c->office_phone ?? null,
                    'whatsapp' => $c->whatsapp ?? null,
                    'division' => $c->division ?? null,
                    'preferred_contact' => $c->preferred_contact ?? null,
                    'is_primary' => true
                ]);
                $migratedContact = true;
            }
        }
        
        if ($cleanedNotes || $migratedContact) {
            $count++;
            $output[] = "Customer ID {$c->id} (" . ($c->company_name ?? 'Tanpa Nama') . "): " . 
                        ($cleanedNotes ? '[Notes Dibersihkan] ' : '') . 
                        ($migratedContact ? '[Kontak Dimigrasi]' : '');
        }
    }
    
    return '<h3>Force Sync Selesai</h3>' . 
           '<p>Berhasil update ' . $count . ' data customer.</p>' . 
           '<pre>' . (empty($output) ? 'Tidak ada data yang perlu diupdate (sudah bersih).' : implode("\n", $output)) . '</pre>';
});

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth'])
    ->name('dashboard');

// BUGFIX (Routing Leak): endpoint status auth YANG DIKECUALIKAN dari middleware 'auth'.
// Dipakai halaman login untuk cek session (skenario bfcache/BACK setelah login sukses)
// tanpa menodai 'url.intended' seperti polling '/api/notifications/poll'.
Route::get('/api/auth/status', function () {
    return response()->json(['authenticated' => auth()->check()]);
})->name('api.auth.status');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ─── Notifikasi (semua user yang sudah login) ───
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::get('/api/notifications/poll', [NotificationController::class, 'poll'])->name('notifications.poll');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.markAllRead');

    // ─── API Wilayah Indonesia (Dependent Select: Provinsi -> Kota -> Kec -> Kel) ───
    Route::get('/api/region/cities',    [RegionController::class, 'cities'])->name('region.cities');
    Route::get('/api/region/districts', [RegionController::class, 'districts'])->name('region.districts');
    Route::get('/api/region/villages',  [RegionController::class, 'villages'])->name('region.villages');

    // ─── Customer Access (Sales, Manager, Admin, Super Admin) ───
    Route::middleware('permission:Customer Access')->group(function () {
        Route::get('/api/customers/suggestions', [CustomerController::class, 'suggestions'])->name('customers.suggestions');
        Route::get('/api/customers/check-duplicate', [CustomerController::class, 'checkDuplicate'])->name('customers.check-duplicate');
        Route::post('/api/customers/{customer}/contacts', [CustomerController::class, 'storeContactAjax'])->name('customers.storeContactAjax');
        Route::put('/api/customers/{customer}/contacts/{contact}', [CustomerController::class, 'updateContactAjax'])->name('customers.updateContactAjax');
        Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::get('/customers/create', [CustomerController::class, 'create'])->name('customers.create');
        Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
        Route::get('/customers/trash', [CustomerController::class, 'trash'])->name('customers.trash');
        Route::get('/customers/export', [CustomerController::class, 'export'])->name('customers.export');
        Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
        Route::get('/customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
        Route::put('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
        Route::post('/customers/{customer}/approve', [CustomerController::class, 'approve'])->name('customers.approve');
        Route::post('/customers/bulk-approve', [CustomerController::class, 'bulkApprove'])->name('customers.bulk-approve');
        Route::post('/customers/import/preview', [CustomerController::class, 'previewImport'])->name('customers.import.preview');
        Route::post('/customers/import/process', [CustomerController::class, 'processImport'])->name('customers.import.process');
        Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');
        Route::post('/customers/{customer}/restore', [CustomerController::class, 'restore'])->name('customers.restore');

        // PO Routes
        Route::get('/purchase-orders', [PurchaseOrderController::class, 'index'])->name('po.index');
        Route::get('/purchase-orders/create', [PurchaseOrderController::class, 'create'])->name('po.create');
        Route::post('/purchase-orders', [PurchaseOrderController::class, 'store'])->name('po.store');
        Route::get('/purchase-orders/{po}', [PurchaseOrderController::class, 'show'])->name('po.show');
        Route::get('/purchase-orders/{po}/edit', [PurchaseOrderController::class, 'edit'])->name('po.edit');
        Route::put('/purchase-orders/{po}', [PurchaseOrderController::class, 'update'])->name('po.update');
        Route::post('/purchase-orders/{po}/request-change', [PurchaseOrderController::class, 'requestChange'])->name('po.request-change');
        Route::post('/purchase-orders/parse-pdf', [PurchaseOrderController::class, 'parsePdf'])->name('po.parse-pdf');
        Route::get('/purchase-orders/{po}/download-pdf', [PurchaseOrderController::class, 'downloadPdf'])->name('po.download-pdf');

        // RFQ Routes — Semua user yang punya Customer Access
        Route::get('/rfqs/customer/{customer}', [RfqController::class, 'getCustomer'])->name('rfq.get-customer');
        Route::get('/rfqs', [RfqController::class, 'index'])->name('rfq.index');
        Route::get('/rfqs/create', [RfqController::class, 'create'])->name('rfq.create');
        Route::post('/rfqs', [RfqController::class, 'store'])->name('rfq.store');
        // RFQ Projek — Hanya Admin Purchase & Super Admin (harus sebelum wildcard /rfqs/{rfq})
        Route::middleware('role:Admin,Admin Purchase,Super Admin,Leader')->group(function () {
            Route::get('/rfqs/create-project', [RfqController::class, 'createProject'])->name('rfq.create_project');
            Route::post('/rfqs/project', [RfqController::class, 'storeProject'])->name('rfq.store_project');
        });

        Route::get('/rfqs/{rfq}', [RfqController::class, 'show'])->name('rfq.show');
        Route::get('/rfqs/{rfq}/edit', [RfqController::class, 'edit'])->name('rfq.edit');
        Route::put('/rfqs/{rfq}', [RfqController::class, 'update'])->name('rfq.update');
        Route::delete('/rfqs/{rfq}', [RfqController::class, 'destroy'])->name('rfq.destroy');
        Route::get('/rfqs/{rfq}/price', [RfqController::class, 'priceForm'])->name('rfq.price_form');
        Route::post('/rfqs/{rfq}/price', [RfqController::class, 'submitPrice'])->name('rfq.submit_price');
        Route::post('/rfqs/{rfq}/approve', [RfqController::class, 'approve'])->name('rfq.approve');
        Route::post('/rfqs/{rfq}/reject', [RfqController::class, 'reject'])->name('rfq.reject');
        Route::get('/rfqs/{rfq}/download-quotation', [RfqController::class, 'downloadQuotation'])->name('rfq.download_quotation');
        Route::get('/rfqs/{rfq}/preview-quotation', [RfqController::class, 'previewQuotation'])->name('rfq.preview_quotation');
        Route::post('/rfqs/{rfq}/mark-quotation-sent', [RfqController::class, 'markQuotationSent'])->name('rfq.mark_quotation_sent');
        Route::get('/rfqs/{rfq}/revisi-qty', [RfqController::class, 'editQty'])->name('rfq.edit_qty');
        Route::put('/rfqs/{rfq}/revisi-qty', [RfqController::class, 'updateQty'])->name('rfq.update_qty');
        Route::post('/rfqs/{rfq}/verify-po', [RfqController::class, 'verifyPo'])->name('rfq.verify_po');
        Route::post('/rfqs/{rfq}/upload-po', [RfqController::class, 'uploadPo'])->name('rfq.upload_po');
        Route::post('/rfqs/{rfq}/approve-goal', [RfqController::class, 'approveGoal'])->name('rfq.approve_goal');

        Route::get('/sales/history', [RfqController::class, 'history'])->name('sales.history');

        // Quotation Routes
        Route::post('/quotations/parse-pdf', [QuotationController::class, 'parsePdf'])->name('quo.parse-pdf');
        Route::get('/quotations/{quotation}/download-pdf', [QuotationController::class, 'downloadPdf'])->name('quo.download-pdf');
        Route::post('/quotations/{quotation}/send', [QuotationController::class, 'send'])->name('quo.send');
        Route::post('/quotations/{quotation}/approve', [QuotationController::class, 'approve'])->name('quo.approve');
        Route::post('/quotations/{quotation}/revise', [QuotationController::class, 'revise'])->name('quo.revise');
        Route::post('/quotations/{quotation}/process-goal', [QuotationController::class, 'processGoal'])->name('quo.process-goal');
        Route::get('/quotations', [QuotationController::class, 'index'])->name('quo.index');
        Route::get('/quotations/create', [QuotationController::class, 'create'])->name('quo.create');
        Route::post('/quotations', [QuotationController::class, 'store'])->name('quo.store');
        Route::get('/quotations/{quotation}', [QuotationController::class, 'show'])->name('quo.show');
        Route::get('/quotations/{quotation}/edit', [QuotationController::class, 'edit'])->name('quo.edit');
        Route::put('/quotations/{quotation}', [QuotationController::class, 'update'])->name('quo.update');
        Route::delete('/quotations/{quotation}', [QuotationController::class, 'destroy'])->name('quo.destroy');
    });

    // ─── Approval (Manager, Super Admin) ───
    Route::middleware('permission:Approval')->group(function () {
        Route::post('/purchase-orders/{po}/approve', [PurchaseOrderController::class, 'approve'])->name('po.approve');
        Route::post('/purchase-orders/{po}/reject', [PurchaseOrderController::class, 'reject'])->name('po.reject');
        Route::get('/approvals', [ApprovalController::class, 'index'])->name('approvals.index');
    });

    // ─── Report Access ───
    Route::middleware('permission:Report Access')->group(function () {
        Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
    });

    // ─── Admin Purchase & Super Admin Only ───
    Route::middleware('role:Admin,Admin Purchase,Super Admin,Leader')->group(function () {
        Route::resource('vendors', \App\Http\Controllers\VendorController::class);
        Route::resource('mainpowers', \App\Http\Controllers\MainpowerController::class);

        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit')->withTrashed();
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update')->withTrashed();
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy')->withTrashed();
        Route::post('/users/{user}/restore', [UserController::class, 'restore'])->name('users.restore')->withTrashed();
        Route::post('/users/{user}/migrate-customers', [UserController::class, 'migrateCustomers'])->name('users.migrate-customers')->withTrashed();
    });
});

require __DIR__.'/auth.php';
