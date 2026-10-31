<?php

namespace App\Traits;

use Carbon\Carbon;
use Illuminate\Http\Request;

trait HandlesDateRangeFilter
{
    /**
     * Resolves date range from query parameters or session.
     * Presets supported:
     * - 'this_workweek' : Senin - Jumat minggu ini
     * - 'this_week'     : Senin - Minggu minggu ini
     * - 'this_month'    : Tanggal 1 sampai akhir bulan ini
     * - 'last_month'    : Bulan kemarin
     * - 'today'         : Hari ini
     * - 'custom'        : Rentang tanggal bebas (start_date & end_date)
     *
     * @param Request $request
     * @param string $defaultPreset
     * @return array
     */
    protected function resolveDateRange(Request $request, string $defaultPreset = 'this_month'): array
    {
        Carbon::setLocale('id');
        $now = Carbon::now();

        // Check if user requested to reset date filter
        if ($request->has('reset_date')) {
            session()->forget([
                'filter_start_date',
                'filter_end_date',
                'filter_preset',
                'filter_label',
            ]);
            $preset = $defaultPreset;
            $rawStart = null;
            $rawEnd = null;
        } else {
            $preset = $request->query('preset');
            $rawStart = $request->query('start_date');
            $rawEnd = $request->query('end_date');

            // If not in query string, look in session
            if (!$preset && !$rawStart && !$rawEnd) {
                if (session()->has('filter_start_date') && session()->has('filter_end_date')) {
                    $rawStart = session('filter_start_date');
                    $rawEnd = session('filter_end_date');
                    $preset = session('filter_preset', 'custom');
                } else {
                    $preset = $defaultPreset;
                }
            }
        }

        if ($preset && in_array($preset, ['today', 'this_workweek', 'this_week', 'this_month', 'last_month'])) {
            switch ($preset) {
                case 'today':
                    $startDate = $now->copy()->startOfDay();
                    $endDate = $now->copy()->endOfDay();
                    $label = 'Hari Ini (' . $startDate->translatedFormat('d M Y') . ')';
                    break;

                case 'this_workweek':
                    // Senin s/d Jumat minggu berjalan
                    $startDate = $now->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
                    $endDate = $now->copy()->startOfWeek(Carbon::MONDAY)->addDays(4)->endOfDay();
                    $label = 'Senin – Jumat (' . $startDate->format('d') . '–' . $endDate->translatedFormat('d M Y') . ')';
                    break;

                case 'this_week':
                    // Senin s/d Minggu
                    $startDate = $now->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
                    $endDate = $now->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();
                    $label = 'Minggu Ini (' . $startDate->format('d') . '–' . $endDate->translatedFormat('d M Y') . ')';
                    break;

                case 'last_month':
                    $startDate = $now->copy()->subMonthNoOverflow()->startOfMonth()->startOfDay();
                    $endDate = $now->copy()->subMonthNoOverflow()->endOfMonth()->endOfDay();
                    $label = 'Bulan Lalu (' . $startDate->translatedFormat('M Y') . ')';
                    break;

                case 'this_month':
                default:
                    $startDate = $now->copy()->startOfMonth()->startOfDay();
                    $endDate = $now->copy()->endOfMonth()->endOfDay();
                    $label = $startDate->format('M 1') . ' – ' . $endDate->translatedFormat('M t, Y');
                    $preset = 'this_month';
                    break;
            }
        } else {
            // Custom Range
            try {
                $startDate = $rawStart ? Carbon::parse($rawStart)->startOfDay() : $now->copy()->startOfMonth()->startOfDay();
            } catch (\Exception $e) {
                $startDate = $now->copy()->startOfMonth()->startOfDay();
            }

            try {
                $endDate = $rawEnd ? Carbon::parse($rawEnd)->endOfDay() : $now->copy()->endOfMonth()->endOfDay();
            } catch (\Exception $e) {
                $endDate = $now->copy()->endOfMonth()->endOfDay();
            }

            if ($startDate->gt($endDate)) {
                $temp = $startDate;
                $startDate = $endDate->copy()->startOfDay();
                $endDate = $temp->copy()->endOfDay();
            }

            $preset = 'custom';
            $label = $startDate->format('d M') . ' – ' . $endDate->translatedFormat('d M Y');
        }

        // Simpan ke session agar konsisten antar navigasi halaman
        session([
            'filter_start_date' => $startDate->toDateString(),
            'filter_end_date'   => $endDate->toDateString(),
            'filter_preset'     => $preset,
            'filter_label'      => $label,
        ]);

        return [
            'startDate'  => $startDate,
            'endDate'    => $endDate,
            'preset'     => $preset,
            'label'      => $label,
            'isFiltered' => ($preset !== 'this_month'),
        ];
    }
}
