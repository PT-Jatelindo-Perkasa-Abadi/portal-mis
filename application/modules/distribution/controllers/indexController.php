<?php // application/modules/distribution/controllers/IndexController.php

class Distribution_IndexController extends Zend_Controller_Action
{
    public function indexAction()
    {
        // Deteksi Tab Aktif (Default: it-provider)
        $activeTab = $this->_getParam('type', 'it-provider');

        // --- MOCK DATA GRAFIK & SUM KARTU ---
        if ($activeTab === 'mitra') {
            $summary = ['prepaid' => 4150, 'postpaid' => 3100, 'non_taglist' => 980];
            $chartLabels = ['Mitra Sejahtera', 'Global Sinergi', 'Nusa Raya', 'Indo Sentosa', 'Prima Karya'];
            $chartDataPrepaid    = [380, 290, 210, 150, 90];
            $chartDataPostpaid   = [310, 410, 280, 180, 120];
            $chartDataNonTaglist = [290, 320, 310, 220, 110];
            
            // --- MOCK DATA TABEL MITRA ---
            $listData = [
                ['kode' => 'MTS', 'nama' => 'Mitra Sejahtera', 'layanan' => 'Prepaid', 'lembar' => 45, 'tagihan' => '3.150.000', 'admin' => '45.000', 'total' => '3.195.000'],
                ['kode' => 'MTS', 'nama' => 'Mitra Sejahtera', 'layanan' => 'Postpaid', 'lembar' => 32, 'tagihan' => '2.400.000', 'admin' => '32.000', 'total' => '2.432.000'],
                ['kode' => 'GBS', 'nama' => 'Global Sinergi', 'layanan' => 'Non-Taglist', 'lembar' => 15, 'tagihan' => '1.100.000', 'admin' => '15.000', 'total' => '1.115.000'],
            ];
        } else {
            $summary = ['prepaid' => 3241, 'postpaid' => 2832, 'non_taglist' => 1240];
            $chartLabels = ['Jatelindo Perkasa Abadi', 'Value Stream International', 'Gerbang Sinergi Prima', 'Saran Yukti Bandhana', 'Magna Karsa Mulya'];
            $chartDataPrepaid    = [450, 320, 245, 190, 65];
            $chartDataPostpaid   = [295, 425, 295, 168, 140];
            $chartDataNonTaglist = [345, 348, 348, 248, 140];

            // --- MOCK DATA TABEL IT PROVIDER (Sesuai Gambar Figma) ---
            $listData = [
                ['kode' => 'JPA', 'nama' => 'Jatelindo Perkasa Abadi', 'layanan' => 'Prepaid', 'lembar' => 30, 'tagihan' => '2.100.000', 'admin' => '30.000', 'total' => '2.130.000'],
                ['kode' => 'JPA', 'nama' => 'Jatelindo Perkasa Abadi', 'layanan' => 'Postpaid', 'lembar' => 30, 'tagihan' => '2.100.000', 'admin' => '30.000', 'total' => '2.130.000'],
                ['kode' => 'JPA', 'nama' => 'Jatelindo Perkasa Abadi', 'layanan' => 'Non-Taglist', 'lembar' => 30, 'tagihan' => '2.100.000', 'admin' => '30.000', 'total' => '2.130.000'],
                ['kode' => 'VSI', 'nama' => 'Value Stream International', 'layanan' => 'Prepaid', 'lembar' => 12, 'tagihan' => '1.300.000', 'admin' => '21.000', 'total' => '1.321.000'],
                ['kode' => 'VSI', 'nama' => 'Value Stream International', 'layanan' => 'Postpaid', 'lembar' => 12, 'tagihan' => '1.300.000', 'admin' => '21.000', 'total' => '1.321.000'],
                ['kode' => 'VSI', 'nama' => 'Value Stream International', 'layanan' => 'Non-Taglist', 'lembar' => 12, 'tagihan' => '1.300.000', 'admin' => '21.000', 'total' => '1.321.000'],
                ['kode' => 'GSP', 'nama' => 'Gerbang Sinergi Prima', 'layanan' => 'Prepaid', 'lembar' => 20, 'tagihan' => '980.000', 'admin' => '20.000', 'total' => '1.000.000'],
                ['kode' => 'GSP', 'nama' => 'Gerbang Sinergi Prima', 'layanan' => 'Postpaid', 'lembar' => 20, 'tagihan' => '980.000', 'admin' => '20.000', 'total' => '1.000.000'],
                ['kode' => 'GSP', 'nama' => 'Gerbang Sinergi Prima', 'layanan' => 'Non-Taglist', 'lembar' => 20, 'tagihan' => '980.000', 'admin' => '20.000', 'total' => '1.000.000'],
                ['kode' => 'SYB', 'nama' => 'Sarana Yukti Bandhana', 'layanan' => 'Prepaid', 'lembar' => 8, 'tagihan' => '800.000', 'admin' => '8.000', 'total' => '808.000']
            ];
        }

        // Lempar ke View
        $this->view->activeTab = $activeTab;
        $this->view->summary = $summary;
        $this->view->listData = $listData;
        
        // Bungkus paket data grafik ke JSON agar mudah dibaca Chart.js
        $this->view->chartJson = json_encode([
            'labels' => $chartLabels,
            'prepaid' => $chartDataPrepaid,
            'postpaid' => $chartDataPostpaid,
            'non_taglist' => $chartDataNonTaglist
        ]);
    }
}