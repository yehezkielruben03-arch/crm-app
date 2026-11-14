<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use ZipArchive;

class BackupService
{
    protected string $backupDir;

    public function __construct()
    {
        $this->backupDir = storage_path('app/backups');
        if (!is_dir($this->backupDir)) {
            @mkdir($this->backupDir, 0777, true);
        }
        @chmod($this->backupDir, 0777);
    }

    /**
     * Dapatkan path direktori backup.
     */
    public function getBackupDir(): string
    {
        return $this->backupDir;
    }

    /**
     * Membuat backup lengkap (Database + Storage Uploads) atau parsial.
     *
     * @param array $options ['only_db' => bool, 'only_files' => bool, 'prefix' => string, 'retention_days' => int, 'created_by' => string]
     * @return array Metadata hasil backup
     */
    public function createBackup(array $options = []): array
    {
        $onlyDb = $options['only_db'] ?? false;
        $onlyFiles = $options['only_files'] ?? false;
        $prefix = $options['prefix'] ?? 'crm_backup';
        $retentionDays = $options['retention_days'] ?? 14;
        $createdBy = $options['created_by'] ?? (auth()->check() ? auth()->user()->name : 'System / Artisan');

        $timestamp = date('Y-m-d_His');
        $zipFilename = "{$prefix}_{$timestamp}.zip";
        $zipPath = $this->backupDir . DIRECTORY_SEPARATOR . $zipFilename;

        $tempDir = storage_path('app/backups/temp_' . uniqid());
        @mkdir($tempDir, 0777, true);
        @chmod($tempDir, 0777);

        $tablesCount = 0;
        $filesCount = 0;
        $sqlGzRelative = 'database.sql.gz';
        $sqlGzPath = $tempDir . DIRECTORY_SEPARATOR . $sqlGzRelative;

        try {
            // 1. Dump Database jika bukan only_files
            if (!$onlyFiles) {
                $tablesCount = $this->dumpDatabase($sqlGzPath);
            }

            // 2. Siapkan ZipArchive
            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new Exception("Gagal membuat arsip zip backup pada path: {$zipPath}");
            }

            // Masukkan database.sql.gz ke dalam zip
            if (!$onlyFiles && file_exists($sqlGzPath)) {
                $zip->addFile($sqlGzPath, 'database.sql.gz');
            }

            // 3. Masukkan file storage/app/public jika bukan only_db
            if (!$onlyDb) {
                $storagePublicDir = storage_path('app/public');
                if (is_dir($storagePublicDir)) {
                    $filesCount = $this->addDirectoryToZip($zip, $storagePublicDir, 'storage');
                }
            }

            // 4. Manifest metadata
            $manifest = [
                'app_name' => config('app.name', 'Pedia CRM'),
                'backup_type' => $onlyDb ? 'db_only' : ($onlyFiles ? 'files_only' : 'full'),
                'created_at' => now()->toDateTimeString(),
                'created_by' => $createdBy,
                'db_driver' => DB::connection()->getDriverName(),
                'tables_count' => $tablesCount,
                'files_count' => $filesCount,
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
            ];
            $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            $zip->close();
            if (file_exists($zipPath)) {
                @chmod($zipPath, 0666);
            }

            // 5. Bersihkan temp files
            if (file_exists($sqlGzPath)) {
                @unlink($sqlGzPath);
            }
            @rmdir($tempDir);

            // 6. Jalankan rotasi otomatis (hanya jika prefix default)
            $purgedCount = 0;
            if ($prefix === 'crm_backup') {
                $purgedCount = $this->pruneOldBackups($retentionDays);
            }

            $sizeBytes = file_exists($zipPath) ? filesize($zipPath) : 0;

            Log::info("Backup berhasil dibuat: {$zipFilename}", [
                'size_bytes' => $sizeBytes,
                'tables' => $tablesCount,
                'files' => $filesCount,
                'created_by' => $createdBy,
            ]);

            return [
                'success' => true,
                'filename' => $zipFilename,
                'path' => $zipPath,
                'size_bytes' => $sizeBytes,
                'size_formatted' => $this->formatBytes($sizeBytes),
                'tables_count' => $tablesCount,
                'files_count' => $filesCount,
                'purged_count' => $purgedCount,
                'manifest' => $manifest,
            ];
        } catch (Exception $e) {
            // Bersihkan sisa temp dan zip yang rusak
            if (file_exists($sqlGzPath)) {
                @unlink($sqlGzPath);
            }
            if (is_dir($tempDir)) {
                @rmdir($tempDir);
            }
            if (file_exists($zipPath)) {
                @unlink($zipPath);
            }

            Log::error("Gagal membuat backup: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            throw $e;
        }
    }

    /**
     * Melakukan restore dari file zip backup.
     * Selalu membuat safety snapshot terlebih dahulu sebelum data diubah.
     */
    public function restoreBackup(string $filename, array $options = []): array
    {
        // Cegah path traversal
        $cleanFilename = basename($filename);
        $zipPath = $this->backupDir . DIRECTORY_SEPARATOR . $cleanFilename;

        if (!file_exists($zipPath)) {
            throw new Exception("File backup tidak ditemukan: {$cleanFilename}");
        }

        $skipSnapshot = $options['skip_snapshot'] ?? false;
        $snapshotInfo = null;

        // 1. Buat Pre-Restore Safety Snapshot secara otomatis
        if (!$skipSnapshot) {
            $snapshotInfo = $this->createBackup([
                'prefix' => 'pre_restore_snapshot',
                'created_by' => 'Auto (Pre-Restore Safety Guard)',
            ]);
        }

        // 2. Ekstrak arsip ke temporary directory
        $tempExtractDir = storage_path('app/backups/restore_' . uniqid());
        @mkdir($tempExtractDir, 0777, true);
        @chmod($tempExtractDir, 0777);

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new Exception("Tidak dapat membuka file arsip backup untuk proses restore.");
        }

        $zip->extractTo($tempExtractDir);
        $zip->close();

        $tablesRestored = 0;
        $filesRestored = 0;

        try {
            // 3. Restore Database jika ada database.sql.gz
            $sqlGzPath = $tempExtractDir . DIRECTORY_SEPARATOR . 'database.sql.gz';
            if (file_exists($sqlGzPath)) {
                $tablesRestored = $this->restoreDatabaseFromGz($sqlGzPath);
            }

            // 4. Restore Storage Uploads jika ada folder storage/
            $storageExtractDir = $tempExtractDir . DIRECTORY_SEPARATOR . 'storage';
            if (is_dir($storageExtractDir)) {
                $targetStorageDir = storage_path('app/public');
                $filesRestored = $this->copyDirectory($storageExtractDir, $targetStorageDir);
            }

            // 5. Bersihkan cache aplikasi agar data langsung sinkron
            try {
                Artisan::call('optimize:clear');
            } catch (\Throwable $t) {
                Log::warning("Optimize clear pasca-restore gagal dijalankan: " . $t->getMessage());
            }

            // 6. Bersihkan temporary extract directory
            $this->deleteDirectory($tempExtractDir);

            Log::warning("Proses restore selesai dari file: {$cleanFilename}", [
                'tables_restored' => $tablesRestored,
                'files_restored' => $filesRestored,
                'safety_snapshot' => $snapshotInfo['filename'] ?? null,
            ]);

            return [
                'success' => true,
                'restored_file' => $cleanFilename,
                'tables_restored' => $tablesRestored,
                'files_restored' => $filesRestored,
                'safety_snapshot' => $snapshotInfo ? $snapshotInfo['filename'] : null,
                'restored_at' => now()->toDateTimeString(),
            ];
        } catch (Exception $e) {
            $this->deleteDirectory($tempExtractDir);
            Log::error("Gagal saat restore backup: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Membaca daftar semua file backup yang tersedia.
     */
    public function listBackups(): array
    {
        if (!is_dir($this->backupDir)) {
            return [];
        }

        $files = glob($this->backupDir . DIRECTORY_SEPARATOR . '*.zip');
        if (!$files) {
            return [];
        }

        $backups = [];
        foreach ($files as $filePath) {
            $filename = basename($filePath);
            $size = filesize($filePath);
            $modified = filemtime($filePath);

            // Cek manifest jika bisa dibaca cepat
            $manifest = $this->readManifestFromZip($filePath);

            $backups[] = [
                'filename' => $filename,
                'filepath' => $filePath,
                'size_bytes' => $size,
                'size_formatted' => $this->formatBytes($size),
                'created_at' => date('Y-m-d H:i:s', $modified),
                'is_snapshot' => str_starts_with($filename, 'pre_restore_snapshot_'),
                'type' => $manifest['backup_type'] ?? 'full',
                'created_by' => $manifest['created_by'] ?? 'Unknown',
                'tables_count' => $manifest['tables_count'] ?? '-',
                'files_count' => $manifest['files_count'] ?? '-',
            ];
        }

        // Urutkan dari yang paling baru
        usort($backups, fn($a, $b) => strcmp($b['filename'], $a['filename']));

        return $backups;
    }

    /**
     * Menghapus file backup tertentu.
     */
    public function deleteBackup(string $filename): bool
    {
        $cleanFilename = basename($filename);
        $filePath = $this->backupDir . DIRECTORY_SEPARATOR . $cleanFilename;

        if (file_exists($filePath) && str_ends_with($cleanFilename, '.zip')) {
            $result = @unlink($filePath);
            if ($result) {
                Log::info("File backup dihapus: {$cleanFilename}");
            }
            return $result;
        }

        return false;
    }

    /**
     * Membersihkan file backup yang lebih lama dari retention days.
     */
    public function pruneOldBackups(int $retentionDays = 14): int
    {
        if (!is_dir($this->backupDir) || $retentionDays < 1) {
            return 0;
        }

        $files = glob($this->backupDir . DIRECTORY_SEPARATOR . 'crm_backup_*.zip');
        if (!$files) {
            return 0;
        }

        $cutoffTime = time() - ($retentionDays * 86400);
        $purged = 0;

        foreach ($files as $file) {
            if (filemtime($file) < $cutoffTime) {
                if (@unlink($file)) {
                    $purged++;
                    Log::info("Auto-retention menghapus backup lama: " . basename($file));
                }
            }
        }

        // Bersihkan juga snapshot lama (> 7 hari)
        $snapshotCutoff = time() - (7 * 86400);
        $snapshots = glob($this->backupDir . DIRECTORY_SEPARATOR . 'pre_restore_snapshot_*.zip');
        if ($snapshots) {
            foreach ($snapshots as $snap) {
                if (filemtime($snap) < $snapshotCutoff) {
                    @unlink($snap);
                }
            }
        }

        return $purged;
    }

    /**
     * Dapatkan informasi kapasitas disk dan backup.
     */
    public function getDiskStats(): array
    {
        $backups = $this->listBackups();
        $totalBackupBytes = array_sum(array_column($backups, 'size_bytes'));

        $totalDisk = @disk_total_space($this->backupDir);
        $freeDisk = @disk_free_space($this->backupDir);

        return [
            'total_backups' => count($backups),
            'total_backup_bytes' => $totalBackupBytes,
            'total_backup_formatted' => $this->formatBytes($totalBackupBytes),
            'free_disk_bytes' => $freeDisk ?: 0,
            'free_disk_formatted' => $this->formatBytes($freeDisk ?: 0),
            'total_disk_bytes' => $totalDisk ?: 0,
            'total_disk_formatted' => $this->formatBytes($totalDisk ?: 0),
            'disk_usage_percent' => ($totalDisk && $freeDisk) ? round((($totalDisk - $freeDisk) / $totalDisk) * 100, 1) : 0,
            'last_backup' => !empty($backups) ? $backups[0]['created_at'] : null,
        ];
    }

    /**
     * Dump database ke file .sql.gz dengan streaming (memory friendly).
     */
    protected function dumpDatabase(string $sqlGzPath): int
    {
        $gz = gzopen($sqlGzPath, 'w9');
        if (!$gz) {
            throw new Exception("Gagal membuka file stream gzip untuk database dump.");
        }

        $driver = DB::connection()->getDriverName();
        $pdo = DB::connection()->getPdo();

        gzwrite($gz, "-- ========================================================\n");
        gzwrite($gz, "-- Pedia CRM Database Backup\n");
        gzwrite($gz, "-- Driver: {$driver}\n");
        gzwrite($gz, "-- Generated At: " . now()->toDateTimeString() . "\n");
        gzwrite($gz, "-- ========================================================\n\n");

        $tablesCount = 0;

        if ($driver === 'mysql') {
            gzwrite($gz, "SET FOREIGN_KEY_CHECKS=0;\n");
            gzwrite($gz, "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n");
            gzwrite($gz, "SET NAMES utf8mb4;\n\n");

            // Ambil daftar semua tabel
            $tables = [];
            $tableStmt = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
            while ($row = $tableStmt->fetch(\PDO::FETCH_NUM)) {
                $tables[] = $row[0];
            }

            foreach ($tables as $table) {
                $tablesCount++;
                gzwrite($gz, "-- --------------------------------------------------------\n");
                gzwrite($gz, "-- Table structure for table `{$table}`\n");
                gzwrite($gz, "-- --------------------------------------------------------\n");
                gzwrite($gz, "DROP TABLE IF EXISTS `{$table}`;\n");

                $createStmt = $pdo->query("SHOW CREATE TABLE `{$table}`");
                $createRow = $createStmt->fetch(\PDO::FETCH_ASSOC);
                $createSql = $createRow['Create Table'] ?? '';
                gzwrite($gz, $createSql . ";\n\n");

                // Dump data tabel secara chunked (100 rows per batch)
                $dataStmt = $pdo->query("SELECT * FROM `{$table}`");
                $batch = [];
                while ($row = $dataStmt->fetch(\PDO::FETCH_ASSOC)) {
                    $escapedValues = array_map(function ($val) use ($pdo) {
                        if (is_null($val)) return 'NULL';
                        return $pdo->quote($val);
                    }, array_values($row));

                    $batch[] = "(" . implode(', ', $escapedValues) . ")";

                    if (count($batch) >= 100) {
                        gzwrite($gz, "INSERT INTO `{$table}` VALUES " . implode(",\n", $batch) . ";\n");
                        $batch = [];
                    }
                }

                if (!empty($batch)) {
                    gzwrite($gz, "INSERT INTO `{$table}` VALUES " . implode(",\n", $batch) . ";\n\n");
                }
            }

            gzwrite($gz, "SET FOREIGN_KEY_CHECKS=1;\n");
        } else {
            // SQLite (Testing & Local development)
            gzwrite($gz, "PRAGMA foreign_keys = OFF;\n\n");

            $tables = [];
            $tableStmt = $pdo->query("SELECT name, sql FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
            while ($row = $tableStmt->fetch(\PDO::FETCH_ASSOC)) {
                $tables[] = $row;
            }

            foreach ($tables as $tableInfo) {
                $table = $tableInfo['name'];
                $tablesCount++;

                gzwrite($gz, "DROP TABLE IF EXISTS \"{$table}\";\n");
                gzwrite($gz, $tableInfo['sql'] . ";\n\n");

                $dataStmt = $pdo->query("SELECT * FROM \"{$table}\"");
                $batch = [];
                while ($row = $dataStmt->fetch(\PDO::FETCH_ASSOC)) {
                    $escapedValues = array_map(function ($val) use ($pdo) {
                        if (is_null($val)) return 'NULL';
                        return $pdo->quote($val);
                    }, array_values($row));

                    $batch[] = "(" . implode(', ', $escapedValues) . ")";

                    if (count($batch) >= 100) {
                        gzwrite($gz, "INSERT INTO \"{$table}\" VALUES " . implode(",\n", $batch) . ";\n");
                        $batch = [];
                    }
                }

                if (!empty($batch)) {
                    gzwrite($gz, "INSERT INTO \"{$table}\" VALUES " . implode(",\n", $batch) . ";\n\n");
                }
            }

            gzwrite($gz, "PRAGMA foreign_keys = ON;\n");
        }

        gzclose($gz);
        return $tablesCount;
    }

    /**
     * Restore database dari file .sql.gz
     */
    protected function restoreDatabaseFromGz(string $sqlGzPath): int
    {
        $gz = gzopen($sqlGzPath, 'r');
        if (!$gz) {
            throw new Exception("Gagal membaca file database.sql.gz arsip backup.");
        }

        $driver = DB::connection()->getDriverName();
        $pdo = DB::connection()->getPdo();

        if ($driver === 'mysql') {
            $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
        } else {
            $pdo->exec("PRAGMA foreign_keys = OFF");
        }

        $currentStatement = '';
        $tablesRestored = 0;

        while (!gzeof($gz)) {
            $line = gzgets($gz, 65536);
            if ($line === false) break;

            $trimmed = trim($line);
            // Abaikan komentar
            if (str_starts_with($trimmed, '--') || str_starts_with($trimmed, '/*') || empty($trimmed)) {
                continue;
            }

            $currentStatement .= $line;

            // Jika akhir pernyataan SQL ';'
            if (str_ends_with($trimmed, ';')) {
                try {
                    $pdo->exec($currentStatement);
                    if (stripos($currentStatement, 'CREATE TABLE') !== false) {
                        $tablesRestored++;
                    }
                } catch (\PDOException $e) {
                    Log::warning("SQL execution note during restore: " . $e->getMessage());
                }
                $currentStatement = '';
            }
        }

        gzclose($gz);

        if ($driver === 'mysql') {
            $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
        } else {
            $pdo->exec("PRAGMA foreign_keys = ON");
        }

        return $tablesRestored;
    }

    /**
     * Menambahkan folder rekursif ke dalam zip.
     */
    protected function addDirectoryToZip(ZipArchive $zip, string $dirPath, string $zipSubdir = ''): int
    {
        $filesCount = 0;
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dirPath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($files as $file) {
            $filePath = $file->getRealPath();
            $filename = $file->getFilename();

            // Abaikan file sistem sampah
            if (in_array($filename, ['.DS_Store', 'Thumbs.db', '.gitignore'])) {
                continue;
            }

            $relativePath = substr($filePath, strlen($dirPath) + 1);
            $zipEntryPath = $zipSubdir ? ($zipSubdir . '/' . str_replace('\\', '/', $relativePath)) : str_replace('\\', '/', $relativePath);

            if ($file->isDir()) {
                $zip->addEmptyDir($zipEntryPath);
            } elseif ($file->isFile()) {
                $zip->addFile($filePath, $zipEntryPath);
                $filesCount++;
            }
        }

        return $filesCount;
    }

    /**
     * Salin seluruh isi folder secara rekursif (untuk restore storage).
     */
    protected function copyDirectory(string $source, string $destination): int
    {
        if (!is_dir($destination)) {
            @mkdir($destination, 0777, true);
            @chmod($destination, 0777);
        }

        $filesCount = 0;
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($items as $item) {
            $destPath = $destination . DIRECTORY_SEPARATOR . $items->getSubPathname();
            if ($item->isDir()) {
                if (!is_dir($destPath)) {
                    @mkdir($destPath, 0777, true);
                    @chmod($destPath, 0777);
                }
            } else {
                @copy($item->getRealPath(), $destPath);
                @chmod($destPath, 0666);
                $filesCount++;
            }
        }

        return $filesCount;
    }

    /**
     * Hapus direktori rekursif (cleanup temp).
     */
    protected function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) return;

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            if ($item->isDir()) {
                @rmdir($item->getRealPath());
            } else {
                @unlink($item->getRealPath());
            }
        }
        @rmdir($dir);
    }

    /**
     * Baca manifest metadata dari dalam zip tanpa mengekstrak keseluruhan file.
     */
    protected function readManifestFromZip(string $zipPath): array
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) === true) {
            $manifestJson = $zip->getFromName('manifest.json');
            $zip->close();
            if (file_exists($zipPath)) {
                @chmod($zipPath, 0666);
            }
            if ($manifestJson) {
                return json_decode($manifestJson, true) ?: [];
            }
        }
        return [];
    }

    /**
     * Format byte ke string yang mudah dibaca (KB, MB, GB).
     */
    protected function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = (int) floor(log($bytes, 1024));
        return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
    }
}
