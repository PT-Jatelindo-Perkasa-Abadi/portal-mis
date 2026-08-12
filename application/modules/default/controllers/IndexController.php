<?php

class Default_IndexController extends App_Controller_Base
{
    protected $_dashboardSession;

    public function init()
    {
        parent::init();

        // 1. Ambil session user via class App_Service_Session
        $currentUser = App_Service_Session::get('user');

        // 2. Pembacaan session_token yang fleksibel dan aman
        $sessionToken = null;

        if (is_array($currentUser) && !empty($currentUser['session_token'])) {
            $sessionToken = $currentUser['session_token'];
        } elseif (is_object($currentUser) && !empty($currentUser->session_token)) {
            $sessionToken = $currentUser->session_token;
        } else {
            $userSession = new Zend_Session_Namespace('UserDetailCache');
            if (isset($userSession->data['session_token'])) {
                $sessionToken = $userSession->data['session_token'];
            } elseif (isset($userSession->session_token)) {
                $sessionToken = $userSession->session_token;
            } elseif (isset($_SESSION['session_token'])) {
                $sessionToken = $_SESSION['session_token'];
            }
        }

        // 3. Validasi token session lokal
        if (empty($sessionToken)) {
            App_Service_Session::destroy();
            $this->_helper->redirector->gotoSimple('index', 'login', 'auth');
            return;
        }

        // 🛑 4. PING VALIDASI SESSION KE BACKEND (Proteksi Concurrent Login)
        // Memaksa verifikasi ketersediaan session_token di database backend.
        // Jika Device 2 sudah login, panggilan ini otomatis memicu auto-logout via App_Service_Api.
        try {
            $api = new App_Service_Api();
            $api->authorization();
            $response = $api->request('POST', '/service/proxy/service/alias/get-acl-config', [$sessionToken]);

            $rawMsg = '';
            if (isset($response['msg'])) {
                if (is_string($response['msg'])) {
                    $rawMsg = $response['msg'];
                } elseif (is_array($response['msg'])) {
                    $rawMsg = $response['msg'][0]['ERROR'] ?? ($response['msg']['ERROR'] ?? '');
                }
            }

            if (
                strpos(strtolower($rawMsg), 'invalid') !== false ||
                strpos(strtolower($rawMsg), 'expired') !== false ||
                strpos(strtolower($rawMsg), 'session') !== false
            ) {
                App_Service_Session::destroy();
                $this->_helper->redirector->gotoSimple('index', 'login', 'auth');
                return;
            }
        } catch (Exception $e) {
            // Error session invalid ditangani oleh interseptor App_Service_Api
        }

        // 5. Inisialisasi namespace session dashboard
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
        $this->view->currentHour = date('H') . ':00';
        $this->view->currentUser = $this->currentUser();
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

        $request = $this->buildDashboardRequest(
            'row1',
            $filterDate,
            'ch_12_dev',
            [strtoupper($this->currentUser()['tp_code'] ?? '')]
        );

        $response = $service->request(
            'POST',
            $request['endpoint'],
            $request['payload']
        );

        $data = $response['msg'][0] ?? [];

        return $this->jsonSuccess($data);
    }

    public function summaryTransactionAverageAction()
    {
        $service = $this->api();
        $filterDate = isset($this->_dashboardSession->filterDate)
            ? (int) $this->_dashboardSession->filterDate
            : 1;

        $tpCode = $this->currentUser()['tp_code'] ?? '';

        $request = $this->buildDashboardRequest(
            'row2',
            $filterDate,
            'mis_ch_rekon',
            array_fill(0, 4, $tpCode)
        );

        $response = $service->request(
            'POST',
            $request['endpoint'],
            $request['payload']
        );

        $data = $response['msg'][0] ?? [];

        return $this->jsonSuccess($data);
    }

    public function transactionChartAction()
    {
        $service = $this->api();
        $filterDate = isset($this->_dashboardSession->filterDate)
            ? (int) $this->_dashboardSession->filterDate
            : 1;

        $tpCode = $this->currentUser()['tp_code'] ?? '';

        $request = $this->buildDashboardRequest(
            'row3',
            $filterDate,
            'ch_12_dev',
            [$tpCode]
        );

        $response = $service->request(
            'POST',
            $request['endpoint'],
            $request['payload']
        );

        $msgData = isset($response['msg']) && is_array($response['msg']) ? $response['msg'] : [];
        $result  = Default_Model_TransactionTransformer::transform($msgData);

        return $this->jsonSuccess($result);
    }

    public function latencyChartAction()
    {
        $service = $this->api();
        $filterDate = isset($this->_dashboardSession->filterDate)
            ? (int) $this->_dashboardSession->filterDate
            : 1;

        $currentFilter = $filterDate > 0 ? 'today' : 'yesterday';

        $response = $service->request(
            'POST',
            "/service/proxy/service/alias/row4-latency-{$currentFilter}",
            ["payload" => []]
        );

        $msgData = isset($response['msg']) && is_array($response['msg']) ? $response['msg'] : [];
        $result  = Default_Model_LatencyTransformer::transform($msgData);

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

    private function buildDashboardRequest($alias, $filterDate, $conf, array $params)
    {
        $period = ((int) $filterDate === 1) ? 'now' : 'yesterday';
        $tpCode = strtoupper(trim((string) ($this->currentUser()['tp_code'] ?? '')));
        $scope = 'all';

        if ($tpCode !== '') {
            $scope = 'in';

            $params = array_fill(0, count($params), $tpCode);
        }

        return [
            'endpoint' => "/service/proxy/service/alias/{$alias}-{$scope}-tp-{$period}",
            'payload' => [
                'conf' => $conf,
                'params' => $params,
            ],
        ];
    }
}
