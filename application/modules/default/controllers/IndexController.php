<?php
class Default_IndexController extends App_Controller_Base
{
    protected $_dashboardSession;

    public function init()
    {
        Zend_Session::start();
        $this->_dashboardSession = new Zend_Session_Namespace('dashboard_filter');
    }
    public function indexAction()
    {
        date_default_timezone_set('Asia/Jakarta');

        $this->view->headTitle('Dashboard');
        $this->view->headScript()->appendFile($this->view->baseUrl('/assets/js/chart.umd.min.js'));
        $this->view->headScript()->appendFile($this->view->baseUrl('/assets/js/dashboard-chart.js'));
        $this->view->headScript()->appendFile($this->view->baseUrl('/assets/js/format-currency-compact.js'));
        $this->view->headScript()->appendFile($this->view->baseUrl('/assets/js/format-number.js'));

        $filterDate = isset($this->_dashboardSession->filterDate)
            ? (int) $this->_dashboardSession->filterDate
            : 1;
        $currentDate = ($filterDate === 1)
            ? date('Y-m-d')
            : date('Y-m-d', strtotime('-1 day'));
        $this->view->filterDate = $filterDate;
        $this->view->currentDate = App_Helper_Date::indonesia($currentDate);
        $this->view->currentHour = date('H').':00';
    }

    public function filterDateAction()
    {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $filterDate = $this->_getParam('filterDate');
        $this->_dashboardSession->filterDate = $filterDate ?? '1';

        return $this->jsonSuccess([]);

    }

    public function totalSummaryAction()
    {
        $service = $this->api();
        $filterDate = isset($this->_dashboardSession->filterDate)
            ? (int) $this->_dashboardSession->filterDate
            : 1;

        $currentFilter = $filterDate > 0 ? 'now' : 'yesterday';

        $response = $service->request(
            'POST',
            "/service/proxy/service/alias/row1-all-tp-{$currentFilter}",
            ["conf" => "ch_12_dev"]
        );

        return $this->jsonSuccess($response['msg'][0]);
    }

    public function summaryTransactionAverageAction()
    {
        $service = $this->api();
        $filterDate = isset($this->_dashboardSession->filterDate)
            ? (int) $this->_dashboardSession->filterDate
            : 1;

        $currentFilter = $filterDate > 0 ? 'now' : 'yesterday';

        $response = $service->request(
            'POST',
            "/service/proxy/service/alias/row2-all-tp-{$currentFilter}",
            ["conf" => "mis_ch_rekon"]
        );

        return $this->jsonSuccess($response['msg'][0]);
    }

    public function transactionChartAction()
    {
        $service = $this->api();
        $filterDate = isset($this->_dashboardSession->filterDate)
            ? (int) $this->_dashboardSession->filterDate
            : 1;

        $currentFilter = $filterDate > 0 ? 'now' : 'yesterday';

        $response = $service->request(
            'POST',
            "/service/proxy/service/alias/row3-all-tp-{$currentFilter}",
            ["conf" => "ch_12_dev"]
        );

        $result = Default_Model_DashboardChart::transform($response['msg']);

        return $this->jsonSuccess($result);
    }

    public function apiLogAction()
    {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $service = $this->api();
        $end = new DateTime();
        $start = clone $end;
        $start->modify('-1 minute');
        $response = $service->request(
            'POST',
            "/service/proxy/service/third-api",
            [
                "conf_key" => "monitoring_chart_mis",
                "path" => "events",
                "payload" => [
                    "start" => $start->format('Y-m-d H:i:s'),
                    "end" => $end->format('Y-m-d H:i:s')
                ]
            ]
        );

        return $this->jsonSuccess($response);
    }

    protected function getDashboardSession() {
        return new Zend_Session_Namespace('dashboard_filter');
    }
}