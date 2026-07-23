<?php
class Reports_Model_mis
{
	public static function rupiah($nominal = 0, $prefix = true)
    {
        if ($nominal === null || $nominal === '') {
            $nominal = 0;
        }

        $hasil = number_format((float)$nominal, 0, ',', '.');

        return $prefix ? $hasil : $hasil;
    }
}