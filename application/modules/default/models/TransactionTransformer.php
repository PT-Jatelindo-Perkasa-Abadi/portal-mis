<?php

class Default_Model_TransactionTransformer
{
    /**
     * Konfigurasi layanan
     */
    private const SERVICES = [
        'PREPAID' => [
            'label' => 'Prepaid',
            'color' => '#A97800'
        ],
        'POSTPAID' => [
            'label' => 'Postpaid',
            'color' => '#F5B400'
        ],
        'NONTAGLIS' => [
            'label' => 'Non-Taglist',
            'color' => '#F6D98A'
        ]
    ];

    /**
     * Entry Point
     */
    public static function transform(array $rows): array
    {
        $labels = [];
        $summary = [];
        $matrix = [];

        self::buildMatrix(
            $rows,
            $labels,
            $summary,
            $matrix
        );

        return [
            'nominal' => [
                'summary' => self::buildSummary(
                    $summary,
                    'nominal'
                ),
                'chart' => self::buildChart(
                    $labels,
                    $matrix,
                    'nominal'
                )
            ],
            'service' => [
                'summary' => self::buildSummary(
                    $summary,
                    'sheet'
                ),
                'chart' => self::buildChart(
                    $labels,
                    $matrix,
                    'sheet'
                )
            ]
        ];
    }

    /**
     * Build matrix
     *
     * Hanya satu kali membaca response API.
     */
    private static function buildMatrix(
        array $rows,
        array &$labels,
        array &$summary,
        array &$matrix
    ): void {
        /**
         * Inisialisasi
         */
        foreach (self::SERVICES as $service => $config) {
            $summary[$service] = [
                'nominal' => 0,
                'sheet' => 0
            ];

            $matrix[$service] = [];
        }

        /**
         * Single Loop
         */
        foreach ($rows as $row) {
            $service = strtoupper(
                trim($row['LAYANAN'])
            );

            if (!isset(self::SERVICES[$service])) {
                continue;
            }

            $hour = substr(
                $row['JAM'],
                11,
                2
            ) . ':00';

            $nominal = (int) $row['SUM_NOMINAL_TRANSAKSI'];
            $sheet = (int) $row['SUM_LEMBAR'];

            /**
             * Label
             */
            $labels[$hour] = true;

            /**
             * Summary
             */
            $summary[$service]['nominal'] += $nominal;
            $summary[$service]['sheet'] += $sheet;

            /**
             * Matrix
             */
            if (!isset($matrix[$service][$hour])) {
                $matrix[$service][$hour] = [
                    'nominal' => 0,
                    'sheet' => 0
                ];
            }

            /**
             * Nominal
             * Disimpan dalam juta agar sesuai Chart
             */
            $matrix[$service][$hour]['nominal'] += round($nominal / 1000000, 2);

            /**
             * Sheet
             */
            $matrix[$service][$hour]['sheet'] += $sheet;
        }

        /**
         * Urutkan label jam
         */
        ksort($labels);

        $labels = array_keys($labels);
    }

    /**
     * Build Summary Card
     */
    private static function buildSummary(
        array $summary,
        string $field
    ): array {
        $cards = [];

        foreach (self::SERVICES as $service => $config) {
            $cards[] =
                Default_Model_DashboardChartBuilder::summary(
                    $service,
                    $config['label'],
                    $config['color'],
                    $summary[$service][$field] ?? 0
                );
        }

        return $cards;
    }

    /**
     * Build Chart
     */
    private static function buildChart(
        array $labels,
        array $matrix,
        string $field
    ): array {
        $datasets = [];

        foreach (self::SERVICES as $service => $config) {
            $values = [];

            foreach ($labels as $hour) {
                $values[] = $matrix[$service][$hour][$field] ?? 0;
            }

            $datasets[] =
                Default_Model_DashboardChartBuilder::dataset(
                    $config['label'],
                    $values,
                    $config['color']
                );
        }

        return Default_Model_DashboardChartBuilder::chart(
            $labels,
            $datasets
        );
    }
}