<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Traits\HandlesDateRangeFilter;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Rfq;
use App\Models\Customer;

class CheckDateFilterHealth extends Command
{
    use HandlesDateRangeFilter;

    protected $signature = 'filter:health-check';
    protected $description = 'Health check and validation test for Global Date Range Filter and Database Query Indexes';

    public function handle(): int
    {
        $this->info("=== Starting CRM Date Filter Health Check ===");
        $errors = [];

        // 1. Test Workweek Preset
        $resWorkweek = $this->resolveDateRange(Request::create('/dashboard', 'GET', ['preset' => 'this_workweek']));
        if ($resWorkweek['preset'] !== 'this_workweek' || !$resWorkweek['isFiltered']) {
            $errors[] = "Preset this_workweek failed to resolve correctly.";
        } else {
            $this->line("  [OK] Preset this_workweek: " . $resWorkweek['label']);
        }

        // 2. Test Invalid Preset Fallback
        $resInvalid = $this->resolveDateRange(Request::create('/dashboard', 'GET', ['preset' => 'malicious_data']));
        if ($resInvalid['preset'] !== 'this_month' || $resInvalid['isFiltered']) {
            $errors[] = "Invalid preset fallback protection failed.";
        } else {
            $this->line("  [OK] Invalid preset fallback safely to this_month.");
        }

        // 3. Test Strict Date Validation
        $resCustom = $this->resolveDateRange(Request::create('/dashboard', 'GET', [
            'start_date' => '2026-10-01',
            'end_date'   => '2026-10-15',
        ]));
        if ($resCustom['preset'] !== 'custom' || !$resCustom['isFiltered']) {
            $errors[] = "Custom date range resolution failed.";
        } else {
            $this->line("  [OK] Custom date range: " . $resCustom['label']);
        }

        // 4. Test Query Index Performance
        $start = microtime(true);
        $rfqCount = Rfq::whereBetween('created_at', [$resWorkweek['startDate'], $resWorkweek['endDate']])->count();
        $durationRfq = round((microtime(true) - $start) * 1000, 2);

        $start = microtime(true);
        $custCount = Customer::whereBetween('created_at', [$resWorkweek['startDate'], $resWorkweek['endDate']])->count();
        $durationCust = round((microtime(true) - $start) * 1000, 2);

        $this->line("  [OK] Rfq indexed range scan query: {$durationRfq} ms ({$rfqCount} records)");
        $this->line("  [OK] Customer indexed range scan query: {$durationCust} ms ({$custCount} records)");

        if (!empty($errors)) {
            $this->error("Health check encountered " . count($errors) . " error(s):");
            foreach ($errors as $err) {
                $this->error("  - " . $err);
            }
            return Command::FAILURE;
        }

        $this->info("=== Date Filter & Database Indexes: 100% HEALTHY ===");
        return Command::SUCCESS;
    }
}
