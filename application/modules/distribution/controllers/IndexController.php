<?php

class Distribution_IndexController extends App_Controller_Base
{

    public function indexAction()
    {
        $this->view->isError = false;

        $currentUser = App_Service_Session::get('user');
        $this->view->currentUser = $currentUser;

        $userLevel = strtolower(trim($currentUser['level'] ?? ''));
        $isItpUser = ($userLevel === 'it provider' || $userLevel === 'mitra acquirer');

        $defaultParam = $isItpUser ? 'sub-mitra-acquirer' : 'mitra-acquirer';
        $rawTypeParam = $this->_getParam('type', $defaultParam);

        if ($rawTypeParam === 'it-provider' || $rawTypeParam === 'mitra-acquirer') {
            $activeTab = 'mitra-acquirer';
        } else {
            $activeTab = 'sub-mitra-acquirer';
        }

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
        $filterMitra   = $this->_getParam('mitra_code', '');

        $modelAps = new Distribution_Model_Aps();

        $result = $modelAps->getDistributionData($activeTab, $filterDate, $filterService, $filterSort);
        $this->view->isError = !$result['success'];
        $listData = $result['data'];

        $this->view->listMitraAcquirer = $modelAps->getMasterMitraList();

        $listData = $modelAps->filterSearchData($listData, $filterSearch, $filterMitra);

        $isDownload = $this->_getParam('download', 'false');
        if ($isDownload === 'true') {
            $this->_helper->layout->disableLayout();
            $this->_helper->viewRenderer->setNoRender(true);

            $modelAps->exportExcel($listData, $activeTab, $filterDate);
            exit;
        }

        $chartSummary = $modelAps->processChartAndSummary($listData);

        $isUserFiltering = !empty($filterSearch) || !empty($filterService) || !empty($filterMitra);

        $this->view->isUserFiltering = $isUserFiltering;
        $this->view->activeTab       = $activeTab;
        $this->view->summary         = $chartSummary['summary'];
        $this->view->chartJson       = $chartSummary['chartJson'];
        $this->view->listData        = $listData;

        $this->view->filters = [
            'date'       => $filterDate,
            'service'    => $filterService,
            'sort_by'    => $filterSort,
            'search'     => $filterSearch,
            'mitra_code' => $filterMitra
        ];
    }
}
