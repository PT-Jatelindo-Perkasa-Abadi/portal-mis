<?php

class App_View_Helper_FormatDateTime extends Zend_View_Helper_Abstract
{
    /**
     * Format a date/datetime string (or unix timestamp) using the given format.
     * Returns $fallback when the value is empty or unparseable.
     */
    public function formatDateTime($value, string $format = 'd/m/Y • H:i', string $fallback = '-'): string
    {
        if (empty($value)) {
            return $fallback;
        }
        $ts = is_numeric($value) ? (int) $value : strtotime($value);
        return $ts ? date($format, $ts) : (string) $value;
    }
}
