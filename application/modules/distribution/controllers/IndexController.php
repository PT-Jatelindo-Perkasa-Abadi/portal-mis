<?php

class Distribution_IndexController extends Zend_Controller_Action
{
    public function indexAction()
    {
        $api = new App_Service_Api();
        $_ = $api->authorization();

        $activeTab = $this->_getParam('type', 'it-provider');

        date_default_timezone_set('Asia/Jakarta');
        $today = date('Y-m-d');

        $rawDateInput  = $this->_getParam('date', '');
        $filterDate    = !empty($rawDateInput) ? $rawDateInput : $today;
        $filterService = $this->_getParam('service', '');
        $filterSort    = $this->_getParam('sort_by', 'total');
        $filterSearch  = $this->_getParam('search', '');

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

        // 🎯 GLOBAL SEARCH ENGINE UNTUK RANGKUMAN DISTRIBUSI (MENYAPU SEMUA KOLOM DI TABEL)
        if (!empty($filterSearch) && is_array($listData)) {
            $searchKeyword = strtolower(trim($filterSearch));
            $filteredList = [];

            foreach ($listData as $value) {
                $kodeData     = isset($value["kode"]) ? strtolower($value["kode"]) : '';
                $namaData     = isset($value["nama"]) ? strtolower($value["nama"]) : '';
                $layananRaw   = isset($value["layanan"]) ? trim($value["layanan"]) : '';

                // Normalisasi teks layanan sesuai visual badge tabel
                if (in_array(strtolower($layananRaw), ['nontaglist', 'non-taglist'])) {
                    $namaLayanan = 'non-taglist';
                } else {
                    $namaLayanan = strtolower($layananRaw);
                }

                // Ambil data angka & hilangkan titik pemisah ribuan agar pencarian nominal tetap akurat
                $lembar   = isset($value['lembar']) ? (string)$value['lembar'] : (isset($value['jumlah']) ? (string)$value['jumlah'] : '0');
                $tagihan  = isset($value['tagihan']) ? str_replace('.', '', (string)$value['tagihan']) : '0';
                $admin    = isset($value['admin']) ? str_replace('.', '', (string)$value['admin']) : '0';
                $total    = isset($value['total']) ? str_replace('.', '', (string)$value['total']) : '0';

                // Jalankan filter kecocokan di semua kolom tanpa celah
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
            $listData = $filteredList;
        }

        //DOWNLOADING EXCEL 
        $isDownload = $this->_getParam('download', 'false');
        if ($isDownload === 'true') {
            $this->_helper->layout->disableLayout();
            $this->_helper->viewRenderer->setNoRender(true);

            set_include_path(get_include_path() . PATH_SEPARATOR . APPLICATION_PATH . '/../library');
            require_once '../library/Spreadsheet/Excel/Writer.php';

            $filename = "Rangkuman_Distribusi_" . ($activeTab === 'mitra' ? 'Mitra' : 'ITP') . "_" . date('Ymd_His') . ".xls";

            $workbook = new Spreadsheet_Excel_Writer();
            $workbook->send($filename);

            $worksheet = &$workbook->addWorksheet('Rangkuman');

            // --- 🎨 DEFINISI STYLE FORMATTING STANDAR FINANCE (BORDER + ALIGNMENT + SEPARATOR) ---
            $format_bold = &$workbook->addFormat();
            $format_bold->setBold();

            // Style isi data text (No, Kode, Nama, Layanan) -> Center + Border
            $format_center_data = &$workbook->addFormat();
            $format_center_data->setAlign('center');
            $format_center_data->setBorder(1);

            // Style isi data volume (Lembar) -> Right + Border (Angka biasa tanpa desimal)
            $format_lembar_data = &$workbook->addFormat();
            $format_lembar_data->setAlign('right');
            $format_lembar_data->setBorder(1);

            // 🌟 STANDAR FINANCE: Style nominal uang -> Right + Border + Ribuan Separator
            $format_uang = &$workbook->addFormat();
            $format_uang->setAlign('right');
            $format_uang->setBorder(1);
            $format_uang->setNumFormat('#,##0'); // Memunculkan pembatas ribuan otomatis (e.g., 47.800.000)

            // Style untuk Header Tabel -> Bold + Center + Border
            $format_header = &$workbook->addFormat();
            $format_header->setBold();
            $format_header->setAlign('center');
            $format_header->setBorder(1);

            // Style untuk Baris TOTAL nominal uang di bawah -> Bold + Right + Border + Separator
            $format_total_uang = &$workbook->addFormat();
            $format_total_uang->setBold();
            $format_total_uang->setAlign('right');
            $format_total_uang->setBorder(1);
            $format_total_uang->setNumFormat('#,##0');

            // Style untuk Baris TOTAL data lembar -> Bold + Right + Border
            $format_total_lembar = &$workbook->addFormat();
            $format_total_lembar->setBold();
            $format_total_lembar->setAlign('right');
            $format_total_lembar->setBorder(1);

            // Style khusus teks tulisan 'TOTAL' -> Bold + Center + Border
            $format_total_text = &$workbook->addFormat();
            $format_total_text->setBold();
            $format_total_text->setAlign('center');
            $format_total_text->setBorder(1);

            // --- 🛠️ PENGATURAN LEBAR KOLOM (ANTI TEKS TERPOTONG) ---
            $worksheet->setColumn(0, 0, 8);   // Kolom A (No)
            $worksheet->setColumn(1, 1, 18);  // Kolom B (Kode Mitra / ITP)
            $worksheet->setColumn(2, 2, 32);  // Kolom C (Nama Mitra / ITP) -> Lebar untuk nama perusahaan panjang
            $worksheet->setColumn(3, 3, 16);  // Kolom D (Layanan)
            $worksheet->setColumn(4, 4, 14);  // Kolom E (Lembar)
            $worksheet->setColumn(5, 5, 20);  // Kolom F (Tagihan Rp)
            $worksheet->setColumn(6, 6, 20);  // Kolom G (Admin ITP Rp)
            $worksheet->setColumn(7, 7, 20);  // Kolom H (Total Rp)

            $colCode = ($activeTab === 'mitra') ? 'Kode Mitra' : 'Kode IT Provider';
            $colName = ($activeTab === 'mitra') ? 'Nama Mitra' : 'Nama IT Provider';

            // Menulis Informasi Judul dan Filter di atas tabel
            $worksheet->write(0, 0, "REKAP RANGKUMAN DISTRIBUSI", $format_bold);
            $worksheet->write(1, 0, "PERIODE FILTER TANGGAL : " . $filterDate, $format_bold);
            $worksheet->write(2, 0, "KATEGORI DISTRIBUSI   : " . strtoupper($activeTab), $format_bold);

            // Menulis Header Tabel (Baris ke-5 / Indeks 4)
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
                    $kode    = isset($lap['kode']) ? $lap['kode'] : (isset($lap['KODE']) ? $lap['KODE'] : '');
                    $nama    = isset($lap['nama']) ? $lap['nama'] : (isset($lap['NAMA']) ? $lap['NAMA'] : '');
                    $layanan = isset($lap['layanan']) ? $lap['layanan'] : (isset($lap['LAYANAN']) ? $lap['LAYANAN'] : '');

                    // Parsing data Lembar
                    if (isset($lap['lembar'])) { $nilaiLembar = $lap['lembar']; }
                    elseif (isset($lap['LEMBAR'])) { $nilaiLembar = $lap['LEMBAR']; }
                    elseif (isset($lap['jumlah'])) { $nilaiLembar = $lap['jumlah']; }
                    elseif (isset($lap['JUMLAH'])) { $nilaiLembar = $lap['JUMLAH']; }
                    else { $nilaiLembar = 0; }

                    // Parsing data Tagihan
                    if (isset($lap['total_amount'])) { $nilaiTagihan = $lap['total_amount']; }
                    elseif (isset($lap['TOTAL_AMOUNT'])) { $nilaiTagihan = $lap['TOTAL_AMOUNT']; }
                    elseif (isset($lap['tagihan'])) { $nilaiTagihan = $lap['tagihan']; }
                    elseif (isset($lap['TAGIHAN'])) { $nilaiTagihan = $lap['TAGIHAN']; }
                    elseif (isset($lap['rp_pelimpahan'])) { $nilaiTagihan = $lap['rp_pelimpahan']; }
                    elseif (isset($lap['RP_PELIMPAHAN'])) { $nilaiTagihan = $lap['RP_PELIMPAHAN']; }
                    else { $nilaiTagihan = 0; }

                    // Parsing data Admin
                    if (isset($lap['total_fee'])) { $nilaiAdmin = $lap['total_fee']; }
                    elseif (isset($lap['TOTAL_FEE'])) { $nilaiAdmin = $lap['TOTAL_FEE']; }
                    elseif (isset($lap['admin'])) { $nilaiAdmin = $lap['admin']; }
                    elseif (isset($lap['ADMIN'])) { $nilaiAdmin = $lap['ADMIN']; }
                    elseif (isset($lap['rp_admin'])) { $nilaiAdmin = $lap['rp_admin']; }
                    elseif (isset($lap['RP_ADMIN'])) { $nilaiAdmin = $lap['RP_ADMIN']; }
                    else { $nilaiAdmin = 0; }

                    // Parsing data Total Bill
                    if (isset($lap['total_bill'])) { $nilaiTotal = $lap['total_bill']; }
                    elseif (isset($lap['TOTAL_BILL'])) { $nilaiTotal = $lap['TOTAL_BILL']; }
                    elseif (isset($lap['total'])) { $nilaiTotal = $lap['total']; }
                    elseif (isset($lap['TOTAL'])) { $nilaiTotal = $lap['TOTAL']; }
                    else { $nilaiTotal = 0; }

                    // Menulis isi tabel (Menggunakan format rata tengah untuk text & rata kanan dengan separator untuk nominal)
                    $worksheet->write($i + 4, 0, $i, $format_center_data);
                    $worksheet->write($i + 4, 1, $kode, $format_center_data);
                    $worksheet->write($i + 4, 2, $nama, $format_center_data);
                    $worksheet->write($i + 4, 3, $layanan, $format_center_data);

                    $worksheet->writeNumber($i + 4, 4, intval($nilaiLembar), $format_lembar_data);
                    $worksheet->writeNumber($i + 4, 5, floatval($nilaiTagihan), $format_uang);
                    $worksheet->writeNumber($i + 4, 6, floatval($nilaiAdmin), $format_uang);
                    $worksheet->writeNumber($i + 4, 7, floatval($nilaiTotal), $format_uang);

                    $total_lembar  = $total_lembar + intval($nilaiLembar);
                    $total_amount  = $total_amount + floatval($nilaiTagihan);
                    $total_fee     = $total_fee + floatval($nilaiAdmin);
                    $total_bill    = $total_bill + floatval($nilaiTotal);

                    $i++;
                }
            }

            // Menentukan posisi baris TOTAL paling bawah
            $row_total = count($listData) + 5;
            
            // Memberikan border kotak kosong agar selaras dengan tabel atasnya
            $worksheet->write($row_total, 0, '', $format_total_text);
            $worksheet->write($row_total, 1, 'TOTAL', $format_total_text);
            $worksheet->write($row_total, 2, '', $format_total_text);
            $worksheet->write($row_total, 3, '', $format_total_text);
            
            // Menulis total akhir dengan format tebal + pemisah ribuan khusus finance
            $worksheet->writeNumber($row_total, 4, $total_lembar, $format_total_lembar);
            $worksheet->writeNumber($row_total, 5, $total_amount, $format_total_uang);
            $worksheet->writeNumber($row_total, 6, $total_fee, $format_total_uang);
            $worksheet->writeNumber($row_total, 7, $total_bill, $format_total_uang);

            $workbook->close();
            exit;
        }

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
