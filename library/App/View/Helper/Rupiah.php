<?php

class App_View_Helper_Rupiah extends Zend_View_Helper_Abstract
{
    /**
     * Format a numeric value as Indonesian Rupiah (e.g. 1000000 -> "1.000.000").
     * Pass $withPrefix = true to prepend "Rp".
     */
    public function rupiah($value, bool $withPrefix = false): string
    {
        $formatted = number_format((float) $value, 0, ',', '.');
        return $withPrefix ? "Rp$formatted" : $formatted;
    }
}
