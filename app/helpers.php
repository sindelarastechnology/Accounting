<?php

if (!function_exists('rupiah')) {
    function rupiah(mixed $value, bool $prefix = true): string
    {
        $formatted = number_format((float) $value, 0, ',', '.');
        return $prefix ? "Rp {$formatted}" : $formatted;
    }
}
