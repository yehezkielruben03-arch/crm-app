<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    private function rawMonth(string $column)
    {
        return DB::getDriverName() === 'sqlite'
            ? DB::raw("CAST(strftime('%m', {$column}) AS INTEGER) as month")
            : DB::raw("MONTH({$column}) as month");
    }

    private function rawYear(string $column)
    {
        return DB::getDriverName() === 'sqlite'
            ? DB::raw("CAST(strftime('%Y', {$column}) AS INTEGER) as year")
            : DB::raw("YEAR({$column}) as year");
    }

    private function buildMonthlySeries(int $months)
    {
        $now = now();
        return collect(range(0, $months - 1))
            ->reverse()
            ->map(function ($index) use ($now) {
                $date = $now->copy()->subMonths($index);
                return [
                    'year' => $date->year,
                    'month' => $date->month,
                ];
            });
    }

    private function monthLabel(int $month): string
    {
        $labels = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agt','Sep','Okt','Nov','Des'];
        return $labels[$month - 1] ?? '';
    }

    public function index()
    {
        $now = now();

        // ── Overall Revenue Stats ──
        $totalRevenue   = PurchaseOrder::where('status', PurchaseOrder::STATUS_GOAL)->sum('grand_total');
        $totalCompletedPO = PurchaseOrder::where('status', PurchaseOrder::STATUS_GOAL)->count();
        $aov            = $totalCompletedPO > 0 ? $totalRevenue / $totalCompletedPO : 0;
        [$totalCustomers, $activeCustomers] = Cache::remember('analytics:customer_stats', 300, function () {
            return [
                Customer::count(),
                Customer::where('status', 'Active')->count(),
            ];
        });

        $pendingReviewCount = Cache::remember('analytics:pending_review_count', 60, function () {
            $pendingCustomers = Customer::whereIn('status', Customer::pendingApprovalStatuses())->count();
            $pendingPurchaseOrders = PurchaseOrder::whereIn('status', [PurchaseOrder::STATUS_PENDING, PurchaseOrder::STATUS_REVISI])->count();

            return $pendingCustomers + $pendingPurchaseOrders;
        });

        // ── Revenue bulan ini vs bulan lalu ──
        $revenueThisMonth = Cache::remember('analytics:revenue_this_month', 60, function () use ($now) {
            return PurchaseOrder::where('status', PurchaseOrder::STATUS_GOAL)
                ->whereMonth('created_at', $now->month)
                ->whereYear('created_at', $now->year)
                ->sum('grand_total');
        });

        $revenueLastMonth = PurchaseOrder::where('status', PurchaseOrder::STATUS_GOAL)
            ->whereMonth('created_at', $now->copy()->subMonth()->month)
            ->whereYear('created_at', $now->copy()->subMonth()->year)
            ->sum('grand_total');

        $revenueGrowth = $revenueLastMonth > 0
            ? round((($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth) * 100, 1)
            : ($revenueThisMonth > 0 ? 100 : 0);

        // ── Monthly Revenue Chart (12 bulan terakhir) ──
        $monthlyRevenue = Cache::remember('analytics:monthly_revenue', 300, function () use ($now) {
            $rows = PurchaseOrder::where('status', PurchaseOrder::STATUS_GOAL)
                ->where('created_at', '>=', $now->copy()->subMonths(11)->startOfMonth())
                ->select(
                    $this->rawYear('created_at'),
                    $this->rawMonth('created_at'),
                    DB::raw('SUM(grand_total) as total')
                )
                ->groupBy('year', 'month')
                ->orderBy('year')->orderBy('month')
                ->get();

            return $this->buildMonthlySeries(12)
                ->map(function ($month) use ($rows) {
                    $match = $rows->first(function ($row) use ($month) {
                        return $row->year == $month['year'] && $row->month == $month['month'];
                    });

                    return (object) [
                        'year' => $month['year'],
                        'month' => $month['month'],
                        'total' => $match->total ?? 0,
                    ];
                });
        });

        $avgMonthlyRevenue = round($monthlyRevenue->avg('total'));
        $peakMonth = $monthlyRevenue->sortByDesc('total')->first();
        $peakMonthLabel = $peakMonth && $peakMonth->total > 0
            ? $this->monthLabel($peakMonth->month) . ' ' . $peakMonth->year
            : 'N/A';

        // ── Top 5 Sales Terbaik (All-time) ──
        $topSales = User::whereIn('role', ['Sales', 'Sales Marketing'])
            ->where('status', 'Active')
            ->with(['purchaseOrders' => function ($q) {
                $q->where('status', PurchaseOrder::STATUS_GOAL);
            }])
            ->get()
            ->map(function ($s) use ($now) {
                $allTimeRevenue = $s->purchaseOrders->sum('grand_total');
                // Current month revenue untuk percentage
                $currentMonthRevenue = $s->purchaseOrders
                    ->filter(function ($po) use ($now) {
                        return $po->created_at->month == $now->month && $po->created_at->year == $now->year;
                    })
                    ->sum('grand_total');
                $target   = $s->currentMonthTarget()?->target_amount ?? $s->monthly_target ?? 0;
                $pct      = $target > 0 ? min(round(($currentMonthRevenue / $target) * 100, 1), 100) : 0;
                return [
                    'name'       => $s->name,
                    'achieved'   => $allTimeRevenue,
                    'target'     => $target,
                    'percentage' => $pct,
                    'po_count'   => $s->purchaseOrders->count(),
                    'status'     => $s->status,
                ];
            })
            ->sortByDesc('achieved')
            ->take(5)
            ->values();

        // ── Top 5 Customers berdasarkan Grand Total ──
        $topCustomers = Customer::with(['purchaseOrders' => function ($q) {
                $q->where('status', PurchaseOrder::STATUS_GOAL);
            }])
            ->get()
            ->map(function ($c) {
                return [
                    'name'    => $c->company_name,
                    'total'   => $c->purchaseOrders->sum('grand_total'),
                    'po_count' => $c->purchaseOrders->count(),
                ];
            })
            ->sortByDesc('total')
            ->take(5)
            ->values();

        // ── Sales Performance bulan ini ──
        $salesPerformance = User::whereIn('role', ['Sales', 'Sales Marketing'])
            ->where('status', 'Active')
            ->with(['purchaseOrders' => function ($q) use ($now) {
                $q->where('status', PurchaseOrder::STATUS_GOAL)
                  ->whereMonth('created_at', $now->month)
                  ->whereYear('created_at', $now->year);
            }])
            ->get()
            ->map(function ($s) {
                $achieved = $s->purchaseOrders->sum('grand_total');
                $target   = $s->currentMonthTarget()?->target_amount ?? $s->monthly_target ?? 0;
                $pct      = $target > 0 ? min(round(($achieved / $target) * 100, 1), 100) : 0;
                return [
                    'name'       => $s->name,
                    'achieved'   => $achieved,
                    'target'     => $target,
                    'percentage' => $pct,
                ];
            })
            ->sortByDesc('achieved')
            ->values();

        return view('analytics.index', compact(
            'totalRevenue', 'totalCompletedPO', 'aov',
            'totalCustomers', 'activeCustomers', 'pendingReviewCount',
            'revenueThisMonth', 'revenueLastMonth', 'revenueGrowth',
            'monthlyRevenue', 'avgMonthlyRevenue', 'peakMonthLabel',
            'topSales', 'topCustomers',
            'salesPerformance',
        ));
    }
}
