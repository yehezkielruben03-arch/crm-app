<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\Rfq;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class MonthlyPoReportService
{
    public const MONTHS_ID = [
        1  => 'Januari',
        2  => 'Februari',
        3  => 'Maret',
        4  => 'April',
        5  => 'Mei',
        6  => 'Juni',
        7  => 'Juli',
        8  => 'Agustus',
        9  => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    public static function monthName(int $month): string
    {
        return self::MONTHS_ID[$month] ?? (string) $month;
    }

    /**
     * Ambil seluruh baris laporan PO GOAL (dan standalone RFQ GOAL jika ada) untuk periode bulan & tahun tertentu.
     * Logika filter & perhitungan DPP diselaraskan 100% dengan DashboardController & AuditRevenueCommand.
     *
     * @return Collection<int, array{
     *     sort_key: string,
     *     date_display: string,
     *     sales_id: int|null,
     *     sales_name: string,
     *     customer_name: string,
     *     item_summary: string,
     *     exclude_price: float
     * }>
     */
    public function getMonthlyRows(int $month, int $year, ?int $salesId = null): Collection
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth()->startOfDay();
        $endDate   = $startDate->copy()->endOfMonth()->endOfDay();

        $poQuery = PurchaseOrder::with([
            'customer',
            'sales',
            'items',
            'rfq.sales',
            'rfq.items.components',
        ])
            ->whereIn('status', [PurchaseOrder::STATUS_GOAL, 'Goal', 'goal', 'Approved', 'approved'])
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('po_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                    ->orWhereBetween('approved_at', [$startDate, $endDate])
                    ->orWhere(function ($fallbackQ) use ($startDate, $endDate) {
                        $fallbackQ->whereNull('po_date')
                            ->whereNull('approved_at')
                            ->whereBetween('updated_at', [$startDate, $endDate]);
                    });
            });

        if ($salesId !== null) {
            $poQuery->where(function ($sq) use ($salesId) {
                $sq->where('sales_id', $salesId)
                    ->orWhereHas('rfq', fn ($rq) => $rq->where('sales_id', $salesId));
            });
        }

        $pos = $poQuery->orderBy('po_date', 'asc')->orderBy('id', 'asc')->get();

        $poRfqIds = $pos->pluck('rfq_id')->filter()->unique();
        $rfqQuery = Rfq::with(['customer', 'sales', 'items.components'])
            ->whereIn('status', [Rfq::STATUS_GOAL, 'Goal', 'goal', 'GOAL'])
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->whereNotIn('id', $poRfqIds);

        if ($salesId !== null) {
            $rfqQuery->where('sales_id', $salesId);
        }

        $standaloneRfqs = $rfqQuery->orderBy('updated_at', 'asc')->orderBy('id', 'asc')->get();

        $rows = collect();

        foreach ($pos as $po) {
            $date = $this->resolvePoDate($po);
            $resolvedSalesId = $po->sales_id ?? $po->rfq?->sales_id;
            $salesName = $po->sales?->name ?? $po->rfq?->sales?->name ?? $po->rfq?->sales_name ?? 'Tanpa Sales';
            $customerName = $po->customer?->company_name ?? $po->rfq?->customer_name ?? '-';

            $rows->push([
                'sort_key'      => $date->format('Y-m-d') . '_po_' . str_pad((string) $po->id, 10, '0', STR_PAD_LEFT),
                'date_display'  => $date->format('d/m/Y'),
                'sales_id'      => $resolvedSalesId ? (int) $resolvedSalesId : null,
                'sales_name'    => trim($salesName) !== '' ? trim($salesName) : 'Tanpa Sales',
                'customer_name' => trim($customerName) !== '' ? trim($customerName) : '-',
                'item_summary'  => $this->buildPoItemSummary($po),
                'exclude_price' => (float) $po->effective_dpp,
            ]);
        }

        foreach ($standaloneRfqs as $rfq) {
            $date = $rfq->updated_at ? Carbon::parse($rfq->updated_at) : Carbon::parse($rfq->rfq_date ?? now());
            $salesName = $rfq->sales?->name ?? $rfq->sales_name ?? 'Tanpa Sales';
            $customerName = $rfq->customer?->company_name ?? $rfq->customer_name ?? '-';

            $rows->push([
                'sort_key'      => $date->format('Y-m-d') . '_rfq_' . str_pad((string) $rfq->id, 10, '0', STR_PAD_LEFT),
                'date_display'  => $date->format('d/m/Y'),
                'sales_id'      => $rfq->sales_id ? (int) $rfq->sales_id : null,
                'sales_name'    => trim($salesName) !== '' ? trim($salesName) : 'Tanpa Sales',
                'customer_name' => trim($customerName) !== '' ? trim($customerName) : '-',
                'item_summary'  => $this->buildRfqItemSummary($rfq),
                'exclude_price' => (float) $rfq->grand_total,
            ]);
        }

        return $rows->sortBy('sort_key')->values();
    }

    /**
     * Kelompokkan baris per Sales untuk kebutuhan export Multi-Sheet (Admin / Leader / Super Admin).
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return Collection<int, array{sales_id: int|null, sales_name: string, rows: Collection}>
     */
    public function groupRowsBySales(Collection $rows): Collection
    {
        return $rows
            ->groupBy(fn (array $row) => $row['sales_id'] !== null ? 'id_' . $row['sales_id'] : 'name_' . $row['sales_name'])
            ->map(function (Collection $group) {
                $first = $group->first();
                return [
                    'sales_id'   => $first['sales_id'],
                    'sales_name' => $first['sales_name'],
                    'rows'       => $group->values(),
                ];
            })
            ->sortBy(fn (array $entry) => mb_strtolower($entry['sales_name']))
            ->values();
    }

    /**
     * Daftar tahun yang tersedia untuk dropdown filter export.
     *
     * @return array<int, int>
     */
    public function getAvailableYears(): array
    {
        $currentYear = (int) now()->year;
        $minPoDate = PurchaseOrder::whereNotNull('po_date')->min('po_date');
        $minYear = $minPoDate ? (int) Carbon::parse($minPoDate)->year : $currentYear;
        $startYear = min($minYear, $currentYear);
        $endYear = max($currentYear, $minYear);

        $years = [];
        for ($y = $endYear; $y >= $startYear; $y--) {
            $years[] = $y;
        }

        return $years;
    }

    /**
     * Daftar sales aktif untuk dropdown filter Admin/Leader/Super Admin.
     *
     * @return Collection<int, User>
     */
    public function getActiveSalesList(): Collection
    {
        return User::whereIn('role', ['Sales', 'Sales Marketing'])
            ->where('status', 'Active')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function resolvePoDate(PurchaseOrder $po): Carbon
    {
        if ($po->po_date) {
            return Carbon::parse($po->po_date);
        }
        if ($po->approved_at) {
            return Carbon::parse($po->approved_at);
        }

        return Carbon::parse($po->updated_at ?? $po->created_at ?? now());
    }

    /**
     * Susun ringkasan Nama Item 1 baris per PO, dipisahkan dengan " + ".
     * Jika RFQ memakai bundling/PC rakitan, selalu ambil item induk dari RFQ agar komponen anak tidak pernah bocor.
     */
    private function buildPoItemSummary(PurchaseOrder $po): string
    {
        $hasBundledRfq = $po->rfq
            && $po->rfq->items
            && $po->rfq->items->contains(fn ($item) => !empty($item->parent_id));

        if ($hasBundledRfq) {
            $rfqSummary = $this->buildRfqItemSummary($po->rfq);
            if ($rfqSummary !== '-') {
                return $rfqSummary;
            }
        }

        if ($po->items && $po->items->isNotEmpty()) {
            $names = $po->items
                ->map(fn ($item) => trim((string) ($item->item_name ?? '')))
                ->filter(fn (string $name) => $name !== '')
                ->values();

            if ($names->isNotEmpty()) {
                return $names->implode(' + ');
            }
        }

        if ($po->rfq && $po->rfq->items && $po->rfq->items->isNotEmpty()) {
            return $this->buildRfqItemSummary($po->rfq);
        }

        return '-';
    }

    private function buildRfqItemSummary(Rfq $rfq): string
    {
        $quotationItems = $rfq->customer_quotation_items;

        if ($quotationItems->isNotEmpty()) {
            $names = $quotationItems
                ->map(fn ($item) => trim((string) ($item->product_name ?? '')))
                ->filter(fn (string $name) => $name !== '')
                ->values();

            if ($names->isNotEmpty()) {
                return $names->implode(' + ');
            }
        }

        $names = $rfq->items
            ->filter(fn ($item) => empty($item->parent_id))
            ->map(fn ($item) => trim((string) ($item->product_name ?? '')))
            ->filter(fn (string $name) => $name !== '')
            ->values();

        return $names->isNotEmpty() ? $names->implode(' + ') : '-';
    }
}
