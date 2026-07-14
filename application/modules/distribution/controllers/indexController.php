<?php // application/modules/distribution/controllers/IndexController.php

class Distribution_IndexController extends Zend_Controller_Action
{
    
    public function indexAction()
    {
        $api = new App_Service_Api();
        $_ = $api->authorization();

        $activeTab = $this->_getParam('type', 'it-provider');

        date_default_timezone_set('Asia/Jakarta');
        $today = date('Y-m-d');

        $filterDate    = $this->_getParam('date', $today);
        $filterService = $this->_getParam('service', '');
        $filterSort    = $this->_getParam('sort_by', 'total');

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

        if ($activeTab === 'mitra') {
            $apiUrl = '/service/proxy/service/alias/get-distribution-mitra';
        } else {
            $apiUrl = '/service/proxy/service/alias/get-distribution-itp';
        }

        $response = $api->request('POST', $apiUrl, $payload);
        $listData = isset($response["msg"]) ? $response["msg"] : [];

        $summary = ['prepaid' => 0, 'postpaid' => 0, 'non_taglist' => 0];
        $chartLabels = [];
        $chartDataPrepaid = [];
        $chartDataPostpaid = [];
        $chartDataNonTaglist = [];

        $processedChartData = [];
        if (is_array($listData)) {
            foreach ($listData as $value) {
                if (isset($value["kode"])) {
                    $processedChartData[$value["kode"]] = [
                        'nama'       => isset($value["nama"]) ? $value["nama"] : $value["kode"],
                        'prepaid'    => 0,
                        'postpaid'   => 0,
                        'nontaglist' => 0
                    ];
                }
            }

            foreach ($listData as $value) {
                if (isset($value["kode"])) {
                    $nilaiLembar = isset($value['lembar']) ? $value['lembar'] : (isset($value['jumlah']) ? $value['jumlah'] : 0);

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

                $chartDataPrepaid[]    = $value["prepaid"];
                $chartDataPostpaid[]   = $value["postpaid"];
                $chartDataNonTaglist[] = $value["nontaglist"];

                $summary["prepaid"]     += $value["prepaid"];
                $summary["postpaid"]    += $value["postpaid"];
                $summary["non_taglist"] += $value["nontaglist"];
            }
        }

        // Kirim data penampung kembali ke View UI
        $this->view->activeTab = $activeTab;
        $this->view->summary   = $summary;
        $this->view->listData  = $listData;
        $this->view->filters   = [
            'date'    => $filterDate,
            'service' => $filterService,
            'sort_by' => $filterSort
        ];

        $this->view->chartJson = json_encode([
            'labels'      => $chartLabels,
            'prepaid'     => $chartDataPrepaid,
            'postpaid'    => $chartDataPostpaid,
            'non_taglist' => $chartDataNonTaglist
        ]);
    }
}
