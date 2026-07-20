<?php

class Default_Model_LatencyTransformer
{
    /**
     * Konfigurasi dataset
     */
    private const DATASETS = [
        'latency' => [
            'label' => 'Latency',
            'color' => '#3A7BF6'
        ],
        'rata_rata_request' => [
            'label' => 'Average Request',
            'color' => '#43E0CF'
        ],
        'rata_rata_rt' => [
            'label' => 'Average Response',
            'color' => '#1AD4F3'
        ]
    ];

    /**
     * Entry Point
     */
    public static function transform(array $rows): array
    {
        $labels = [];
        $summary = [];
        $chart = [];

        self::buildData(
            $rows,
            $labels,
            $summary,
            $chart
        );

        return [
            'summary' => self::buildSummary(
                $summary
            ),
            'chart' => self::buildChart(
                $labels,
                $chart
            )
        ];
    }

    /**
     * Build data
     */
    private static function buildData(
        array $rows,
        array &$labels,
        array &$summary,
        array &$chart
    ): void {
        foreach (self::DATASETS as $key => $config) {
            $summary[$key] = 0;
            $chart[$key] = [];
        }

        foreach ($rows as $row) {
            $labels[] = date(
                'H:i',
                strtotime($row['waktu_jam'])
            );

            $latency = (float)$row['latency'];
            $request = (float)$row['rata_rata_request'];
            $response = (float)$row['rata_rata_rt'];

            /**
             * Chart
             */
            $chart['latency'][] = $latency;
            $chart['rata_rata_request'][] = $request;
            $chart['rata_rata_rt'][] = $response;

            /**
             * Summary
             * Menggunakan data terakhir
             */
            $summary['latency'] = $latency;
            $summary['rata_rata_request'] = $request;
            $summary['rata_rata_rt'] = $response;
        }

    }

    /**
     * Summary
     */
    private static function buildSummary(
        array $summary
    ): array {
        $cards = [];

        foreach (self::DATASETS as $key => $config) {
            $cards[] =
                Default_Model_DashboardChartBuilder::summary(
                    $key,
                    $config['label'],
                    $config['color'],
                    $summary[$key]
                );
        }

        return $cards;
    }

    /**
     * Chart
     */
    private static function buildChart(
        array $labels,
        array $chart
    ): array {
        $datasets = [];

        foreach (self::DATASETS as $key => $config) {
            $datasets[] =
                Default_Model_DashboardChartBuilder::dataset(
                    $config['label'],
                    $chart[$key],
                    $config['color']
                );
        }

        return Default_Model_DashboardChartBuilder::chart(
            $labels,
            $datasets
        );
    }
}