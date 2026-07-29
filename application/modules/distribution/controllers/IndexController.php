<?php

class Distribution_IndexController extends Zend_Controller_Action
{
    public function indexAction()
    {
        $this->view->isError = false;
        $this->view->currentUser = App_Service_Session::get('user');

        $activeTab = $this->_getParam('type', 'it-provider');

        date_default_timezone_set('Asia/Jakarta');
        $today = date('Y-m-d');

        $rawDateInput = $this->_getParam('date', '');
        $filterDate   = !empty($rawDateInput) ? $rawDateInput : $today;

        if (strpos($filterDate, '/') !== false) {
            $parts = explode('/', $filterDate);
            if (count($parts) == 3) {
                $filterDate = $parts[2] . '-' . $parts[1] . '-' . $parts[0];
            }
        }

        $filterService = $this->_getParam('service', '');
        $filterSort    = $this->_getParam('sort_by', 'total');
        $filterSearch  = $this->_getParam('search', '');

        $modelAps = new Distribution_Model_Aps();

        $result = $modelAps->getDistributionData($activeTab, $filterDate, $filterService, $filterSort);
        $this->view->isError = !$result['success'];
        $listData = $result['data'];

        $listData = $modelAps->filterSearchData($listData, $filterSearch);

        $isDownload = $this->_getParam('download', 'false');
        if ($isDownload === 'true') {
            $this->_helper->layout->disableLayout();
            $this->_helper->viewRenderer->setNoRender(true);

            $modelAps->exportExcel($listData, $activeTab, $filterDate);
            exit;
        }

        $chartSummary = $modelAps->processChartAndSummary($listData);

        $this->view->activeTab = $activeTab;
        $this->view->summary   = $chartSummary['summary'];
        $this->view->chartJson = $chartSummary['chartJson'];
        $this->view->listData  = $listData;

        $this->view->filters = [
            'date'    => $filterDate,
            'service' => $filterService,
            'sort_by' => $filterSort,
            'search'  => $filterSearch
        ];
    }

    // public function indexAction()
    // {
    //     // testing Error
    //     $this->view->isError   = true;
    //     $this->view->activeTab = $this->_getParam('type', 'it-provider');
    //     $this->view->listData  = [];
    //     $this->view->summary   = ['prepaid' => 0, 'postpaid' => 0, 'non_taglist' => 0];

    //     $rawDateInput  = $this->_getParam('date', '');
    //     $filterService = $this->_getParam('service', '');
    //     $filterSort    = $this->_getParam('sort_by', 'total');
    //     $filterSearch  = $this->_getParam('search', '');

    //     $this->view->filters = [
    //         'date'    => $rawDateInput,
    //         'service' => $filterService,
    //         'sort_by' => $filterSort,
    //         'search'  => $filterSearch
    //     ];

    //     $this->view->chartJson = json_encode([
    //         'labels'      => [],
    //         'prepaid'     => [],
    //         'postpaid'    => [],
    //         'non_taglist' => []
    //     ]);

    //     return;
    // }
}
