<?php

class Default_Model_DashboardChartBuilder
{
    /**
     * Membuat item summary dashboard.
     *
     * @param string      $key
     * @param string      $label
     * @param string      $color
     * @param mixed       $value
     * @param string|null $unit
     *
     * @return array
     */
    public static function summary(
        string $key,
        string $label,
        string $color,
        $value,
        ?string $unit = null
    ): array {
        return [
            'key'   => $key,
            'label' => $label,
            'color' => $color,
            'value' => $value,
            'unit'  => $unit
        ];
    }

    /**
     * Membuat dataset Chart.js.
     *
     * @param string $label
     * @param array  $data
     * @param string $color
     *
     * @return array
     */
    public static function dataset(
        string $label,
        array $data,
        string $color
    ): array {
        return [
            'label' => $label,
            'data' => $data,
            'borderColor' => $color,
            'backgroundColor' => $color,
            'borderWidth' => 3,
            'pointRadius' => 0,
            'tension' => 0.35,
            'fill' => false
        ];
    }

    /**
     * Membuat payload Chart.js.
     *
     * @param array $labels
     * @param array $datasets
     *
     * @return array
     */
    public static function chart(
        array $labels,
        array $datasets
    ): array {
        return [
            'labels' => $labels,
            'datasets' => $datasets
        ];
    }
}