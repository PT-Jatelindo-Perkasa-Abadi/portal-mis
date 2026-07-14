<?php

class App_Helper_Date
{
    /**
     * Nama bulan Indonesia
     *
     * @var array
     */
    protected static $months = [
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
        12 => 'Desember'
    ];

    /**
     * Nama hari Indonesia
     *
     * @var array
     */
    protected static $days = [
        'Sunday'    => 'Minggu',
        'Monday'    => 'Senin',
        'Tuesday'   => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday'  => 'Kamis',
        'Friday'    => 'Jumat',
        'Saturday'  => 'Sabtu'
    ];

    /**
     * Format tanggal Indonesia
     *
     * Contoh:
     * App_Helper_Date::indonesia('2026-07-13');
     * // 13 Juli 2026
     *
     * App_Helper_Date::indonesia('2026-07-13 14:30:15', true);
     * // 13 Juli 2026 14:30:15
     *
     * @param string $date
     * @param bool $withTime
     * @return string
     */
    public static function indonesia($date = null, $withTime = false)
    {
        if (empty($date)) {
            $date = date('Y-m-d H:i:s');
        }

        $timestamp = strtotime($date);

        if ($timestamp === false) {
            return '-';
        }

        $result = sprintf(
            '%02d %s %04d',
            date('d', $timestamp),
            self::$months[(int) date('m', $timestamp)],
            date('Y', $timestamp)
        );

        if ($withTime) {
            $result .= ' ' . date('H:i:s', $timestamp);
        }

        return $result;
    }

    /**
     * Format tanggal Indonesia beserta hari
     *
     * Contoh:
     * Senin, 13 Juli 2026
     *
     * @param string $date
     * @param bool $withTime
     * @return string
     */
    public static function full($date, $withTime = false)
    {
        if (empty($date) || $date == '0000-00-00' || $date == '0000-00-00 00:00:00') {
            return '-';
        }

        $timestamp = strtotime($date);

        if ($timestamp === false) {
            return '-';
        }

        $day = self::$days[date('l', $timestamp)];

        return $day . ', ' . self::indonesia($date, $withTime);
    }

    /**
     * Format datetime database
     *
     * @return string
     */
    public static function now()
    {
        return date('Y-m-d H:i:s');
    }

    /**
     * Format date database
     *
     * @return string
     */
    public static function today()
    {
        return date('Y-m-d');
    }

    /**
     * Cek apakah tanggal valid
     *
     * @param string $date
     * @return bool
     */
    public static function isValid($date)
    {
        return strtotime($date) !== false;
    }

    /**
     * Konversi format tanggal
     *
     * @param string $date
     * @param string $format
     * @return string
     */
    public static function format($date, $format = 'Y-m-d')
    {
        if (!self::isValid($date)) {
            return '-';
        }

        return date($format, strtotime($date));
    }

    /**
     * Format untuk input HTML date
     *
     * @param string $date
     * @return string|null
     */
    public static function htmlDate($date)
    {
        if (!self::isValid($date)) {
            return null;
        }

        return date('Y-m-d', strtotime($date));
    }

    /**
     * Format untuk input HTML datetime-local
     *
     * @param string $date
     * @return string|null
     */
    public static function htmlDatetime($date)
    {
        if (!self::isValid($date)) {
            return null;
        }

        return date('Y-m-d\TH:i', strtotime($date));
    }
}