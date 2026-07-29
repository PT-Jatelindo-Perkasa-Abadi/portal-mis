<?php

class Distribution_Model_Aps
{
    protected $_api;

    public function __construct()
    {
        $this->_api = new App_Service_Api();
    }

    /**
     * Mengambil data distribusi dari API berdasarkan parameter filter
     */
    public function getDistributionData($activeTab, $filterDate, $filterService, $filterSort)
    {
        $this->_api->authorization();

        $productCode = "";
        if (strtolower($filterService) === 'prepaid') {
            $productCode = "502";
        } elseif (strtolower($filterService) === 'postpaid') {
            $productCode = "501";
        } elseif (strtolower($filterService) === 'non-taglist') {
            $productCode = "504";
        }

        $orderBy = "";
        if ($filterSort === 'lembar') {
            $orderBy = "lembar";
        } elseif ($filterSort === 'total') {
            $orderBy = "total_amount";
        } elseif ($filterSort === 'tagihan') {
            $orderBy = "total_bill";
        } elseif ($filterSort === 'admin') {
            $orderBy = "total_fee";
        }

        $payload = [$filterDate, $productCode, $orderBy, ""];
        $apiUrl  = ($activeTab === 'mitra')
            ? '/service/proxy/service/alias/get-distribution-mitra'
            : '/service/proxy/service/alias/get-distribution-itp';

        try {
            $response = $this->_api->request('POST', $apiUrl, $payload);
            if (!$response || !is_array($response)) {
                throw new Exception("API response invalid atau gagal koneksi.");
            }

            if (isset($response["msg"]) && is_array($response["msg"])) {
                return ['success' => true, 'data' => $response["msg"]];
            } elseif (is_array($response) && isset($response[0])) {
                return ['success' => true, 'data' => $response];
            }

            return ['success' => true, 'data' => []];
        } catch (Exception $e) {
            error_log("Error API Distribution: " . $e->getMessage());
            return ['success' => false, 'data' => []];
        }
    }

    /**
     * Melakukan pencarian global pada list data
     */
    public function filterSearchData($listData, $filterSearch)
    {
        if (empty($filterSearch) || !is_array($listData) || empty($listData)) {
            return $listData;
        }

        $searchKeyword = strtolower(trim($filterSearch));
        $filteredList  = [];

        foreach ($listData as $value) {
            $kodeData   = isset($value["kode"]) ? strtolower($value["kode"]) : '';
            $namaData   = isset($value["nama"]) ? strtolower($value["nama"]) : '';
            $layananRaw = isset($value["layanan"]) ? trim($value["layanan"]) : '';

            $namaLayanan = in_array(strtolower($layananRaw), ['nontaglist', 'non-taglist'])
                ? 'non-taglist'
                : strtolower($layananRaw);

            $lembar  = isset($value['lembar']) ? (string)$value['lembar'] : (isset($value['jumlah']) ? (string)$value['jumlah'] : '0');
            $tagihan = isset($value['tagihan']) ? str_replace('.', '', (string)$value['tagihan']) : '0';
            $admin   = isset($value['admin']) ? str_replace('.', '', (string)$value['admin']) : '0';
            $total   = isset($value['total']) ? str_replace('.', '', (string)$value['total']) : '0';

            if (
                strpos($kodeData, $searchKeyword) !== false ||
                strpos($namaData, $searchKeyword) !== false ||
                strpos($namaLayanan, $searchKeyword) !== false ||
                strpos($lembar, $searchKeyword) !== false ||
                strpos($tagihan, $searchKeyword) !== false ||
                strpos($admin, $searchKeyword) !== false ||
                strpos($total, $searchKeyword) !== false
            ) {
                $filteredList[] = $value;
            }
        }

        return $filteredList;
    }

    /**
     * Memproses data untuk Chart dan Summary
     */
    public function processChartAndSummary($listData)
    {
        $summary = ['prepaid' => 0, 'postpaid' => 0, 'non_taglist' => 0];
        $chartLabels = [];
        $chartDataPrepaid = [];
        $chartDataPostpaid = [];
        $chartDataNonTaglist = [];
        $processedChartData = [];

        if (is_array($listData) && !empty($listData)) {
            foreach ($listData as $value) {
                if (isset($value["kode"])) {
                    $processedChartData[$value["kode"]] = [
                        'nama'     => $value["nama"] ?? $value["kode"],
                        'prepaid'  => 0,
                        'postpaid' => 0,
                        'nontaglist' => 0
                    ];
                }
            }
            foreach ($listData as $value) {
                if (isset($value["kode"])) {
                    $nilaiLembar = $value['lembar'] ?? ($value['jumlah'] ?? 0);
                    switch (strtolower($value['layanan'] ?? '')) {
                        case 'prepaid':
                            $processedChartData[$value["kode"]]["prepaid"] = $nilaiLembar;
                            break;
                        case 'postpaid':
                            $processedChartData[$value["kode"]]["postpaid"] = $nilaiLembar;
                            break;
                        case 'nontaglist':
                        case 'non-taglist':
                            $processedChartData[$value["kode"]]["nontaglist"] = $nilaiLembar;
                            break;
                    }
                }
            }
            foreach ($processedChartData as $key => $value) {
                $chartLabels[]         = $value["nama"];
                $chartDataPrepaid[]    = $value["prepaid"];
                $chartDataPostpaid[]   = $value["postpaid"];
                $chartDataNonTaglist[] = $value["nontaglist"];

                $summary["prepaid"]     += $value["prepaid"];
                $summary["postpaid"]    += $value["postpaid"];
                $summary["non_taglist"] += $value["nontaglist"];
            }
        }

        return [
            'summary'   => $summary,
            'chartJson' => json_encode([
                'labels'      => $chartLabels,
                'prepaid'     => $chartDataPrepaid,
                'postpaid'    => $chartDataPostpaid,
                'non_taglist' => $chartDataNonTaglist
            ])
        ];
    }

    /**
     * Memproses Export Excel
     */
    public function exportExcel($listData, $activeTab, $filterDate)
    {
        set_include_path(get_include_path() . PATH_SEPARATOR . APPLICATION_PATH . '/../library');
        require_once '../library/Spreadsheet/Excel/Writer.php';

        $filename = "Rangkuman_Distribusi_" . ($activeTab === 'mitra' ? 'Mitra' : 'ITP') . "_" . date('Ymd_His') . ".xls";

        $workbook = new Spreadsheet_Excel_Writer();
        $workbook->send($filename);

        $worksheet = &$workbook->addWorksheet('Rangkuman');

        $format_bold = &$workbook->addFormat();
        $format_bold->setBold();

        $format_center_data = &$workbook->addFormat();
        $format_center_data->setAlign('center');
        $format_center_data->setBorder(1);

        $format_lembar_data = &$workbook->addFormat();
        $format_lembar_data->setAlign('right');
        $format_lembar_data->setBorder(1);

        $format_uang = &$workbook->addFormat();
        $format_uang->setAlign('right');
        $format_uang->setBorder(1);
        $format_uang->setNumFormat('#,##0');

        $format_header = &$workbook->addFormat();
        $format_header->setBold();
        $format_header->setAlign('center');
        $format_header->setBorder(1);

        $format_total_uang = &$workbook->addFormat();
        $format_total_uang->setBold();
        $format_total_uang->setAlign('right');
        $format_total_uang->setBorder(1);
        $format_total_uang->setNumFormat('#,##0');

        $format_total_lembar = &$workbook->addFormat();
        $format_total_lembar->setBold();
        $format_total_lembar->setAlign('right');
        $format_total_lembar->setBorder(1);

        $format_total_text = &$workbook->addFormat();
        $format_total_text->setBold();
        $format_total_text->setAlign('center');
        $format_total_text->setBorder(1);

        $worksheet->setColumn(0, 0, 8);
        $worksheet->setColumn(1, 1, 18);
        $worksheet->setColumn(2, 2, 32);
        $worksheet->setColumn(3, 3, 16);
        $worksheet->setColumn(4, 4, 14);
        $worksheet->setColumn(5, 5, 20);
        $worksheet->setColumn(6, 6, 20);
        $worksheet->setColumn(7, 7, 20);

        $colCode = ($activeTab === 'mitra') ? 'Kode Mitra' : 'Kode IT Provider';
        $colName = ($activeTab === 'mitra') ? 'Nama Mitra' : 'Nama IT Provider';

        $worksheet->write(0, 0, "REKAP RANGKUMAN DISTRIBUSI", $format_bold);
        $worksheet->write(1, 0, "PERIODE FILTER TANGGAL : " . $filterDate, $format_bold);
        $worksheet->write(2, 0, "KATEGORI DISTRIBUSI   : " . strtoupper($activeTab), $format_bold);

        $worksheet->write(4, 0, 'No', $format_header);
        $worksheet->write(4, 1, $colCode, $format_header);
        $worksheet->write(4, 2, $colName, $format_header);
        $worksheet->write(4, 3, 'Layanan', $format_header);
        $worksheet->write(4, 4, 'Lembar', $format_header);
        $worksheet->write(4, 5, 'Tagihan (Rp)', $format_header);
        $worksheet->write(4, 6, 'Admin ITP (Rp)', $format_header);
        $worksheet->write(4, 7, 'Total (Rp)', $format_header);

        $total_lembar = 0;
        $total_amount = 0;
        $total_fee    = 0;
        $total_bill   = 0;

        $i = 1;
        if (is_array($listData) && !empty($listData)) {
            foreach ($listData as $lap) {
                $kode    = $lap['kode'] ?? ($lap['KODE'] ?? '');
                $nama    = $lap['nama'] ?? ($lap['NAMA'] ?? '');
                $layanan = $lap['layanan'] ?? ($lap['LAYANAN'] ?? '');

                $nilaiLembar  = $lap['lembar'] ?? ($lap['LEMBAR'] ?? ($lap['jumlah'] ?? ($lap['JUMLAH'] ?? 0)));
                $nilaiTagihan = $lap['total_amount'] ?? ($lap['TOTAL_AMOUNT'] ?? ($lap['tagihan'] ?? ($lap['TAGIHAN'] ?? ($lap['rp_pelimpahan'] ?? ($lap['RP_PELIMPAHAN'] ?? 0)))));
                $nilaiAdmin   = $lap['total_fee'] ?? ($lap['TOTAL_FEE'] ?? ($lap['admin'] ?? ($lap['ADMIN'] ?? ($lap['rp_admin'] ?? ($lap['RP_ADMIN'] ?? 0)))));
                $nilaiTotal   = $lap['total_bill'] ?? ($lap['TOTAL_BILL'] ?? ($lap['total'] ?? ($lap['TOTAL'] ?? 0)));

                $worksheet->write($i + 4, 0, $i, $format_center_data);
                $worksheet->write($i + 4, 1, $kode, $format_center_data);
                $worksheet->write($i + 4, 2, $nama, $format_center_data);
                $worksheet->write($i + 4, 3, $layanan, $format_center_data);

                $worksheet->writeNumber($i + 4, 4, intval($nilaiLembar), $format_lembar_data);
                $worksheet->writeNumber($i + 4, 5, floatval($nilaiTagihan), $format_uang);
                $worksheet->writeNumber($i + 4, 6, floatval($nilaiAdmin), $format_uang);
                $worksheet->writeNumber($i + 4, 7, floatval($nilaiTotal), $format_uang);

                $total_lembar += intval($nilaiLembar);
                $total_amount += floatval($nilaiTagihan);
                $total_fee    += floatval($nilaiAdmin);
                $total_bill   += floatval($nilaiTotal);

                $i++;
            }
        }

        $row_total = count($listData) + 5;

        $worksheet->write($row_total, 0, '', $format_total_text);
        $worksheet->write($row_total, 1, 'TOTAL', $format_total_text);
        $worksheet->write($row_total, 2, '', $format_total_text);
        $worksheet->write($row_total, 3, '', $format_total_text);

        $worksheet->writeNumber($row_total, 4, $total_lembar, $format_total_lembar);
        $worksheet->writeNumber($row_total, 5, $total_amount, $format_total_uang);
        $worksheet->writeNumber($row_total, 6, $total_fee, $format_total_uang);
        $worksheet->writeNumber($row_total, 7, $total_bill, $format_total_uang);

        $workbook->close();
    }
}
