<?php

class Reports_IndexController extends App_Controller_Base
{
    protected $_Model;

    public function init()
    {
        // 1. Wajib panggil parent::init() untuk menjalankan proteksi dasar
        parent::init();

        $this->_Model = new Reports_Model_Aps();

        // 2. Ambil session user
        $currentUser = App_Service_Session::get('user');

        // 3. Ambil session_token
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

        // 4. Validasi token session lokal
        if (empty($sessionToken)) {
            App_Service_Session::destroy();
            $this->_helper->redirector->gotoSimple('index', 'login', 'auth');
            return;
        }

        // 🛑 5. PING VALIDASI SESSION KE BACKEND (Pencegah Concurrent Login)
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
            // Ditangani interseptor global App_Service_Api
        }
    }

    public function indexAction()
    {
        $api = new App_Service_Api();
        $_ = $api->authorization();

        $sess = App_Service_Session::get('user');

        $payloadUser = [
            $sess['id'],
            $sess['session_token']
        ];

        $responseUser = $api->request('POST', '/service/proxy/service/alias/get-user-detail', $payloadUser);
        $listDataUser = isset($responseUser["msg"]) ? $responseUser["msg"] : [];

        /*** Khusus Level ITP ****/
        if (isset($listDataUser[0]["level_id"]) && $listDataUser[0]["level_id"] == '2') {
            $this->_redirect('/reports/index/mitrareport');
            exit;
        }

        $layanan = $this->_getParam('layanan', 'ALL');
        $this->view->selectedLayanan = $layanan;

        $activeTab = $this->_getParam('type', 'it-provider');

        $this->view->layanan = array(
            'ALL'         => 'Semua Layanan',
            'POSTPAID'    => 'Postpaid',
            'PREPAID'     => 'Prepaid',
            'NON_TAGLIST' => 'Non-Taglist'
        );

        $apiUrl   = '/service/proxy/service/alias/get-all-itp';
        $payload  = [];

        $response = $api->request('POST', $apiUrl, $payload);
        $this->view->listData = isset($response["msg"]) ? $response["msg"] : [];
    }

    public function listtransaksiAction()
    {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        $this->getResponse()->setHeader('Content-Type', 'application/json');

        try {
            $params = [];

            $params['draw']           = (int)$this->_getParam('draw', 1);
            $params['start']          = (int)$this->_getParam('start', 0);
            $params['length']         = (int)$this->_getParam('length', 10);
            $params['tanggal']        = $this->_getParam('tanggal', date('Y-m-d'));
            $params['it_provider']    = trim($this->_getParam('it_provider', ''));
            $params['layanan']        = trim($this->_getParam('layanan', ''));
            $params['keyword']        = trim($this->_getParam('keyword', ''));
            $params['mitra']          = trim($this->_getParam('mitra', ''));
            $params['order']          = $this->_getParam('order', '');

            $result = $this->_Model->getTransactionMitra($params);

            echo Zend_Json_Encoder::encode($result);
        } catch (Exception $e) {
            echo Zend_Json_Encoder::encode([
                "draw"            => 1,
                "recordsTotal"    => 0,
                "recordsFiltered" => 0,
                "data"            => [],
                "error"           => $e->getMessage()
            ]);
        }
        exit;
    }

    public function downloadAction()
    {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $params  = $this->getRequest()->getPost();
        $data    = $this->_Model->getTransactionMitraTotal($params);
        $summary = $this->calculateSummaryMitra($data);

        $this->_Model->generateExcelMitra($params, $summary, $data);
        exit;
    }

    public function downloadmitraAction() {}

    public function downloaditpmitraAction()
    {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $params  = $this->getRequest()->getPost();
        $data    = $this->_Model->getTransactionMitraTotal($params);
        $summary = $this->calculateSummaryMitra($data);

        $this->_Model->generateExcelMitra($params, $summary, $data);
        exit;
    }

    public function mitraAction()
    {
        $api = new App_Service_Api();
        $_ = $api->authorization();

        $layanan = $this->_getParam('layanan', 'ALL');
        $this->view->selectedLayanan = $layanan;

        $activeTab = $this->_getParam('type', 'it-provider');

        $this->view->layanan = array(
            'ALL'         => 'Semua Layanan',
            'POSTPAID'    => 'Postpaid',
            'PREPAID'     => 'Prepaid',
            'NON_TAGLIST' => 'Non-Taglist'
        );

        $apiUrl   = '/service/proxy/service/alias/get-all-itp';
        $payload  = [];

        $response = $api->request('POST', $apiUrl, $payload);
        $this->view->listData = isset($response["msg"]) ? $response["msg"] : [];

        $apiUrlPartner   = '/service/proxy/service/alias/get-all-partner';
        $payloadPartner  = [];

        $responsePartner = $api->request('POST', $apiUrlPartner, $payloadPartner);
        $this->view->listDataPartner = isset($responsePartner["msg"]) ? $responsePartner["msg"] : [];
    }

    public function itpreportAction()
    {
        $api = new App_Service_Api();
        $_ = $api->authorization();

        $layanan = $this->_getParam('layanan', 'ALL');
        $this->view->selectedLayanan = $layanan;

        $activeTab = $this->_getParam('type', 'it-provider');

        $this->view->layanan = array(
            'ALL'         => 'Semua Layanan',
            'POSTPAID'    => 'Postpaid',
            'PREPAID'     => 'Prepaid',
            'NON_TAGLIST' => 'Non-Taglist'
        );

        $sess = App_Service_Session::get('user');

        $payloadUser = [
            $sess['id'],
            $sess['session_token']
        ];

        $responseUser = $api->request('POST', '/service/proxy/service/alias/get-user-detail', $payloadUser);
        $listDataUser = isset($responseUser["msg"]) ? $responseUser["msg"] : [];

        $resjson = json_decode($listDataUser[0]["additional_info"] ?? '{}', true);
        $itpcode = strtolower($resjson["tp_code"] ?? '');

        $this->view->itpcode = strtoupper($itpcode);

        $apiUrl   = '/service/proxy/service/alias/get-partner-itp';
        $payload  = [$itpcode];

        $response = $api->request('POST', $apiUrl, $payload);
        $this->view->listData = isset($response["msg"]) ? $response["msg"] : [];

        $apiUrlPartner   = '/service/proxy/service/alias/get-all-partner';
        $payloadPartner  = [];

        $responsePartner = $api->request('POST', $apiUrlPartner, $payloadPartner);
        $this->view->listDataPartner = isset($responsePartner["msg"]) ? $responsePartner["msg"] : [];
    }

    public function mitrareportAction()
    {
        $api = new App_Service_Api();
        $_ = $api->authorization();

        $layanan = $this->_getParam('layanan', 'ALL');
        $this->view->selectedLayanan = $layanan;

        $activeTab = $this->_getParam('type', 'it-provider');

        $this->view->layanan = array(
            'ALL'         => 'Semua Layanan',
            'POSTPAID'    => 'Postpaid',
            'PREPAID'     => 'Prepaid',
            'NON_TAGLIST' => 'Non-Taglist'
        );

        $sess = App_Service_Session::get('user');

        $payloadUser = [
            $sess['id'],
            $sess['session_token']
        ];

        $responseUser = $api->request('POST', '/service/proxy/service/alias/get-user-detail', $payloadUser);
        $listDataUser = isset($responseUser["msg"]) ? $responseUser["msg"] : [];

        $resjson = json_decode($listDataUser[0]["additional_info"] ?? '{}', true);
        $itpcode = strtolower($resjson["tp_code"] ?? '');

        $this->view->itpcode = strtoupper($itpcode);

        $apiUrl   = '/service/proxy/service/alias/get-partner-itp';
        $payload  = [$itpcode];

        $response = $api->request('POST', $apiUrl, $payload);
        $this->view->listData = isset($response["msg"]) ? $response["msg"] : [];

        $apiUrlPartner   = '/service/proxy/service/alias/get-all-partner';
        $payloadPartner  = [];

        $responsePartner = $api->request('POST', $apiUrlPartner, $payloadPartner);
        $this->view->listDataPartner = isset($responsePartner["msg"]) ? $responsePartner["msg"] : [];
    }

    private function calculateSummaryMitra($data)
    {
        $summary = array(
            'total_lembar'   => 0,
            'total_tagihan'  => 0,
            'total_fee'      => 0,
            'total_nominal'  => 0
        );

        if (is_array($data)) {
            foreach ($data as $row) {
                $summary['total_lembar']  += (float) ($row['lembar'] ?? 0);
                $summary['total_tagihan'] += (float) ($row['sum_total_tagihan'] ?? 0);
                $summary['total_fee']     += (float) ($row['sum_total_fee'] ?? 0);
                $summary['total_nominal'] += (float) ($row['sum_total_nomial'] ?? 0);
            }
        }

        return $summary;
    }
}
