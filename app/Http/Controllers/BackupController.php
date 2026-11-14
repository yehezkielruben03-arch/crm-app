<?php

namespace App\Http\Controllers;

use App\Services\BackupService;
use Exception;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupController extends Controller
{
    protected BackupService $backupService;

    public function __construct(BackupService $backupService)
    {
        $this->backupService = $backupService;
    }

    /**
     * Tampilkan halaman manajemen backup & restore.
     */
    public function index()
    {
        $backups = $this->backupService->listBackups();
        $diskStats = $this->backupService->getDiskStats();

        return view('settings.backups.index', compact('backups', 'diskStats'));
    }

    /**
     * Buat backup baru secara instan dari Web UI.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'backup_type' => 'required|in:full,db_only,files_only',
        ]);

        $onlyDb = $validated['backup_type'] === 'db_only';
        $onlyFiles = $validated['backup_type'] === 'files_only';

        try {
            $result = $this->backupService->createBackup([
                'only_db' => $onlyDb,
                'only_files' => $onlyFiles,
                'retention_days' => 14,
                'created_by' => auth()->user()->name . ' (Web UI)',
            ]);

            return redirect()->route('settings.backups.index')
                ->with('success', "Backup berhasil dibuat: {$result['filename']} ({$result['size_formatted']})");
        } catch (Exception $e) {
            return redirect()->route('settings.backups.index')
                ->with('error', "Gagal membuat backup: " . $e->getMessage());
        }
    }

    /**
     * Download file zip backup.
     */
    public function download(string $filename): BinaryFileResponse
    {
        $cleanFilename = basename($filename);
        $backupDir = $this->backupService->getBackupDir();
        $filePath = $backupDir . DIRECTORY_SEPARATOR . $cleanFilename;

        if (!file_exists($filePath) || !str_ends_with($cleanFilename, '.zip')) {
            abort(404, 'File backup tidak ditemukan atau tidak valid.');
        }

        return response()->download($filePath, $cleanFilename, [
            'Content-Type' => 'application/zip',
        ]);
    }

    /**
     * Restore database dan storage dari file backup.
     */
    public function restore(Request $request)
    {
        $request->validate([
            'filename' => 'required|string',
            'confirm_text' => 'required|in:RESTORE',
        ], [
            'confirm_text.in' => 'Konfirmasi tidak sesuai. Harap ketik RESTORE dengan huruf kapital untuk melanjutkan.',
        ]);

        $filename = basename($request->input('filename'));

        try {
            $result = $this->backupService->restoreBackup($filename);

            $snapshotNote = !empty($result['safety_snapshot']) 
                ? " (Safety snapshot dibuat: {$result['safety_snapshot']})"
                : "";

            return redirect()->route('settings.backups.index')
                ->with('success', "Sistem berhasil dipulihkan dari file: {$filename}. {$result['tables_restored']} tabel DB & {$result['files_restored']} dokumen storage telah disinkronkan.{$snapshotNote}");
        } catch (Exception $e) {
            return redirect()->route('settings.backups.index')
                ->with('error', "Gagal melakukan restore: " . $e->getMessage());
        }
    }

    /**
     * Hapus file backup tertentu.
     */
    public function destroy(string $filename)
    {
        $cleanFilename = basename($filename);

        if ($this->backupService->deleteBackup($cleanFilename)) {
            return redirect()->route('settings.backups.index')
                ->with('success', "File backup {$cleanFilename} berhasil dihapus.");
        }

        return redirect()->route('settings.backups.index')
            ->with('error', "Gagal menghapus file backup {$cleanFilename}.");
    }
}
