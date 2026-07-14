<?php // application/modules/distribution/controllers/IndexController.php

class Distribution_IndexController extends Zend_Controller_Action
{
    public function indexAction()
    {
        $api = new App_Service_Api();
        $_ = $api->authorization();

        // 1. Deteksi Tab Aktif (Default: it-provider)
        $activeTab = $this->_getParam('type', 'it-provider');

        // 2. Tentukan Tanggal Hari Ini Secara Realtime (Format Standard API: YYYY-MM-DD)
        date_default_timezone_set('Asia/Jakarta');
        $today = date('Y-m-d');

        // 3. Tangkap Parameter Filter dari Form GET Request
        $filterDate    = $this->_getParam('date', $today);       
        $filterService = $this->_getParam('service', '');       
        $filterSort    = $this->_getParam('sort_by', 'total');   

        // Mapping String Dropdown View ke Kode Angka Produk
        $productCode = "";
        if (strtolower($filterService) === 'prepaid') {
            $productCode = "502";
        } elseif (strtolower($filterService) === 'postpaid') {
            $productCode = "501";
        } elseif (strtolower($filterService) === 'non-taglist') {
            $productCode = "504";
        }

        // Mapping Aturan Urutan / Sorting Data
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

        // --- MOCK DATA GRAFIK & SUM KARTU UNTUK TAB MITRA ---
        if ($activeTab === 'mitra') {
            $summary = ['prepaid' => 4150, 'postpaid' => 3100, 'non_taglist' => 980];
            $chartLabels = ['Mitra Sejahtera', 'Global Sinergi', 'Nusa Raya', 'Indo Sentosa', 'Prima Karya'];
            $chartDataPrepaid = [380, 290, 210, 150, 90];
            $chartDataPostpaid = [310, 410, 280, 180, 120];
            $chartDataNonTaglist = [290, 320, 310, 220, 110];

            $listData = [
                ['kode' => 'MTS', 'nama' => 'Mitra Sejahtera', 'layanan' => 'Prepaid', 'lembar' => 45, 'tagihan' => '3.150.000', 'admin' => '45.000', 'total' => '3.195.000'],
                ['kode' => 'MTS', 'nama' => 'Mitra Sejahtera', 'layanan' => 'Postpaid', 'lembar' => 32, 'tagihan' => '2.400.000', 'admin' => '32.000', 'total' => '2.432.000'],
                ['kode' => 'GBS', 'nama' => 'Global Sinergi', 'layanan' => 'Non-Taglist', 'lembar' => 15, 'tagihan' => '1.100.000', 'admin' => '15.000', 'total' => '1.115.000'],
            ];
        } else {
            // --- DATA ASLI DARI LIVE API UNTUK IT PROVIDER ---
            $payload = [$filterDate, $productCode, $orderBy, ""];
            $response = $api->request('POST', '/service/proxy/service/alias/get-distribution-itp', $payload);
            $listData = isset($response["msg"]) ? $response["msg"] : [];

            // LOGIKA FALLBACK: Jika tanggal realtime hari ini kosong, otomatis mundurkan ke tanggal yang ada datanya
            if (empty($listData) && $filterDate === $today) {
                $filterDate = "2026-02-26"; 
                $payload[0] = $filterDate;
                $response = $api->request('POST', '/service/proxy/service/alias/get-distribution-itp', $payload);
                $listData = isset($response["msg"]) ? $response["msg"] : [];
            }

            $summary = ['prepaid' => 0, 'postpaid' => 0, 'non_taglist' => 0];
            $chartLabels = [];
            $chartDataPrepaid = [];
            $chartDataPostpaid = [];
            $chartDataNonTaglist = [];

            $itpChart = [];
            if (is_array($listData)) {
                // Inisialisasi awal key agar tidak terjadi notice undefined index
                foreach ($listData as $value) {
                    $itpChart[$value["kode"]] = [
                        'nama' => $value["nama"],
                        'prepaid' => 0,
                        'postpaid' => 0,
                        'nontaglist' => 0
                    ];
                }

                foreach ($listData as $value) {
                    // 🎯 FIX SINKRONISASI: Menggunakan operator fallback 'lembar' ?? 'jumlah' agar sinkron dengan tabel data
                    $nilaiLembar = isset($value['lembar']) ? $value['lembar'] : (isset($value['jumlah']) ? $value['jumlah'] : 0);
                    
                    switch (strtolower($value['layanan'])) {
                        case 'prepaid':
                            $itpChart[$value["kode"]]["prepaid"] = $nilaiLembar;
                            break;
                        case 'postpaid':
                            $itpChart[$value["kode"]]["postpaid"] = $nilaiLembar;
                            break;
                        case 'nontaglist':
                        case 'non-taglist':
                            $itpChart[$value["kode"]]["nontaglist"] = $nilaiLembar;
                            break;
                    }
                }

                foreach ($itpChart as $key => $value) {
                    $chartLabels[] = $value["nama"];

                    $chartDataPrepaid[] = $value["prepaid"];
                    $chartDataPostpaid[] = $value["postpaid"];
                    $chartDataNonTaglist[] = $value["nontaglist"];

                    $summary["prepaid"] += $value["prepaid"];
                    $summary["postpaid"] += $value["postpaid"];
                    $summary["non_taglist"] += $value["nontaglist"];
                }
            }
        }

        // Kirim data penampung kembali ke View UI
        $this->view->activeTab = $activeTab;
        $this->view->summary = $summary;
        $this->view->listData = $listData;
        $this->view->filters = [
            'date' => $filterDate,
            'service' => $filterService,
            'sort_by' => $filterSort
        ];

        $this->view->chartJson = json_encode([
            'labels' => $chartLabels,
            'prepaid' => $chartDataPrepaid,
            'postpaid' => $chartDataPostpaid,
            'non_taglist' => $chartDataNonTaglist
        ]);
    }
}