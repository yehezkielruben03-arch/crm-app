<?php

namespace App\Services;

use Smalot\PdfParser\Parser;

class PdfItemParser
{
    private const UNITS = [
        'pcs', 'unit', 'set', 'paket', 'box', 'kg', 'meter', 'm',
        'liter', 'buah', 'lembar', 'eksemplar', 'item', 'lot',
        'batch', 'pack', 'lusin', 'kodi', 'rim', 'ton', 'kuintal',
        'sak', 'botol', 'tube', 'roll', 'sheet',
    ];

    public function parse(string $filePath): array
    {
        if (!file_exists($filePath)) {
            return [];
        }

        try {
            $parser = new Parser();
            $pdf    = $parser->parseFile($filePath);
            $text   = $pdf->getText();
        } catch (\Exception $e) {
            return [];
        }

        $hasTable = preg_match('/\bN[Oo]\.?\b/i', $text) && preg_match('/\bTotal\b/i', $text);

        return $hasTable
            ? $this->parseTableFormat($text)
            : $this->parseLineByLine($text);
    }

    private function parseTableFormat(string $text): array
    {
        $lines = $this->getCleanLines($text);

        $items      = [];
        $inTable    = false;
        $pastHeader = false;

        foreach ($lines as $line) {
            if (preg_match('/\bN[Oo]\.?\b/i', $line)) {
                $inTable = true;
                continue;
            }

            if (!$inTable) continue;

            if (preg_match('/\bTotal\b/i', $line)) break;
            if (preg_match('/^[\s\-\_\.\=]+$/', $line)) {
                if ($pastHeader) break;
                $pastHeader = true;
                continue;
            }

            $item = $this->extractTableRow($line);
            if (!$item) $item = $this->extractItemSmart($line);
            if ($item) $items[] = $item;
        }

        return $items;
    }

    private function parseLineByLine(string $text): array
    {
        $lines = $this->getCleanLines($text);

        $items = [];
        $buffer = '';

        foreach ($lines as $line) {
            if (!preg_match('/\d/', $line) || strlen($line) < 5) continue;

            $buffer = ($buffer ? $buffer . ' ' : '') . $line;

            $item = $this->extractItemSmart($buffer);
            if ($item) {
                $items[] = $item;
                $buffer = '';
            }
        }

        return $items;
    }

    private function extractItemSmart(string $text): ?array
    {
        $text = preg_replace('/\b(Rp|Rp\.|IDR|\$|EUR|USD)\b/i', '', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        $text = str_replace(',', '', $text);

        preg_match_all('/\b\d+(?:\.\d+)?\b/', $text, $m, PREG_OFFSET_CAPTURE);
        $matches = $m[0];
        if (count($matches) < 2) return null;

        $unitWord  = $this->findUnitWord($text);
        $unitIdx   = $unitWord ? strpos($text, $unitWord) : false;

        $qty        = null;
        $unitPrice  = null;
        $qtyNumStr  = null;
        $priceNumStr = null;

        if ($unitWord && $unitIdx !== false) {
            $numsBefore = [];
            $numsAfter  = [];

            foreach ($matches as $match) {
                $endPos = $match[1] + strlen($match[0]);

                if ($endPos <= $unitIdx) {
                    $numsBefore[] = $match;
                } else {
                    $numsAfter[]  = $match;
                }
            }

            if (!empty($numsBefore)) {
                $candidates = array_filter($numsBefore, fn($m) => (float) $m[0] >= 1 && (float) $m[0] <= 99999);
                if (!empty($candidates)) {
                    $last = end($candidates);
                    $qty       = (float) $last[0];
                    $qtyNumStr = $last[0];
                }
            }

            if ($qty === null && !empty($numsBefore)) {
                $last = end($numsBefore);
                $qty       = (float) $last[0];
                $qtyNumStr = $last[0];
            }

            $priceCandidates = array_filter($numsAfter, fn($m) => (float) $m[0] > ($qty ?? 0));
            if (!empty($priceCandidates)) {
                $first = reset($priceCandidates);
                $unitPrice   = (float) $first[0];
                $priceNumStr = $first[0];
            }
        }

        if ($qty === null || $unitPrice === null) {
            $vals = array_map(fn($m) => (float) $m[0], $matches);
            $firstNum = $matches[0][0];
            $lastNum  = $matches[count($matches) - 1][0];
            $fv = (float) $firstNum;
            $lv = (float) $lastNum;

            if ($fv >= 1 && $fv <= 99999 && $lv > $fv && $lv >= 1000) {
                $qty         = $fv;
                $unitPrice   = $lv;
                $qtyNumStr   = $firstNum;
                $priceNumStr = $lastNum;
            } elseif ($lv <= 99999 && $fv > $lv) {
                $qty         = $lv;
                $unitPrice   = $fv;
                $qtyNumStr   = $lastNum;
                $priceNumStr = $firstNum;
            } else {
                $qty = min($fv, $lv);
                $unitPrice = max($fv, $lv);
                $qtyNumStr   = $qty == $fv ? $firstNum : $lastNum;
                $priceNumStr = $qty == $fv ? $lastNum : $firstNum;
            }
        }

        if ($qty <= 0 || $unitPrice <= 0) return null;

        $productName = $this->extractProductName($text, $qtyNumStr, $priceNumStr, $unitWord);

        return [
            'product_name' => $productName ?: 'Item',
            'qty'          => $qty,
            'unit'         => $unitWord ? strtolower($unitWord) : 'pcs',
            'unit_price'   => round($unitPrice, 2),
            'total_price'  => round($qty * $unitPrice, 2),
        ];
    }

    private function extractProductName(string $text, string $qtyStr, string $priceStr, ?string $unit): string
    {
        $result = $text;
        $result = preg_replace('/\b' . preg_quote($qtyStr, '/') . '\b/', '', $result);
        $result = preg_replace('/\b' . preg_quote($priceStr, '/') . '\b/', '', $result);
        if ($unit) {
            $result = preg_replace('/\b' . preg_quote($unit, '/') . '\b/i', '', $result);
        }
        $result = preg_replace('/\s+/', ' ', $result);
        $result = trim($result);
        $result = trim($result, '.,-:;()[] ');

        return $result ?: 'Item';
    }

    private function extractTableRow(string $line): ?array
    {
        $text = preg_replace('/\b(Rp|Rp\.|IDR|\$|EUR|USD)\b/i', '', $line);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        $text = str_replace(',', '', $text);

        preg_match_all('/\b\d+(?:\.\d+)?\b/', $text, $m, PREG_OFFSET_CAPTURE);
        $matches = $m[0];
        if (count($matches) < 2) return null;

        $unitWord = $this->findUnitWord($text);
        if (!$unitWord) return null;

        $unitIdx = strpos($text, $unitWord);
        if ($unitIdx === false) return null;

        $qty      = null;
        $qtyStr   = null;
        $qtyPos   = null;

        foreach ($matches as $match) {
            $endPos = $match[1] + strlen($match[0]);
            if ($endPos <= $unitIdx) {
                $val = (float) $match[0];
                if ($val >= 1 && $val <= 99999) {
                    $qty    = $val;
                    $qtyStr = $match[0];
                    $qtyPos = $match[1];
                }
            }
        }

        if ($qty === null) return null;

        $unitPrice = null;
        $priceStr  = null;

        foreach ($matches as $match) {
            if ($match[1] > $unitIdx + strlen($unitWord)) {
                $unitPrice = (float) $match[0];
                $priceStr  = $match[0];
                break;
            }
        }

        if ($unitPrice === null) return null;

        $beforeQty = trim(substr($text, 0, $qtyPos));

        $productName = null;
        if (preg_match('/^(\d{1,4})\s+(.+)$/', $beforeQty, $pn)) {
            $lineNo = (float) $pn[1];
            if ($lineNo >= 1 && $lineNo <= 999 && $lineNo != $qty) {
                $productName = $pn[2];
            }
        }

        if ($productName === null) {
            $productName = $beforeQty;
        }

        $productName = preg_replace('/\b' . preg_quote($unitWord, '/') . '\b/i', '', $productName);
        $productName = preg_replace('/\s+/', ' ', $productName);
        $productName = trim($productName);
        $productName = trim($productName, '.,-:;()[] ');

        return [
            'product_name' => $productName ?: 'Item',
            'qty'          => $qty,
            'unit'         => strtolower($unitWord),
            'unit_price'   => round($unitPrice, 2),
            'total_price'  => round($qty * $unitPrice, 2),
        ];
    }

    private function findUnitWord(string $text): ?string
    {
        $all = self::UNITS;
        $variants = [];
        foreach ($all as $u) {
            $variants[] = $u;
            $variants[] = ucfirst($u);
            $variants[] = strtoupper($u);
        }

        usort($variants, fn($a, $b) => strlen($b) - strlen($a));

        foreach ($variants as $v) {
            if (preg_match('/\b' . preg_quote($v, '/') . '\b/', $text, $m)) {
                return $m[0];
            }
        }

        return null;
    }

    private function getCleanLines(string $text): array
    {
        $lines = array_filter(explode("\n", $text), fn($l) => trim($l) !== '');
        return array_values(array_map(function ($l) {
            $l = preg_replace('/[^\w\s\.\,\-\/\(\)]/', '', $l);
            $l = preg_replace('/\s+/', ' ', $l);
            return trim($l);
        }, $lines));
    }
}
