<?php // application/modules/distribution/controllers/IndexController.php

class Distribution_IndexController extends Zend_Controller_Action
{
    // public function indexAction()
    // {
    //     $api = new App_Service_Api();
    //     $_ = $api->authorization(); // Lolos otentikasi hak akses

    //     $activeTab = $this->_getParam('type', 'it-provider');

    //     date_default_timezone_set('Asia/Jakarta');
    //     $today = date('Y-m-d');

    //     $rawDateInput  = $this->_getParam('date', '');
    //     $filterDate    = !empty($rawDateInput) ? $rawDateInput : $today;
    //     $filterService = $this->_getParam('service', '');
    //     $filterSort    = $this->_getParam('sort_by', 'total');
    //     $filterSearch  = $this->_getParam('search', '');

    //     // Penentuan filter kode layanan produk
    //     $productCode = "";
    //     if (strtolower($filterService) === 'prepaid') {
    //         $productCode = "502";
    //     } elseif (strtolower($filterService) === 'postpaid') {
    //         $productCode = "501";
    //     } elseif (strtolower($filterService) === 'non-taglist') {
    //         $productCode = "504";
    //     }

    //     $orderBy = "";
    //     if ($filterSort === 'lembar') {
    //         $orderBy = "lembar";
    //     } elseif ($filterSort === 'total') {
    //         $orderBy = "total_amount";
    //     } elseif ($filterSort === 'tagihan') {
    //         $orderBy = "total_bill";
    //     } elseif ($filterSort === 'admin') {
    //         $orderBy = "total_fee";
    //     }

    //     $payload = [$filterDate, $productCode, $orderBy, ""];
    //     $apiUrl  = ($activeTab === 'mitra') ? '/service/proxy/service/alias/get-distribution-mitra' : '/service/proxy/service/alias/get-distribution-itp';

    //     $response = $api->request('POST', $apiUrl, $payload);
    //     $listData = isset($response["msg"]) ? $response["msg"] : [];

    //     // Logic array filter pencarian (search keyword)
    //     if (!empty($filterSearch) && is_array($listData)) {
    //         $searchKeyword = strtolower(trim($filterSearch));
    //         $filteredList = [];
    //         foreach ($listData as $value) {
    //             $namaProviderOrMitra = isset($value["nama"]) ? strtolower($value["nama"]) : '';
    //             $namaLayanan         = isset($value["layanan"]) ? strtolower($value["layanan"]) : '';
    //             $kodeData            = isset($value["kode"]) ? strtolower($value["kode"]) : '';

    //             if (
    //                 strpos($namaProviderOrMitra, $searchKeyword) !== false ||
    //                 strpos($namaLayanan, $searchKeyword) !== false ||
    //                 strpos($kodeData, $searchKeyword) !== false
    //             ) {
    //                 $filteredList[] = $value;
    //             }
    //         }
    //         $listData = $filteredList;
    //     }

    //     // 🎯 INTEGRASI SPREADSHEET_EXCEL_WRITER BERDASARKAN CONTOH KAMU
    //     $isDownload = $this->_getParam('download', 'false');
    //     if ($isDownload === 'true') {
    //         // Matikan render template HTML view & layouting
    //         $this->_helper->layout->disableLayout();
    //         $this->_helper->viewRenderer->setNoRender(true);

    //         // Load Library internal bawaan project
    //         require_once '../library/Spreadsheet/Excel/Writer.php';

    //         $filename = "Rangkuman_Distribusi_" . ($activeTab === 'mitra' ? 'Mitra' : 'ITP') . "_" . date('Ymd_His') . ".xls";

    //         // Inisialisasi kosong agar otomatis mendownload langsung lewat stream browser
    //         $workbook = new Spreadsheet_Excel_Writer();
    //         $workbook->send($filename);

    //         $worksheet = &$workbook->addWorksheet('Rangkuman');

    //         // --- DEKLARASI FORMAT (Mengikuti standard contoh kamu) ---
    //         $format_bold = &$workbook->addFormat();
    //         $format_bold->setBold();

    //         $format_right = &$workbook->addFormat();
    //         $format_right->setAlign('right');

    //         // Style header kolom (mirip $format6 di contoh kamu)
    //         $format_header = &$workbook->addFormat();
    //         $format_header->setBold();
    //         $format_header->setAlign('center');
    //         $format_header->setBorder(1);

    //         // Label judul header kolom dinamis sesuai tipe data tab aktif
    //         $colCode = ($activeTab === 'mitra') ? 'Kode Mitra' : 'Kode IT Provider';
    //         $colName = ($activeTab === 'mitra') ? 'Nama Mitra' : 'Nama IT Provider';

    //         // Tulis Judul Laporan di atas area baris tabel
    //         $worksheet->write(0, 0, "REKAP RANGKUMAN DISTRIBUSI DATA TOKEN", $format_bold);
    //         $worksheet->write(1, 0, "PERIODE FILTER TANGGAL : " . (!empty($rawDateInput) ? $rawDateInput : $today), $format_bold);
    //         $worksheet->write(2, 0, "KATEGORI DISTRIBUSI   : " . strtoupper($activeTab), $format_bold);

    //         // Header table di baris ke-4 (indeks 4)
    //         $worksheet->write(4, 0, 'No', $format_header);
    //         $worksheet->write(4, 1, $colCode, $format_header);
    //         $worksheet->write(4, 2, $colName, $format_header);
    //         $worksheet->write(4, 3, 'Layanan', $format_header);
    //         $worksheet->write(4, 4, 'Lembar', $format_header);
    //         $worksheet->write(4, 5, 'Tagihan (Rp)', $format_header);
    //         $worksheet->write(4, 6, 'Admin ITP (Rp)', $format_header);
    //         $worksheet->write(4, 7, 'Total (Rp)', $format_header);

    //         // Inisialisasi variabel rekap total data awal
    //         $total_lembar  = 0;
    //         $total_amount  = 0;
    //         $total_fee     = 0;
    //         $total_bill    = 0;

    //         $i = 1;
    //         if (is_array($listData) && !empty($listData)) {
    //             foreach ($listData as $lap) {
    //                 // Tulis data ke sheet Excel (Mulai baris indeks ke-5)
    //                 $worksheet->write($i + 4, 0, $i);
    //                 $worksheet->write($i + 4, 1, $lap['kode'] ?? '');
    //                 $worksheet->write($i + 4, 2, $lap['nama'] ?? '');
    //                 $worksheet->write($i + 4, 3, $lap['layanan'] ?? '');

    //                 // Gunakan format data angka numeric asli agar bisa dihitung SUM otomatis oleh user
    //                 $worksheet->writeNumber($i + 4, 4, $lap['lembar'] ?? 0, $format_right);
    //                 $worksheet->writeNumber($i + 4, 5, $lap['total_amount'] ?? 0, $format_right);
    //                 $worksheet->writeNumber($i + 4, 6, $lap['total_fee'] ?? 0, $format_right);
    //                 $worksheet->writeNumber($i + 4, 7, $lap['total_bill'] ?? 0, $format_right);

    //                 // Akumulasi total
    //                 $total_lembar += ($lap['lembar'] ?? 0);
    //                 $total_amount += ($lap['total_amount'] ?? 0);
    //                 $total_fee    += ($lap['total_fee'] ?? 0);
    //                 $total_bill   += ($lap['total_bill'] ?? 0);

    //                 $i++;
    //             }
    //         }

    //         // Cetak Baris Total Rekapitulasi di akhir data (Baris dinamis sesuai jumlah data)
    //         $row_total = count($listData) + 5;
    //         $worksheet->write($row_total, 1, 'TOTAL', $format_bold);
    //         $worksheet->writeNumber($row_total, 4, $total_lembar, $format_bold);
    //         $worksheet->writeNumber($row_total, 5, $total_amount, $format_bold);
    //         $worksheet->writeNumber($row_total, 6, $total_fee, $format_bold);
    //         $worksheet->writeNumber($row_total, 7, $total_bill, $format_bold);

    //         $workbook->close();
    //         exit;
    //     }

    //     // --- LOGIC PROSES UNTUK KEBUTUHAN GRAFIK / CHART / SUMMARY (Biarkan Bawaan Aslinya) ---
    //     $summary = ['prepaid' => 0, 'postpaid' => 0, 'non_taglist' => 0];
    //     $chartLabels = [];
    //     $chartDataPrepaid = [];
    //     $chartDataPostpaid = [];
    //     $chartDataNonTaglist = [];
    //     $processedChartData = [];
    //     if (is_array($listData)) {
    //         foreach ($listData as $value) {
    //             if (isset($value["kode"])) {
    //                 $processedChartData[$value["kode"]] = ['nama' => $value["nama"] ?? $value["kode"], 'prepaid' => 0, 'postpaid' => 0, 'nontaglist' => 0];
    //             }
    //         }
    //         foreach ($listData as $value) {
    //             if (isset($value["kode"])) {
    //                 $nilaiLembar = $value['lembar'] ?? ($value['jumlah'] ?? 0);
    //                 switch (strtolower($value['layanan'])) {
    //                     case 'prepaid':
    //                         $processedChartData[$value["kode"]]["prepaid"] = $nilaiLembar;
    //                         break;
    //                     case 'postpaid':
    //                         $processedChartData[$value["kode"]]["postpaid"] = $nilaiLembar;
    //                         break;
    //                     case 'nontaglist':
    //                     case 'non-taglist':
    //                         $processedChartData[$value["kode"]]["nontaglist"] = $nilaiLembar;
    //                         break;
    //                 }
    //             }
    //         }
    //         foreach ($processedChartData as $key => $value) {
    //             $chartLabels[] = $value["nama"];
    //             $chartDataPrepaid[] = $value["prepaid"];
    //             $chartDataPostpaid[] = $value["postpaid"];
    //             $chartDataNonTaglist[] = $value["nontaglist"];
    //             $summary["prepaid"] += $value["prepaid"];
    //             $summary["postpaid"] += $value["postpaid"];
    //             $summary["non_taglist"] += $value["nontaglist"];
    //         }
    //     }

    //     $this->view->activeTab = $activeTab;
    //     $this->view->summary   = $summary;
    //     $this->view->listData  = $listData;

    //     $this->view->filters   = [
    //         'date'    => $rawDateInput,
    //         'service' => $filterService,
    //         'sort_by' => $filterSort,
    //         'search'  => $filterSearch
    //     ];

    //     $this->view->chartJson = json_encode([
    //         'labels' => $chartLabels,
    //         'prepaid' => $chartDataPrepaid,
    //         'postpaid' => $chartDataPostpaid,
    //         'non_taglist' => $chartDataNonTaglist
    //     ]);
    // }


    public function indexAction()
    {
        $api = new App_Service_Api();
        $_ = $api->authorization(); // Lolos otentikasi hak akses utama

        $activeTab = $this->_getParam('type', 'it-provider');

        date_default_timezone_set('Asia/Jakarta');
        $today = date('Y-m-d');

        $rawDateInput  = $this->_getParam('date', '');
        $filterDate    = !empty($rawDateInput) ? $rawDateInput : $today;
        $filterService = $this->_getParam('service', '');
        $filterSort    = $this->_getParam('sort_by', 'total');
        $filterSearch  = $this->_getParam('search', '');

        // Penentuan filter kode layanan produk
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
        $apiUrl  = ($activeTab === 'mitra') ? '/service/proxy/service/alias/get-distribution-mitra' : '/service/proxy/service/alias/get-distribution-itp';

        $response = $api->request('POST', $apiUrl, $payload);
        $listData = isset($response["msg"]) ? $response["msg"] : [];

        // Logic array filter pencarian (search keyword)
        if (!empty($filterSearch) && is_array($listData)) {
            $searchKeyword = strtolower(trim($filterSearch));
            $filteredList = [];
            foreach ($listData as $value) {
                $namaProviderOrMitra = isset($value["nama"]) ? strtolower($value["nama"]) : '';
                $namaLayanan         = isset($value["layanan"]) ? strtolower($value["layanan"]) : '';
                $kodeData            = isset($value["kode"]) ? strtolower($value["kode"]) : '';

                if (
                    strpos($namaProviderOrMitra, $searchKeyword) !== false ||
                    strpos($namaLayanan, $searchKeyword) !== false ||
                    strpos($kodeData, $searchKeyword) !== false
                ) {
                    $filteredList[] = $value;
                }
            }
            $listData = $filteredList;
        }

        // 🎯 LOGIK DOWNLOADING EXCEL DENGAN SYNTAX JADUL (ANTI PARSER ERROR & DATA KOSONG)
        $isDownload = $this->_getParam('download', 'false');
        if ($isDownload === 'true') {
            // Matikan render template HTML view & layouting
            $this->_helper->layout->disableLayout();
            $this->_helper->viewRenderer->setNoRender(true);

            // Load Library internal bawaan project
            require_once '../library/Spreadsheet/Excel/Writer.php';

            $filename = "Rangkuman_Distribusi_" . ($activeTab === 'mitra' ? 'Mitra' : 'ITP') . "_" . date('Ymd_His') . ".xls";

            $workbook = new Spreadsheet_Excel_Writer();
            $workbook->send($filename);

            $worksheet = &$workbook->addWorksheet('Rangkuman');

            // --- DEKLARASI FORMAT ---
            $format_bold = &$workbook->addFormat();
            $format_bold->setBold();

            $format_right = &$workbook->addFormat();
            $format_right->setAlign('right');

            $format_header = &$workbook->addFormat();
            $format_header->setBold();
            $format_header->setAlign('center');
            $format_header->setBorder(1);

            $colCode = ($activeTab === 'mitra') ? 'Kode Mitra' : 'Kode IT Provider';
            $colName = ($activeTab === 'mitra') ? 'Nama Mitra' : 'Nama IT Provider';

            // Tulis Judul Laporan (Menggunakan concate jadul dot/titik tanpa {} agar ramah parser)
            $worksheet->write(0, 0, "REKAP RANGKUMAN DISTRIBUSI DATA TOKEN", $format_bold);
            $worksheet->write(1, 0, "PERIODE FILTER TANGGAL : " . $filterDate, $format_bold);
            $worksheet->write(2, 0, "KATEGORI DISTRIBUSI   : " . strtoupper($activeTab), $format_bold);

            // Header table di baris ke-4 (indeks 4)
            $worksheet->write(4, 0, 'No', $format_header);
            $worksheet->write(4, 1, $colCode, $format_header);
            $worksheet->write(4, 2, $colName, $format_header);
            $worksheet->write(4, 3, 'Layanan', $format_header);
            $worksheet->write(4, 4, 'Lembar', $format_header);
            $worksheet->write(4, 5, 'Tagihan (Rp)', $format_header);
            $worksheet->write(4, 6, 'Admin ITP (Rp)', $format_header);
            $worksheet->write(4, 7, 'Total (Rp)', $format_header);

            $total_lembar  = 0;
            $total_amount  = 0;
            $total_fee     = 0;
            $total_bill    = 0;

            $i = 1;
            if (is_array($listData) && !empty($listData)) {
                foreach ($listData as $lap) {
                    // 🎯 AMBIL NILAI MENGGUNAKAN IF JADUL BERSTRUKTUR (100% AMAN DARI ERROR PARSER)
                    $kode    = isset($lap['kode']) ? $lap['kode'] : (isset($lap['KODE']) ? $lap['KODE'] : '');
                    $nama    = isset($lap['nama']) ? $lap['nama'] : (isset($lap['NAMA']) ? $lap['NAMA'] : '');
                    $layanan = isset($lap['layanan']) ? $lap['layanan'] : (isset($lap['LAYANAN']) ? $lap['LAYANAN'] : '');

                    // Deteksi Key Lembar
                    if (isset($lap['lembar'])) {
                        $nilaiLembar = $lap['lembar'];
                    } elseif (isset($lap['LEMBAR'])) {
                        $nilaiLembar = $lap['LEMBAR'];
                    } elseif (isset($lap['jumlah'])) {
                        $nilaiLembar = $lap['jumlah'];
                    } elseif (isset($lap['JUMLAH'])) {
                        $nilaiLembar = $lap['JUMLAH'];
                    } else {
                        $nilaiLembar = 0;
                    }

                    // Deteksi Key Tagihan (total_amount)
                    if (isset($lap['total_amount'])) {
                        $nilaiTagihan = $lap['total_amount'];
                    } elseif (isset($lap['TOTAL_AMOUNT'])) {
                        $nilaiTagihan = $lap['TOTAL_AMOUNT'];
                    } elseif (isset($lap['tagihan'])) {
                        $nilaiTagihan = $lap['tagihan'];
                    } elseif (isset($lap['TAGIHAN'])) {
                        $nilaiTagihan = $lap['TAGIHAN'];
                    } elseif (isset($lap['rp_pelimpahan'])) {
                        $nilaiTagihan = $lap['rp_pelimpahan'];
                    } elseif (isset($lap['RP_PELIMPAHAN'])) {
                        $nilaiTagihan = $lap['RP_PELIMPAHAN'];
                    } else {
                        $nilaiTagihan = 0;
                    }

                    // Deteksi Key Admin (total_fee)
                    if (isset($lap['total_fee'])) {
                        $nilaiAdmin = $lap['total_fee'];
                    } elseif (isset($lap['TOTAL_FEE'])) {
                        $nilaiAdmin = $lap['TOTAL_FEE'];
                    } elseif (isset($lap['admin'])) {
                        $nilaiAdmin = $lap['admin'];
                    } elseif (isset($lap['ADMIN'])) {
                        $nilaiAdmin = $lap['ADMIN'];
                    } elseif (isset($lap['rp_admin'])) {
                        $nilaiAdmin = $lap['rp_admin'];
                    } elseif (isset($lap['RP_ADMIN'])) {
                        $nilaiAdmin = $lap['RP_ADMIN'];
                    } else {
                        $nilaiAdmin = 0;
                    }

                    // Deteksi Key Total (total_bill)
                    if (isset($lap['total_bill'])) {
                        $nilaiTotal = $lap['total_bill'];
                    } elseif (isset($lap['TOTAL_BILL'])) {
                        $nilaiTotal = $lap['TOTAL_BILL'];
                    } elseif (isset($lap['total'])) {
                        $nilaiTotal = $lap['total'];
                    } elseif (isset($lap['TOTAL'])) {
                        $nilaiTotal = $lap['TOTAL'];
                    } else {
                        $nilaiTotal = 0;
                    }

                    // Tulis string data dasar
                    $worksheet->write($i + 4, 0, $i);
                    $worksheet->write($i + 4, 1, $kode);
                    $worksheet->write($i + 4, 2, $nama);
                    $worksheet->write($i + 4, 3, $layanan);

                    // Pastikan konversi casting data ke numeric murni saat dikirim agar tidak dibaca kosong/0
                    $worksheet->writeNumber($i + 4, 4, intval($nilaiLembar), $format_right);
                    $worksheet->writeNumber($i + 4, 5, floatval($nilaiTagihan), $format_right);
                    $worksheet->writeNumber($i + 4, 6, floatval($nilaiAdmin), $format_right);
                    $worksheet->writeNumber($i + 4, 7, floatval($nilaiTotal), $format_right);

                    // Akumulasi hitungan total rekap bawah
                    $total_lembar  = $total_lembar + intval($nilaiLembar);
                    $total_amount  = $total_amount + floatval($nilaiTagihan);
                    $total_fee     = $total_fee + floatval($nilaiAdmin);
                    $total_bill    = $total_bill + floatval($nilaiTotal);

                    $i++;
                }
            }

            // Cetak Baris Total Rekapitulasi di paling bawah tabel data
            $row_total = count($listData) + 5;
            $worksheet->write($row_total, 1, 'TOTAL', $format_bold);
            $worksheet->writeNumber($row_total, 4, $total_lembar, $format_bold);
            $worksheet->writeNumber($row_total, 5, $total_amount, $format_bold);
            $worksheet->writeNumber($row_total, 6, $total_fee, $format_bold);
            $worksheet->writeNumber($row_total, 7, $total_bill, $format_bold);

            $workbook->close();
            exit;
        }

        // --- LOGIC PROSES UNTUK KEBUTUHAN GRAFIK / CHART / SUMMARY (Bawaan Asli Kamu) ---
        $summary = ['prepaid' => 0, 'postpaid' => 0, 'non_taglist' => 0];
        $chartLabels = [];
        $chartDataPrepaid = [];
        $chartDataPostpaid = [];
        $chartDataNonTaglist = [];
        $processedChartData = [];
        if (is_array($listData)) {
            foreach ($listData as $value) {
                if (isset($value["kode"])) {
                    $processedChartData[$value["kode"]] = ['nama' => $value["nama"] ?? $value["kode"], 'prepaid' => 0, 'postpaid' => 0, 'nontaglist' => 0];
                }
            }
            foreach ($listData as $value) {
                if (isset($value["kode"])) {
                    $nilaiLembar = $value['lembar'] ?? ($value['jumlah'] ?? 0);
                    switch (strtolower($value['layanan'])) {
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
                $chartLabels[] = $value["nama"];
                $chartDataPrepaid[] = $value["prepaid"];
                $chartDataPostpaid[] = $value["postpaid"];
                $chartDataNonTaglist[] = $value["nontaglist"];
                $summary["prepaid"] += $value["prepaid"];
                $summary["postpaid"] += $value["postpaid"];
                $summary["non_taglist"] += $value["nontaglist"];
            }
        }

        $this->view->activeTab = $activeTab;
        $this->view->summary   = $summary;
        $this->view->listData  = $listData;

        $this->view->filters   = [
            'date'    => $rawDateInput,
            'service' => $filterService,
            'sort_by' => $filterSort,
            'search'  => $filterSearch
        ];

        $this->view->chartJson = json_encode([
            'labels' => $chartLabels,
            'prepaid' => $chartDataPrepaid,
            'postpaid' => $chartDataPostpaid,
            'non_taglist' => $chartDataNonTaglist
        ]);
    }
}
