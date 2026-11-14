<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Customer;
use App\Services\BackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use ZipArchive;

class BackupAndRestoreFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected BackupService $backupService;
    protected string $backupDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->backupService = app(BackupService::class);
        $this->backupDir = $this->backupService->getBackupDir();
    }

    protected function tearDown(): void
    {
        // Bersihkan file backup hasil testing
        if (is_dir($this->backupDir)) {
            $files = glob($this->backupDir . DIRECTORY_SEPARATOR . '*.zip');
            if ($files) {
                foreach ($files as $f) {
                    @unlink($f);
                }
            }
        }
        parent::tearDown();
    }

    public function test_backup_service_creates_valid_zip_with_manifest_and_db_dump(): void
    {
        // 1. Buat data testing
        Customer::create([
            'company_name' => 'PT Backup Test Mandiri',
            'customer_type' => 'End User',
            'status' => 'Active',
            'address' => 'Jl. Pengujian No. 12',
            'city' => 'Jakarta',
        ]);

        // 2. Jalankan backup via Service
        $result = $this->backupService->createBackup([
            'prefix' => 'crm_backup',
            'created_by' => 'Unit Test Runner',
        ]);

        $this->assertTrue($result['success']);
        $this->assertFileExists($result['path']);
        $this->assertGreaterThan(0, $result['size_bytes']);

        // 3. Verifikasi isi zip
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($result['path']));
        $this->assertNotFalse($zip->locateName('manifest.json'));
        $this->assertNotFalse($zip->locateName('database.sql.gz'));

        $manifest = json_decode($zip->getFromName('manifest.json'), true);
        $this->assertEquals('Unit Test Runner', $manifest['created_by']);
        $this->assertEquals('full', $manifest['backup_type']);
        $zip->close();
    }

    public function test_backup_retention_pruning_removes_expired_backups(): void
    {
        // Buat file backup kadaluarsa (> 14 hari)
        $oldFile = $this->backupDir . DIRECTORY_SEPARATOR . 'crm_backup_old_test.zip';
        file_put_contents($oldFile, 'dummy zip content');
        touch($oldFile, time() - (20 * 86400)); // 20 hari lalu

        // Buat file backup baru
        $recentFile = $this->backupDir . DIRECTORY_SEPARATOR . 'crm_backup_recent_test.zip';
        file_put_contents($recentFile, 'dummy zip content');
        touch($recentFile, time()); // Baru saja

        $purgedCount = $this->backupService->pruneOldBackups(14);

        $this->assertEquals(1, $purgedCount);
        $this->assertFileDoesNotExist($oldFile);
        $this->assertFileExists($recentFile);

        @unlink($recentFile);
    }

    public function test_restore_service_creates_safety_snapshot_and_restores_database(): void
    {
        // 1. Buat data awal
        $customer = Customer::create([
            'company_name' => 'PT Data Asli Sebelum Restore',
            'customer_type' => 'End User',
            'status' => 'Active',
            'address' => 'Jl. Asli No. 1',
            'city' => 'Bandung',
        ]);

        // 2. Buat backup dari kondisi ini
        $backupResult = $this->backupService->createBackup([
            'prefix' => 'crm_backup',
            'created_by' => 'Snapshot Test',
        ]);

        // 3. Ubah nama perusahaan untuk mensimulasikan insiden perubahan data
        $customer->update(['company_name' => 'PT Data Rusak Terubah']);
        $this->assertDatabaseHas('customers', ['company_name' => 'PT Data Rusak Terubah']);

        // 4. Lakukan Restore dari backup
        $restoreResult = $this->backupService->restoreBackup($backupResult['filename']);

        $this->assertTrue($restoreResult['success']);
        $this->assertNotNull($restoreResult['safety_snapshot']);
        $this->assertFileExists($this->backupDir . DIRECTORY_SEPARATOR . $restoreResult['safety_snapshot']);

        // 5. Verifikasi bahwa database kembali ke kondisi semula
        $this->assertDatabaseHas('customers', ['company_name' => 'PT Data Asli Sebelum Restore']);
        $this->assertDatabaseMissing('customers', ['company_name' => 'PT Data Rusak Terubah']);
    }

    public function test_super_admin_can_access_backup_dashboard_and_download(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'Super Admin',
        ]);

        // 1. Akses halaman index
        $response = $this->actingAs($superAdmin)->get(route('settings.backups.index'));
        $response->assertStatus(200);
        $response->assertSee('Pusat Backup', false);

        // 2. Buat backup via POST
        $createResponse = $this->actingAs($superAdmin)->post(route('settings.backups.store'), [
            'backup_type' => 'full',
        ]);
        $createResponse->assertRedirect(route('settings.backups.index'));
        $createResponse->assertSessionHas('success');

        // 3. Verifikasi file muncul dan bisa di-download
        $backups = $this->backupService->listBackups();
        $this->assertNotEmpty($backups);
        $targetFilename = $backups[0]['filename'];

        $downloadResponse = $this->actingAs($superAdmin)->get(route('settings.backups.download', $targetFilename));
        $downloadResponse->assertStatus(200);
        $this->assertEquals('application/zip', $downloadResponse->headers->get('Content-Type'));

        // 4. Hapus file via DELETE
        $deleteResponse = $this->actingAs($superAdmin)->delete(route('settings.backups.destroy', $targetFilename));
        $deleteResponse->assertRedirect(route('settings.backups.index'));
        $deleteResponse->assertSessionHas('success');
    }

    public function test_non_super_admin_cannot_access_backup_endpoints(): void
    {
        $salesUser = User::factory()->create([
            'role' => 'Sales Marketing',
        ]);

        // Sales Marketing mencoba akses index -> 403 Forbidden
        $response = $this->actingAs($salesUser)->get(route('settings.backups.index'));
        $response->assertStatus(403);

        // Sales Marketing mencoba POST buat backup -> 403 Forbidden
        $postResponse = $this->actingAs($salesUser)->post(route('settings.backups.store'), [
            'backup_type' => 'full',
        ]);
        $postResponse->assertStatus(403);
    }

    public function test_artisan_crm_backup_and_restore_commands_execute_cleanly(): void
    {
        // 1. Eksekusi crm:backup via CLI
        $exitCode = Artisan::call('crm:backup', [
            '--retention' => 14,
        ]);
        $this->assertEquals(0, $exitCode);

        $backups = $this->backupService->listBackups();
        $this->assertNotEmpty($backups);
        $latestFilename = $backups[0]['filename'];

        // 2. Eksekusi crm:restore via CLI dengan --force
        $restoreExitCode = Artisan::call('crm:restore', [
            'file' => $latestFilename,
            '--force' => true,
        ]);
        $this->assertEquals(0, $restoreExitCode);
    }
}
