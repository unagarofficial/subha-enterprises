<?php

if (!function_exists('indianFmt')) {
    /**
     * Format a number in Indian comma style (e.g., 1,23,456.00).
     */
    function indianFmt($number, int $decimals = 2): string
    {
        $number   = round((float) $number, $decimals);
        $negative = $number < 0;
        $number   = abs($number);

        $parts   = explode('.', number_format($number, $decimals, '.', ''));
        $intPart = $parts[0];
        $decPart = $parts[1];

        if (strlen($intPart) > 3) {
            $last3    = substr($intPart, -3);
            $rest     = substr($intPart, 0, -3);
            $rest     = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);
            $intPart  = $rest . ',' . $last3;
        }

        return ($negative ? '-' : '') . $intPart . '.' . $decPart;
    }
}

if (!function_exists('formatIndian')) {
    /** Alias for indianFmt(). */
    function formatIndian($number, int $decimals = 2): string
    {
        return indianFmt($number, $decimals);
    }
}

if (!function_exists('amountInWords')) {
    /**
     * Convert a rupee amount to Indian-English words.
     * e.g. 1234.50 → "One Thousand Two Hundred Thirty Four Rupees and Fifty Paise Only"
     */
    function amountInWords(float $amount): string
    {
        $intPart = (int) floor(abs($amount));
        $paise   = (int) round((abs($amount) - $intPart) * 100);

        $toWords = null;
        $toWords = function (int $n) use (&$toWords): string {
            static $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
                            'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen',
                            'Seventeen', 'Eighteen', 'Nineteen'];
            static $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
            if ($n === 0) return '';
            $w = '';
            if ($n >= 10000000) { $w .= $toWords((int)($n / 10000000)) . 'Crore ';   $n %= 10000000; }
            if ($n >= 100000)   { $w .= $toWords((int)($n / 100000))   . 'Lakh ';    $n %= 100000; }
            if ($n >= 1000)     { $w .= $toWords((int)($n / 1000))     . 'Thousand '; $n %= 1000; }
            if ($n >= 100)      { $w .= $ones[(int)($n / 100)] . ' Hundred '; $n %= 100; }
            if ($n >= 20)       { $w .= $tens[(int)($n / 10)] . ' '; $n %= 10; }
            if ($n > 0)         { $w .= $ones[$n] . ' '; }
            return $w;
        };

        $words  = $intPart === 0 ? 'Zero' : trim($toWords($intPart));
        $result = $words . ' Rupees';
        if ($paise > 0) {
            $result .= ' and ' . trim($toWords($paise)) . ' Paise';
        }
        return $result . ' Only';
    }
}

if (!function_exists('formatBillNo')) {
    /**
     * Format bill number as [BRCODE]-[YEAR]-[SEQNO].
     * e.g. formatBillNo(1, '2024-25', 45) → "1-2024-00045"
     */
    function formatBillNo(int $brCode, string $finYearName, int $invNo): string
    {
        $year = substr($finYearName, 0, 4);
        return $brCode . '-' . $year . '-' . str_pad($invNo, 5, '0', STR_PAD_LEFT);
    }
}
