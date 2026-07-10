<?php
class Default_IndexController extends App_Controller_Base
{
    public function init()
    {
        Zend_Session::start();
    }
    public function indexAction()
    {
        $this->view->headTitle('Dashboard');
        $this->view->headScript()->appendFile($this->view->baseUrl('/assets/js/chart.umd.min.js'));
        $this->view->headScript()->appendFile($this->view->baseUrl('/assets/js/dashboard-chart.js'));
        $this->view->headScript()->appendFile($this->view->baseUrl('/assets/js/format-currency-compact.js'));
        $this->view->headScript()->appendFile($this->view->baseUrl('/assets/js/format-number.js'));
    }

    public function totalSummaryAction() {
        $service = $this->api();
        $response = $service->request(
            'POST',
            '/service/proxy/service/alias/row1-all-tp-now',
            ["conf" => "ch_12_dev"]
        );

        return $this->jsonSuccess($response['msg'][0]);
    }

    public function summaryTransactionAverageAction() {
        $service = $this->api();
        $response = $service->request(
            'POST',
            '/service/proxy/service/alias/row2-all-tp-yesterday',
            ["conf" => "mis_ch_rekon"]
        );

        return $this->jsonSuccess($response['msg'][0]);
    }

    public function transactionChartAction() {
        $isDay = $this->_getParam('currentDate');
        $service = $this->api();
        $response = [];

        if ($isDay == '1') {
            $response = $service->request(
                'POST',
                '/service/proxy/service/alias/row3-all-tp-now',
                ["conf" => "ch_12_dev"]
            );
        }

        if ($isDay == '0') {
            $response = $service->request(
                'POST',
                '/service/proxy/service/alias/row3-all-tp-yesterday',
                ["conf" => "ch_12_dev"]
            );
        }

        Zend_Debug::dump($response);
        exit;

        return $this->jsonSuccess($response['msg'][0]);
    }
}