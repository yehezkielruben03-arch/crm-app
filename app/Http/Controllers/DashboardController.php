<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\PurchaseOrder;
use App\Models\Quotation;
use App\Models\Rfq;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user->isAdminOrAbove()) {
            return $this->adminDashboard($user);
        }

        return $this->salesDashboard($user);
    }

    private function rawMonth(string $column): \Illuminate\Contracts\Database\Query\Expression
    {
        return DB::getDriverName() === 'sqlite'
            ? DB::raw("CAST(strftime('%m', {$column}) AS INTEGER) as month")
            : DB::raw("MONTH({$column}) as month");
    }

    private function rawYear(string $column): \Illuminate\Contracts\Database\Query\Expression
    {
        return DB::getDriverName() === 'sqlite'
            ? DB::raw("CAST(strftime('%Y', {$column}) AS INTEGER) as year")
            : DB::raw("YEAR({$column}) as year");
    }

    private function salesDashboard(User $user)
    {
        $now    = now();
        $userId = $user->id;

        // 1 query: semua stats customer sekaligus
        $customerStats = Cache::remember("dash:cust_stats:{$userId}", 60, function () use ($userId) {
            return Customer::where('sales_id', $userId)
                ->selectRaw('
                    COUNT(*) as total,
                    SUM(CASE WHEN status = \'Pending\' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = \'Active\' THEN 1 ELSE 0 END) as active
                ')
                ->first();
        });

        // Quotation stats
        $quoStats = Cache::remember("dash:quo_stats:{$userId}", 60, function () use ($userId) {
            return Quotation::where('sales_id', $userId)
                ->selectRaw("
                    COUNT(*) as total,
                    SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as sent
                ", [Quotation::STATUS_SENT])
                ->first();
        });

        // Customer Health (Inactive & Follow Up)
        $customerHealthStats = Cache::remember("dash:cust_health:{$userId}", 60, function () use ($userId, $now) {
            $twelveMonthsAgo = $now->copy()->subMonths(12);
            $threeMonthsAgo = $now->copy()->subMonths(3);

            $customers = Customer::where('sales_id', $userId)->where('status', 'Active')->get(['id', 'company_name', 'created_at']);
            
            $inactiveCount = 0;
            $followUpCount = 0;
            $inactiveList = collect();
            $followUpList = collect();

            if ($customers->isNotEmpty()) {
                $latestPurchaseOrders = PurchaseOrder::whereIn('customer_id', $customers->pluck('id'))
                    ->whereNotNull('po_date')
                    ->select('customer_id', DB::raw('MAX(po_date) as last_po_date'))
                    ->groupBy('customer_id')
                    ->pluck('last_po_date', 'customer_id');

                foreach ($customers as $c) {
                    $lastDate = isset($latestPurchaseOrders[$c->id])
                        ? \Carbon\Carbon::parse($latestPurchaseOrders[$c->id])
                        : $c->created_at;

                    if ($lastDate < $twelveMonthsAgo) {
                        $inactiveCount++;
                        $inactiveList->push((object)[
                            'company_name' => $c->company_name,
                            'last_po' => $lastDate,
                        ]);
                    } elseif ($lastDate < $threeMonthsAgo) {
                        $followUpCount++;
                        $followUpList->push((object)[
                            'company_name' => $c->company_name,
                            'last_po' => $lastDate,
                        ]);
                    }
                }
            }
            return (object)[
                'inactive' => $inactiveCount,
                'followUp' => $followUpCount,
                'inactiveList' => $inactiveList,
                'followUpList' => $followUpList
            ];
        });

        // 1 query: stats RFQ GOAL sekaligus
        $poStats = Cache::remember("dash:po_stats:{$userId}", 60, function () use ($userId) {
            return Rfq::where('sales_id', $userId)
                ->selectRaw("
                    COUNT(*) as total,
                    SUM(CASE WHEN status = '" . Rfq::STATUS_QUOTATION_CREATED . "' THEN 1 ELSE 0 END) as submitted,
                    SUM(CASE WHEN status = '" . Rfq::STATUS_GOAL . "' THEN 1 ELSE 0 END) as approved
                ")
                ->first();
        });

        // Revenue bulan ini (cached 60s)
        $revenueThisMonth = Cache::remember("dash:rev:{$userId}:{$now->format('Y-m')}", 60, function () use ($userId, $now) {
            return Rfq::where('sales_id', $userId)
                ->where('status', Rfq::STATUS_GOAL)
                ->whereMonth('rfq_date', $now->month)
                ->whereYear('rfq_date', $now->year)
                ->with('items')
                ->get()
                ->sum(function($rfq) {
                    return $rfq->items->sum(function($item) {
                        return $item->qty * $item->price_after_margin;
                    });
                });
        });

        // GOAL count this month
        $goalCountThisMonth = Cache::remember("dash:goal_cnt:{$userId}:{$now->format('Y-m')}", 60, function () use ($userId, $now) {
            return Rfq::where('sales_id', $userId)
                ->where('status', Rfq::STATUS_GOAL)
                ->whereMonth('rfq_date', $now->month)
                ->whereYear('rfq_date', $now->year)
                ->count();
        });

        // RFQ conversion and quotation pipeline metrics for Phase 2
        $rfqMonthStats = Cache::remember("dash:stats:{$userId}:{$now->format('Y-m')}", 60, function () use ($userId, $now) {
            return Rfq::where('sales_id', $userId)
                ->whereMonth('rfq_date', $now->month)
                ->whereYear('rfq_date', $now->year)
                ->selectRaw(
                    'COUNT(*) as total, ' .
                    'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as goal_count, ' .
                    'SUM(CASE WHEN status IN (?, ?) THEN 1 ELSE 0 END) as pending_quotation_count',
                    [Rfq::STATUS_GOAL, Rfq::STATUS_APPROVED, Rfq::STATUS_QUOTATION_CREATED]
                )
                ->first();
        });

        $rfqCreatedThisMonth = $rfqMonthStats->total ?? 0;
        $pendingQuotationCount = $rfqMonthStats->pending_quotation_count ?? 0;
        $conversionRateThisMonth = $rfqCreatedThisMonth > 0
            ? min(round(($goalCountThisMonth / $rfqCreatedThisMonth) * 100, 1), 100)
            : 0;
        $avgGoalValueThisMonth = $goalCountThisMonth > 0
            ? round($revenueThisMonth / $goalCountThisMonth)
            : 0;

        // Today's stats
        $todayNewCustomers = Customer::where('sales_id', $userId)
            ->whereDate('created_at', $now->today())
            ->count();
        $todayNewRFQ = Rfq::where('sales_id', $userId)
            ->whereDate('created_at', $now->today())
            ->count();

        // RFQ pipeline breakdown
        $rfqPipeline = Cache::remember("dash:pipeline:{$userId}", 60, function () use ($userId) {
            return Rfq::where('sales_id', $userId)
                ->selectRaw("
                    SUM(CASE WHEN status = '" . Rfq::STATUS_PENDING_ADMIN . "' THEN 1 ELSE 0 END) as pending_admin,
                    SUM(CASE WHEN status = '" . Rfq::STATUS_PENDING_LEADER . "' THEN 1 ELSE 0 END) as pending_leader,
                    SUM(CASE WHEN status = '" . Rfq::STATUS_APPROVED . "' THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN status = '" . Rfq::STATUS_QUOTATION_CREATED . "' THEN 1 ELSE 0 END) as quotation_created,
                    SUM(CASE WHEN status = '" . Rfq::STATUS_GOAL . "' THEN 1 ELSE 0 END) as goal,
                    SUM(CASE WHEN status = '" . Rfq::STATUS_CANCELLED . "' THEN 1 ELSE 0 END) as cancelled
                ")
                ->first();
        });

        $targetThisMonth = $user->currentMonthTarget()?->target_amount ?? $user->monthly_target ?? 0;
        $achievementPct  = $targetThisMonth > 0
            ? min(round(($revenueThisMonth / $targetThisMonth) * 100, 1), 100)
            : 0;

        // Grafik Penawaran vs Goal (6 bulan terakhir)
        $monthlyChart = Cache::remember("dash:chart:{$userId}", 300, function () use ($userId) {
            $rfqs = Rfq::where('sales_id', $userId)
                ->whereIn('status', [Rfq::STATUS_APPROVED, Rfq::STATUS_GOAL]) // Approved = Penawaran dikirim, GOAL = Goal
                ->where('rfq_date', '>=', now()->subMonths(5)->startOfMonth())
                ->with('items')
                ->get();
                
            $grouped = $rfqs->groupBy(function($r) {
                return $r->rfq_date->format('Y-m');
            });
            
            $chartData = collect();
            foreach ($grouped as $monthKey => $group) {
                $parts = explode('-', $monthKey);
                $totalGoal = $group->where('status', Rfq::STATUS_GOAL)->sum(function($rfq) {
                    return $rfq->items->sum(function($item) {
                        return $item->qty * $item->price_after_margin;
                    });
                });
                $totalPenawaran = $group->sum(function($rfq) { // Termasuk GOAL karena GOAL dulunya penawaran
                    return $rfq->items->sum(function($item) {
                        return $item->qty * $item->price_after_margin;
                    });
                });
                $chartData->push((object)[
                    'year' => (int)$parts[0],
                    'month' => (int)$parts[1],
                    'total_goal' => $totalGoal,
                    'total_penawaran' => $totalPenawaran
                ]);
            }
            return $chartData->sortBy('year')->sortBy('month')->values();
        });

        // Recent GOAL (cached 2 menit)
        $recentOrders = Cache::remember("dash:recent:{$userId}", 120, function () use ($userId) {
            return Rfq::where('sales_id', $userId)
                ->where('status', Rfq::STATUS_GOAL)
                ->with(['customer:id,company_name', 'items'])
                ->orderByDesc('rfq_date')
                ->take(5)
                ->get();
        });

        // Recent quotations for sales
        $recentQuotations = Cache::remember("dash:recent_quo:{$userId}", 120, function () use ($userId) {
            return Quotation::where('sales_id', $userId)
                ->with('customer:id,company_name')
                ->orderByDesc('created_at')
                ->take(5)
                ->get();
        });

        return view('dashboard', [
            'user'               => $user,
            'totalMyCustomers'   => $customerStats->total ?? 0,
            'pendingCustomers'   => $customerStats->pending ?? 0,
            'activeCustomers'    => $customerStats->active ?? 0,
            'customerHealthStats' => $customerHealthStats,
            'totalMyQuo'         => $quoStats->total ?? 0,
            'sentQuo'            => $quoStats->sent ?? 0,
            'totalMyPO'          => $poStats->total ?? 0,
            'submittedPO'        => $poStats->submitted ?? 0,
            'approvedPO'         => $poStats->approved ?? 0,
            'revenueThisMonth'   => $revenueThisMonth,
            'targetThisMonth'    => $targetThisMonth,
            'achievementPct'     => $achievementPct,
            'goalCountThisMonth' => $goalCountThisMonth,
            'todayNewCustomers'  => $todayNewCustomers,
            'todayNewRFQ'        => $todayNewRFQ,
            'rfqPipeline'        => $rfqPipeline,
            'monthlyChart'       => $monthlyChart,
            'recentOrders'           => $recentOrders,
            'recentQuotations'       => $recentQuotations,
            'rfqCreatedThisMonth'    => $rfqCreatedThisMonth,
            'pendingQuotationCount'  => $pendingQuotationCount,
            'conversionRateThisMonth'=> $conversionRateThisMonth,
            'avgGoalValueThisMonth'  => $avgGoalValueThisMonth,
            'isDashboardSales'       => true,
        ]);
    }

    private function buildPriorityRecommendations(array $actionQueueSummary): array
    {
        $pendingCustomers = (int) ($actionQueueSummary['pending_customers'] ?? 0);
        $pendingPos = (int) ($actionQueueSummary['pending_pos'] ?? 0);
        $pendingRfqs = (int) ($actionQueueSummary['pending_rfqs'] ?? 0);

        $recommendations = collect([
            [
                'title' => 'Review customer approval',
                'description' => 'Customer yang masih menunggu approval perlu diproses lebih dulu agar pipeline tidak menumpuk.',
                'priority' => 'high',
                'score' => 100 + ($pendingCustomers * 10),
                'count' => $pendingCustomers,
            ],
            [
                'title' => 'Review PO pending',
                'description' => 'PO yang sedang Pending atau Revisi perlu ditangani supaya transaksi tidak berhenti di tengah.',
                'priority' => 'high',
                'score' => 90 + ($pendingPos * 8),
                'count' => $pendingPos,
            ],
            [
                'title' => 'Proses RFQ pending',
                'description' => 'RFQ yang masih menunggu review admin atau leader perlu dipercepat agar sales tetap bergerak.',
                'priority' => 'medium',
                'score' => 80 + ($pendingRfqs * 6),
                'count' => $pendingRfqs,
            ],
        ])
            ->filter(fn ($item) => $item['count'] > 0)
            ->sortByDesc('score')
            ->values()
            ->all();

        if (empty($recommendations)) {
            return [[
                'title' => 'Jaga momentum pipeline',
                'description' => 'Tidak ada item review saat ini. Fokus pada follow-up pelanggan aktif dan pertumbuhan bisnis.',
                'priority' => 'low',
                'score' => 0,
                'count' => 0,
            ]];
        }

        return array_slice($recommendations, 0, 3);
    }

    private function buildHighValueOpportunities(): array
    {
        $pendingCustomers = Customer::whereIn('status', Customer::pendingApprovalStatuses())
            ->with('purchaseOrders')
            ->get();

        $opportunities = $pendingCustomers->map(function (Customer $customer) {
            $poValue = $customer->purchaseOrders
                ->whereIn('status', [PurchaseOrder::STATUS_PENDING, PurchaseOrder::STATUS_REVISI])
                ->sum('grand_total');

            $score = 60 + ($customer->purchaseOrders->count() * 10) + (int) ($poValue / 100000);

            return [
                'company_name' => $customer->company_name,
                'value' => $poValue,
                'score' => $score,
                'label' => $poValue > 0 ? 'PO menunggu review' : 'Review customer approval',
            ];
        })
            ->filter(fn ($item) => $item['score'] > 0)
            ->sortByDesc('score')
            ->values()
            ->all();

        return array_slice($opportunities, 0, 3);
    }

    private function buildNextBestActions(array $actionQueueSummary): array
    {
        $pendingCustomers = (int) ($actionQueueSummary['pending_customers'] ?? 0);
        $pendingPos = (int) ($actionQueueSummary['pending_pos'] ?? 0);
        $pendingRfqs = (int) ($actionQueueSummary['pending_rfqs'] ?? 0);

        $customerUrgency = max(0, $pendingCustomers * 25);
        $poUrgency = max(0, $pendingPos * 20);
        $rfqUrgency = max(0, $pendingRfqs * 15);

        $actions = collect([
            [
                'title' => 'Review customer approval',
                'description' => 'Customer dengan status menunggu approval sebaiknya diproses terlebih dahulu untuk mencegah backlog tumbuh.',
                'score' => $customerUrgency + 40,
                'type' => 'customer',
            ],
            [
                'title' => 'Review PO pending',
                'description' => 'PO yang pending atau revisi biasanya punya nilai transaksi yang sudah siap, jadi cocok diprioritaskan.',
                'score' => $poUrgency + 35,
                'type' => 'po',
            ],
            [
                'title' => 'Proses RFQ pending',
                'description' => 'RFQ yang masih menunggu review membantu sales melanjutkan pipeline dengan lebih cepat.',
                'score' => $rfqUrgency + 20,
                'type' => 'rfq',
            ],
        ])
            ->filter(fn ($item) => $item['score'] > 0)
            ->sortByDesc('score')
            ->values()
            ->all();

        return array_slice($actions, 0, 3);
    }

    private function adminDashboard(User $user)
    {
        $now = now();

        // Quotation global stats
        $quoGlobal = Cache::remember('dash:admin:quo_global', 60, function () use ($now) {
            $total = Quotation::count();
            $sent = Quotation::where('status', Quotation::STATUS_SENT)->count();
            $sentThisMonth = Quotation::where('status', Quotation::STATUS_SENT)
                ->whereMonth('created_at', $now->month)
                ->whereYear('created_at', $now->year)
                ->count();
            return compact('total', 'sent', 'sentThisMonth');
        });

        // 1 query: stats global customer + RFQ + PO
        $globalStats = Cache::remember('dash:admin:global', 60, function () {
            $cust = Customer::selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = \'Pending\' THEN 1 ELSE 0 END) as pending
            ')->first();

            $rfq = Rfq::selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = '" . Rfq::STATUS_PENDING_ADMIN . "' THEN 1 ELSE 0 END) as pending_admin,
                SUM(CASE WHEN status = '" . Rfq::STATUS_PENDING_LEADER . "' THEN 1 ELSE 0 END) as pending_leader
            ")->first();

            $po = PurchaseOrder::selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending
            ")->first();

            return [
                'total_customers'    => $cust->total ?? 0,
                'pending_approvals'  => $cust->pending ?? 0,
                'total_rfq_pending'  => ($rfq->pending_admin ?? 0) + ($rfq->pending_leader ?? 0),
                'rfq_pending_admin'  => $rfq->pending_admin ?? 0,
                'rfq_pending_leader' => $rfq->pending_leader ?? 0,
                'total_po'           => $po->total ?? 0,
                'po_pending'         => $po->pending ?? 0,
            ];
        });

        // Total RFQ (all time)
        $totalRFQ = Cache::remember('dash:admin:total_rfq', 60, function () {
            return Rfq::count();
        });

        $totalRfqItems = Cache::remember('dash:admin:total_rfq_items', 60, function () {
            return \App\Models\RfqItem::count();
        });

        $actionQueueSummary = Cache::remember('dash:admin:action_queue', 60, function () use ($now) {
            $pendingCustomers = Customer::whereIn('status', Customer::pendingApprovalStatuses())->count();
            $pendingPOs = PurchaseOrder::whereIn('status', [PurchaseOrder::STATUS_PENDING, PurchaseOrder::STATUS_REVISI])->count();
            $pendingRfqs = Rfq::whereIn('status', [Rfq::STATUS_PENDING_ADMIN, Rfq::STATUS_PENDING_LEADER])->count();

            return [
                'pending_customers' => $pendingCustomers,
                'pending_pos' => $pendingPOs,
                'pending_rfqs' => $pendingRfqs,
                'total' => $pendingCustomers + $pendingPOs + $pendingRfqs,
            ];
        });

        $priorityRecommendations = $this->buildPriorityRecommendations($actionQueueSummary);
        $highValueOpportunities = $this->buildHighValueOpportunities();
        $nextBestActions = $this->buildNextBestActions($actionQueueSummary);

        // Goal count this month
        $goalCountThisMonth = Cache::remember("dash:admin:goal_cnt:{$now->format('Y-m')}", 60, function () use ($now) {
            return Rfq::where('status', Rfq::STATUS_GOAL)
                ->whereMonth('rfq_date', $now->month)
                ->whereYear('rfq_date', $now->year)
                ->count();
        });

        // Today's activity
        $todayNewCustomers = Customer::whereDate('created_at', $now->today())->count();
        $todayNewRFQ = Rfq::whereDate('created_at', $now->today())->count();

        // RFQ pipeline (admin sees all sales)
        $rfqPipeline = Cache::remember("dash:admin:pipeline", 60, function () {
            return Rfq::selectRaw("
                SUM(CASE WHEN status = '" . Rfq::STATUS_PENDING_ADMIN . "' THEN 1 ELSE 0 END) as pending_admin,
                SUM(CASE WHEN status = '" . Rfq::STATUS_PENDING_LEADER . "' THEN 1 ELSE 0 END) as pending_leader,
                SUM(CASE WHEN status = '" . Rfq::STATUS_APPROVED . "' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = '" . Rfq::STATUS_QUOTATION_CREATED . "' THEN 1 ELSE 0 END) as quotation_created,
                SUM(CASE WHEN status = '" . Rfq::STATUS_GOAL . "' THEN 1 ELSE 0 END) as goal,
                SUM(CASE WHEN status = '" . Rfq::STATUS_CANCELLED . "' THEN 1 ELSE 0 END) as cancelled
            ")
            ->first();
        });

        // Conversion rate: GOAL / Total RFQ (non-cancelled)
        $conversionRate = Cache::remember('dash:admin:conversion', 120, function () {
            $total = Rfq::whereIn('status', [
                Rfq::STATUS_PENDING_ADMIN,
                Rfq::STATUS_PENDING_LEADER,
                Rfq::STATUS_APPROVED,
                Rfq::STATUS_QUOTATION_CREATED,
                Rfq::STATUS_GOAL,
            ])->count();
            $goal = Rfq::where('status', Rfq::STATUS_GOAL)->count();
            return $total > 0 ? round(($goal / $total) * 100, 1) : 0;
        });

        // Revenue bulan ini
        $revenueThisMonth = Cache::remember("dash:admin:rev:{$now->format('Y-m')}", 60, function () use ($now) {
            return Rfq::where('status', Rfq::STATUS_GOAL)
                ->whereMonth('rfq_date', $now->month)
                ->whereYear('rfq_date', $now->year)
                ->with('items')
                ->get()
                ->sum(function($rfq) {
                    return $rfq->items->sum(function($item) {
                        return $item->qty * $item->price_after_margin;
                    });
                });
        });

        // Revenue chart 6 bulan
        $monthlyRevenue = Cache::remember('dash:admin:chart', 300, function () {
            $rfqs = Rfq::where('status', Rfq::STATUS_GOAL)
                ->where('rfq_date', '>=', now()->subMonths(5)->startOfMonth())
                ->with('items')
                ->get();
                
            $grouped = $rfqs->groupBy(function($r) {
                return $r->rfq_date->format('Y-m');
            });
            
            $chartData = collect();
            foreach ($grouped as $monthKey => $group) {
                $parts = explode('-', $monthKey);
                $total = $group->sum(function($rfq) {
                    return $rfq->items->sum(function($item) {
                        return $item->qty * $item->price_after_margin;
                    });
                });
                $chartData->push((object)[
                    'year' => (int)$parts[0],
                    'month' => (int)$parts[1],
                    'total' => $total
                ]);
            }
            return $chartData->sortBy('year')->sortBy('month')->values();
        });

        // Sales performance
        $salesPerformance = Cache::remember("dash:admin:perf:{$now->format('Y-m')}", 120, function () use ($now) {
            return User::whereIn('role', ['Sales', 'Sales Marketing'])
                ->where('status', 'Active')
                ->with(['rfqs' => function ($q) use ($now) {
                    $q->where('status', Rfq::STATUS_GOAL)
                      ->whereMonth('rfq_date', $now->month)
                      ->whereYear('rfq_date', $now->year)
                      ->with('items');
                }, 'salesTargets' => function ($q) use ($now) {
                    $q->where('target_month', $now->month)
                      ->where('target_year', $now->year);
                }])
                ->get()
                ->map(function ($s) use ($now) {
                    $achieved = $s->rfqs->sum(function($rfq) {
                        return $rfq->items->sum(function($item) {
                            return $item->qty * $item->price_after_margin;
                        });
                    });
                    $target   = $s->salesTargets->first()?->target_amount ?? $s->monthly_target ?? 0;
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
        });

        // Recent quotations
        $recentQuotations = Cache::remember('dash:admin:recent_quo', 120, function () {
            return Quotation::with(['customer:id,company_name', 'sales:id,name'])
                ->orderByDesc('created_at')
                ->take(5)
                ->get();
        });

        // Recent PO (Now GOAL)
        $recentOrders = Cache::remember('dash:admin:recent', 120, function () {
            return Rfq::with(['customer:id,company_name', 'sales:id,name', 'items'])
                ->where('status', Rfq::STATUS_GOAL)
                ->orderByDesc('rfq_date')
                ->take(5)
                ->get();
        });

        return view('dashboard', [
            'user'               => $user,
            'totalCustomers'     => $globalStats['total_customers'],
            'pendingApprovals'   => $globalStats['pending_approvals'],
            'totalRfqPending'    => $globalStats['total_rfq_pending'],
            'rfqPendingAdmin'    => $globalStats['rfq_pending_admin'],
            'rfqPendingLeader'   => $globalStats['rfq_pending_leader'],
            'totalPO'            => $globalStats['total_po'],
            'poPending'          => $globalStats['po_pending'],
            'totalQuo'           => $quoGlobal['total'],
            'sentQuo'            => $quoGlobal['sent'],
            'sentQuoThisMonth'   => $quoGlobal['sentThisMonth'],
            'revenueThisMonth'   => $revenueThisMonth,
            'totalRFQ'           => $totalRFQ,
            'totalRfqItems'      => $totalRfqItems,
            'goalCountThisMonth' => $goalCountThisMonth,
            'todayNewCustomers'  => $todayNewCustomers,
            'todayNewRFQ'        => $todayNewRFQ,
            'rfqPipeline'        => $rfqPipeline,
            'conversionRate'     => $conversionRate,
            'monthlyRevenue'     => $monthlyRevenue,
            'salesPerformance'   => $salesPerformance,
            'recentOrders'       => $recentOrders,
            'recentQuotations'   => $recentQuotations,
            'actionQueueSummary' => $actionQueueSummary,
            'priorityRecommendations' => $priorityRecommendations,
            'highValueOpportunities' => $highValueOpportunities,
            'nextBestActions' => $nextBestActions,
            'isDashboardSales'   => false,
        ]);
    }
}
