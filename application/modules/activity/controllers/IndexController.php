<?php

class Activity_IndexController extends App_Controller_Base
{
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

        try {
            $api = new App_Service_Api();
            $api->authorization();
            $response = $api->request('POST', '/service/proxy/service/alias/get-acl-config', [$sessionToken]);

            // Pengecekan manual jika interseptor global belum mengeksekusi exit
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
            // Error ditangani langsung oleh App_Service_Api / redirector
        }

        $this->view->headLink()->appendStylesheet(
            $this->view->baseUrl('/assets/css/activity.css')
        );
    }

    public function indexAction()
    {
        $api = new App_Service_Api();
        $api->authorization();

        $currentUser  = App_Service_Session::get('user');
        $sessionToken = is_array($currentUser) ? ($currentUser['session_token'] ?? '') : '';

        $payload = [
            $sessionToken
        ];

        $responseLevel = $api->request('POST', '/service/proxy/service/alias/get-levels', $payload);
        $listDataLevel = isset($responseLevel["msg"]) && is_array($responseLevel["msg"]) ? $responseLevel["msg"] : [];
        $this->view->listDatalevel = $listDataLevel;

        $responseRoles = $api->request('POST', '/service/proxy/service/alias/get-roles', $payload);
        $listDataRoles = isset($responseRoles["msg"]) && is_array($responseRoles["msg"]) ? $responseRoles["msg"] : [];
        $this->view->listDataRoles = $listDataRoles;
    }

    public function listAction()
    {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $this->getResponse()->setHeader('Content-Type', 'application/json');

        try {
            $currentUser  = App_Service_Session::get('user');
            $sessionToken = is_array($currentUser) ? ($currentUser['session_token'] ?? '') : '';

            $api = new App_Service_Api();
            $api->authorization();

            $draw   = (int) $this->_getParam('draw', 1);
            $start  = (int) $this->_getParam('start', 0);
            $length = (int) $this->_getParam('length', 10);

            $tanggal   = $this->_getParam('tanggal', date('Y-m-d'));
            $leveluser = trim((string) $this->_getParam('leveluser', ''));
            $roleuser  = trim((string) $this->_getParam('roleuser', ''));
            $keyword   = trim((string) $this->_getParam('keyword', ''));

            $columns = [
                0 => 'No',
                1 => 'created_at',
                2 => 'full_name',
                3 => 'email',
                4 => 'level_name',
                5 => 'role_name',
                6 => 'menu',
                7 => 'browser',
                8 => 'deskripsi'
            ];

            $orderParam = $this->_getParam('order');
            $orderIndex = isset($orderParam[0]['column']) ? (int) $orderParam[0]['column'] : 1;
            $orderDir   = isset($orderParam[0]['dir']) ? strtoupper($orderParam[0]['dir']) : 'DESC';

            if (!in_array($orderDir, ['ASC', 'DESC'])) {
                $orderDir = 'DESC';
            }

            $orderBy = isset($columns[$orderIndex]) ? $columns[$orderIndex] : 'created_at';

            $payloadT = [$sessionToken, $tanggal, $roleuser, $leveluser, $keyword, $orderBy, $orderDir, $length, $start];

            $apiUrlT   = '/service/proxy/service/alias/get-view-total-auditlog';
            $responseT = $api->request('POST', $apiUrlT, $payloadT);

            $apiUrlN   = '/service/proxy/service/alias/get-view-auditlog';
            $responseN = $api->request('POST', $apiUrlN, $payloadT);

            // 🛑 5. Validasi jika respon API backend mengembalikan error session expired
            $errT = $responseT['msg'][0]['ERROR'] ?? ($responseT['msg'] ?? '');
            $errN = $responseN['msg'][0]['ERROR'] ?? ($responseN['msg'] ?? '');

            if (
                (is_string($errT) && strpos(strtolower($errT), 'session') !== false) ||
                (is_string($errN) && strpos(strtolower($errN), 'session') !== false)
            ) {
                App_Service_Session::destroy();
                $this->getResponse()->setHttpResponseCode(401);
                echo Zend_Json::encode([
                    "draw"            => $draw,
                    "recordsTotal"    => 0,
                    "recordsFiltered" => 0,
                    "data"            => [],
                    "error"           => "Session expired"
                ]);
                exit;
            }

            $recordsTotal    = isset($responseT['msg'][0]['recordsTotal']) ? (int) $responseT['msg'][0]['recordsTotal'] : 0;
            $recordsFiltered = isset($responseT['msg'][0]['recordsFiltered']) ? (int) $responseT['msg'][0]['recordsFiltered'] : 0;
            $dataList        = isset($responseN['msg']) && is_array($responseN['msg']) ? $responseN['msg'] : [];

            echo Zend_Json::encode([
                "draw"            => $draw,
                "recordsTotal"    => $recordsTotal,
                "recordsFiltered" => $recordsFiltered,
                "data"            => $dataList
            ]);
        } catch (Exception $e) {
            echo Zend_Json::encode([
                "draw"            => 1,
                "recordsTotal"    => 0,
                "recordsFiltered" => 0,
                "data"            => [],
                "message"         => $e->getMessage()
            ]);
        }
        exit;
    }
}
