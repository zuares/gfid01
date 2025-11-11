<?php

if (!function_exists('numf')) {
    /**
     * Format angka dengan gaya Indonesia (1.234,56)
     */
    function numf($value, $decimals = 2)
    {
        return number_format($value ?? 0, $decimals, ',', '.');
    }
}

if (!function_exists('idr')) {
    /**
     * Format rupiah (Rp 1.234)
     */
    function idr($value, $decimals = 0)
    {
        return 'Rp ' . number_format($value ?? 0, $decimals, ',', '.');
    }
}
