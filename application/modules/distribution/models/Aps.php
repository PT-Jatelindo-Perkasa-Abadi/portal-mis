<?php

class Distribution_Model_Aps
{
    protected $_api;

    public function __construct()
    {
        $this->_api = new App_Service_Api();
    }

    public function getMitraAcquirerListFromData($listData)
    {
        if (empty($listData) || !is_array($listData)) {
            return [];
        }

        $uniqueMitra = [];

        foreach ($listData as $row) {
            if (!is_array($row)) continue;

            $code = $row['kode'] ?? $row['KODE'] ?? $row['mitra_code'] ?? null;
            $name = $row['nama'] ?? $row['NAMA'] ?? $row['mitra_name'] ?? $code;

            if (!empty($code) && !isset($uniqueMitra[$code])) {
                $uniqueMitra[$code] = [
                    'code' => (string) $code,
                    'name' => (string) $name
                ];
            }
        }

        return array_values($uniqueMitra);
    }

    public function getDistributionData($activeTab, $filterDate, $filterService, $filterSort, $mitraCode = '')
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

        // Peta endpoint API proxy backend berdasarkan activeTab baru maupun legacy
        $apiUrl = ($activeTab === 'sub-mitra-acquirer' || $activeTab === 'mitra')
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

    public function filterSearchData($listData, $filterSearch = '', $filterMitra = '')
    {
        if (!is_array($listData) || empty($listData)) {
            return [];
        }

        if (empty($filterSearch) && empty($filterMitra)) {
            return $listData;
        }

        $filteredList = [];

        foreach ($listData as $value) {
            $kodeData  = isset($value["kode"]) ? strtolower($value["kode"]) : '';
            $namaData  = isset($value["nama"]) ? strtolower($value["nama"]) : '';

            $namaMitra = isset($value["nama_mitra"])
                ? strtolower($value["nama_mitra"])
                : (isset($value["mitra"]) ? strtolower($value["mitra"]) : '');

            if (!empty($filterMitra)) {
                $mitraLower = strtolower(trim($filterMitra));
                $matchMitra = (
                    strpos($kodeData, $mitraLower) !== false ||
                    strpos($namaData, $mitraLower) !== false ||
                    strpos($namaMitra, $mitraLower) !== false
                );

                if (!$matchMitra) {
                    continue;
                }
            }

            if (!empty($filterSearch)) {
                $searchKeyword = strtolower(trim($filterSearch));
                $layananRaw    = isset($value["layanan"]) ? trim($value["layanan"]) : '';
                $namaLayanan   = in_array(strtolower($layananRaw), ['nontaglist', 'non-taglist']) ? 'non-taglist' : strtolower($layananRaw);

                $lembar  = isset($value['lembar']) ? (string)$value['lembar'] : (isset($value['jumlah']) ? (string)$value['jumlah'] : '0');
                $tagihan = isset($value['tagihan']) ? str_replace('.', '', (string)$value['tagihan']) : '0';
                $admin   = isset($value['admin']) ? str_replace('.', '', (string)$value['admin']) : '0';
                $total   = isset($value['total']) ? str_replace('.', '', (string)$value['total']) : '0';

                $matchSearch = (
                    strpos($kodeData, $searchKeyword) !== false ||
                    strpos($namaData, $searchKeyword) !== false ||
                    strpos($namaMitra, $searchKeyword) !== false ||
                    strpos($namaLayanan, $searchKeyword) !== false ||
                    strpos($lembar, $searchKeyword) !== false ||
                    strpos($tagihan, $searchKeyword) !== false ||
                    strpos($admin, $searchKeyword) !== false ||
                    strpos($total, $searchKeyword) !== false
                );

                if (!$matchSearch) {
                    continue;
                }
            }

            $filteredList[] = $value;
        }

        return $filteredList;
    }

    public function getMasterMitraList()
    {
        return [
            ['code' => 'Jatelindo Perkasa Abadi',    'name' => 'Jatelindo Perkasa Abadi'],
            ['code' => 'Magna Karsa Mulya',          'name' => 'Magna Karsa Mulya'],
            ['code' => 'Value Stream International', 'name' => 'Value Stream International'],
            ['code' => 'Gerbang Sinergi Prima',      'name' => 'Gerbang Sinergi Prima'],
            ['code' => 'Sarana Yukti Bandhana',      'name' => 'Sarana Yukti Bandhana'],
        ];
    }

    public function processChartAndSummary($listData)
    {
        $summary = ['prepaid' => 0, 'postpaid' => 0, 'non_taglist' => 0];
        $chartLabels         = [];
        $chartDataPrepaid    = [];
        $chartDataPostpaid   = [];
        $chartDataNonTaglist = [];
        $groupedData         = [];

        if (is_array($listData) && !empty($listData)) {
            foreach ($listData as $value) {
                $namaDisplay = !empty($value["nama"])
                    ? $value["nama"]
                    : (!empty($value["nama_mitra"]) ? $value["nama_mitra"] : ($value["kode"] ?? 'Lainnya'));

                $groupKey = strtolower(trim($namaDisplay));

                if (!isset($groupedData[$groupKey])) {
                    $groupedData[$groupKey] = [
                        'nama'       => $namaDisplay,
                        'prepaid'    => 0,
                        'postpaid'   => 0,
                        'nontaglist' => 0
                    ];
                }

                $nilaiLembar = intval($value['lembar'] ?? ($value['jumlah'] ?? 0));
                $layanan     = strtolower(trim($value['layanan'] ?? ''));

                if ($layanan === 'prepaid') {
                    $groupedData[$groupKey]['prepaid'] += $nilaiLembar;
                    $summary['prepaid'] += $nilaiLembar;
                } elseif ($layanan === 'postpaid') {
                    $groupedData[$groupKey]['postpaid'] += $nilaiLembar;
                    $summary['postpaid'] += $nilaiLembar;
                } elseif (in_array($layanan, ['nontaglist', 'non-taglist'])) {
                    $groupedData[$groupKey]['nontaglist'] += $nilaiLembar;
                    $summary['non_taglist'] += $nilaiLembar;
                }
            }

            foreach ($groupedData as $item) {
                $chartLabels[]         = $item['nama'];
                $chartDataPrepaid[]    = $item['prepaid'];
                $chartDataPostpaid[]   = $item['postpaid'];
                $chartDataNonTaglist[] = $item['nontaglist'];
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

    public function exportExcel($listData, $activeTab, $filterDate)
    {
        set_include_path(get_include_path() . PATH_SEPARATOR . APPLICATION_PATH . '/../library');
        require_once '../library/Spreadsheet/Excel/Writer.php';

        $isSubMitra = ($activeTab === 'sub-mitra-acquirer' || $activeTab === 'submitra' || $activeTab === 'mitra');

        $fileSuffix  = $isSubMitra ? 'Sub_Mitra_Acquirer' : 'Mitra_Acquirer';
        $labelHeader = $isSubMitra ? 'SUB MITRA ACQUIRER' : 'MITRA ACQUIRER';
        $colCode     = $isSubMitra ? 'Kode Sub Mitra' : 'Kode Mitra Acquirer';
        $colName     = $isSubMitra ? 'Nama Sub Mitra' : 'Nama Mitra Acquirer';
        $colAdmin    = $isSubMitra ? 'Admin Sub Mitra (Rp)' : 'Admin Mitra Acquirer (Rp)';

        $filename = "Rangkuman_Distribusi_" . $fileSuffix . "_" . date('Ymd_His') . ".xls";

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
        $worksheet->setColumn(1, 1, 22);
        $worksheet->setColumn(2, 2, 35);
        $worksheet->setColumn(3, 3, 16);
        $worksheet->setColumn(4, 4, 14);
        $worksheet->setColumn(5, 5, 20);
        $worksheet->setColumn(6, 6, 26);
        $worksheet->setColumn(7, 7, 20);

        $worksheet->write(0, 0, "REKAP RANGKUMAN DISTRIBUSI", $format_bold);
        $worksheet->write(1, 0, "PERIODE FILTER TANGGAL : " . $filterDate, $format_bold);
        $worksheet->write(2, 0, "KATEGORI DISTRIBUSI   : " . $labelHeader, $format_bold);

        $worksheet->write(4, 0, 'No', $format_header);
        $worksheet->write(4, 1, $colCode, $format_header);
        $worksheet->write(4, 2, $colName, $format_header);
        $worksheet->write(4, 3, 'Layanan', $format_header);
        $worksheet->write(4, 4, 'Lembar', $format_header);
        $worksheet->write(4, 5, 'Tagihan (Rp)', $format_header);
        $worksheet->write(4, 6, $colAdmin, $format_header);
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
