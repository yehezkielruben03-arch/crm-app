<?php

namespace App\Helpers;

/**
 * Helper class for CRM related utilities.
 */
class CrmHelper
{
    /**
     * Format an integer amount to Indonesian Rupiah currency format.
     *
     * Example:
     *   formatRupiah(1500000) => "Rp 1.500.000"
     *
     * @param int $amount The amount in integer (no decimal part).
     * @return string Formatted Rupiah string.
     */
    public static function formatRupiah(int $amount): string
    {
        // number_format: number, decimals, decimal_separator, thousands_separator
        // We don't need decimal part, so set decimals to 0.
        // Use '.' as thousands separator and ',' as decimal separator (unused).
        $formatted = number_format($amount, 0, ',', '.');
        return 'Rp ' . $formatted;
    }
}
