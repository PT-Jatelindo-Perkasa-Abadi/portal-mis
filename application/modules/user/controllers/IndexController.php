<?php

class User_IndexController extends App_Controller_Base
{
    public function indexAction()
    {
        $api = new App_Service_Api();
        $_ = $api->authorization();

        $limit = (int) $this->_getParam('limit', 10);
        $page  = (int) $this->_getParam('page', 1);

        $response = $api->request('POST', '/service/proxy/service/alias/get-all-user');

        $sessionToken = $this->currentUser()['session_token'];

        $levels = $api->request('POST', '/service/proxy/service/alias/get-levels', [$sessionToken]);
        $roles = $api->request('POST', '/service/proxy/service/alias/get-roles', [$sessionToken]);

        Zend_Debug::dump($levels);
        Zend_Debug::dump($roles);

        if (isset($response['code']) && $response['code'] == 200 && isset($response['msg']) && is_array($response['msg'])) {

            $paginator = Zend_Paginator::factory($response['msg']);

            $paginator->setItemCountPerPage($limit);
            $paginator->setCurrentPageNumber($page);

            $this->view->users = $paginator;
        } else {
            $this->view->users = [];
        }
    }

    // public function indexAction()
    // {
    //     $api = new App_Service_Api();
    //     $_ = $api->authorization();

    //     // 1. Ambil limit dan halaman paginator
    //     $limit = (int) $this->_getParam('limit', 10);
    //     $page  = (int) $this->_getParam('page', 1);

    //     // 2. Tangkap parameter filter URL dan bersihkan dari spasi liar
    //     $searchName  = trim((string) $this->_getParam('search', ''));
    //     $filterLevel = trim((string) $this->_getParam('level', ''));
    //     $filterRole  = trim((string) $this->_getParam('role', ''));
    //     $filterStatus= trim((string) $this->_getParam('status', ''));

    //     // 3. Ambil token session aktif
    //     $sessionToken = $this->currentUser()['session_token'];

    //     // 4. 🔥 VALIDASI MUTLAK: Jika tidak ada filter aktif, potong payload menjadi 1 parameter saja!
    //     if (empty($searchName) && empty($filterLevel) && empty($filterRole) && empty($filterStatus)) {
    //         // JALUR NORMAL (Pasti Sukses): Sesuai dengan spesifikasi awal API Go Anda
    //         $payloadList = [$sessionToken];
    //     } else {
    //         // JALUR PENCARIAN AKTIF: Mengirimkan parameter filter terisi
    //         $payloadList = [
    //             $sessionToken,
    //             $searchName,
    //             $filterLevel,
    //             $filterRole,
    //             ($filterStatus !== '') ? (int)$filterStatus : 1
    //         ];
    //     }

    //     // Tembak API utama list data user
    //     $response = $api->request('POST', '/service/proxy/service/alias/get-all-user', $payloadList);

    //     // 5. Hit API Master Data Pendukung Dropdown Filter Pencarian
    //     $levels = $api->request('POST', '/service/proxy/service/alias/get-levels', [$sessionToken]);
    //     $roles  = $api->request('POST', '/service/proxy/service/alias/get-roles', [$sessionToken]);

    //     // Lempar master data dropdown ke view index.phtml
    //     $this->view->levelsData = (isset($levels['code']) && $levels['code'] == 200 && isset($levels['msg'])) ? $levels['msg'] : [];
    //     $this->view->rolesData  = (isset($roles['code']) && $roles['code'] == 200 && isset($roles['msg'])) ? $roles['msg'] : [];

    //     // 6. Olah response list user ke Paginator tabel
    //     if (isset($response['code']) && $response['code'] == 200 && isset($response['msg']) && is_array($response['msg'])) {
    //         $paginator = Zend_Paginator::factory($response['msg']);
    //         $paginator->setItemCountPerPage($limit);
    //         $paginator->setCurrentPageNumber($page);

    //         $this->view->users = $paginator;
    //     } else {
    //         $this->view->users = [];
    //     }
    // }

    public function detailAction()
    {
        $api = new App_Service_Api();

        $sessionData = $api->authorization();

        $sessionToken = '';
        if (is_array($sessionData)) {
            if (isset($sessionData['access_token'])) {
                $sessionToken = $sessionData['access_token'];
            } elseif (isset($sessionData['session'])) {
                $sessionToken = $sessionData['session'];
            } elseif (isset($sessionData['token'])) {
                $sessionToken = $sessionData['token'];
            }
        }

        if (empty($sessionToken) && isset($sessionData['msg']['access_token'])) {
            $sessionToken = $sessionData['msg']['access_token'];
        }

        $idUser = (int) $this->_getParam('id', 0);

        $payload = [
            $idUser,
            // $this->currentUserId(),
            $this->currentUser()['session_token'],
        ];

        $response = $api->request('POST', '/service/proxy/service/alias/get-user-detail', $payload);

        $userDetail = null;

        if (isset($response['code']) && $response['code'] == 200 && isset($response['msg']) && is_array($response['msg'])) {
            $userDetail = isset($response['msg'][0]) ? $response['msg'][0] : null;
        }

        if ($userDetail !== null) {
            if (isset($userDetail['is_blocked']) && $userDetail['is_blocked'] == 1) {
                $userDetail['status'] = 'Blokir';
            } elseif (isset($userDetail['is_active']) && $userDetail['is_active'] == 1) {
                $userDetail['status'] = 'Aktif';
            } else {
                $userDetail['status'] = 'Non-Aktif';
            }
        }

        if ($userDetail !== null) {
            $userSession = new Zend_Session_Namespace('UserDetailCache');
            $userSession->data = $userDetail;
        }

        $this->view->userDetail = $userDetail;
    }

    public function createAction()
    {
        $api = new App_Service_Api();

        $api->authorization();

        $sessionToken = $this->currentUser()['session_token'];
        $payload = [$sessionToken];

        $responseRoles = $api->request('POST', '/service/proxy/service/alias/get-roles', $payload);

        if ($responseRoles['msg'][0]['ERROR'] == 'Invalid or expired session') {
            return $this->_redirect('/auth/logout');
        }

        $rolesData = [];
        if (isset($responseRoles['code']) && $responseRoles['code'] == 200 && isset($responseRoles['msg'])) {
            $rolesData = $responseRoles['msg'];
        }

        $this->view->listRoles = $rolesData;
    }

    public function saveAction()
    {
        if ($this->_request->isPost()) {
            $api = new App_Service_Api();

            $sessionData = $api->authorization();

            $sessionToken = '';
            if (is_array($sessionData)) {
                if (isset($sessionData['access_token'])) {
                    $sessionToken = $sessionData['access_token'];
                } elseif (isset($sessionData['session'])) {
                    $sessionToken = $sessionData['session'];
                } elseif (isset($sessionData['token'])) {
                    $sessionToken = $sessionData['token'];
                }
            }

            if (App_Service_Session::getExpiredFlag()) {
                $this->view->errorMessage = 'Session expired, silakan login kembali';
            }

            $fullName  = (string)$this->_getParam('fullName', '');
            $email     = (string)$this->_getParam('email', '');
            $levelUser = (int)$this->_getParam('level_user', 1);
            $roleValue = (int)$this->_getParam('role', 1);

            $defaultPassword = "Biller123!";
            $passwordHash    = hash('sha256', $defaultPassword);

            $ipAddress = $this->_request->getServer('REMOTE_ADDR', '127.0.0.1');
            if ($ipAddress === '::1') {
                $ipAddress = '127.0.0.1';
            }
            $userAgent = "google chrome";

            $payload = [
                $email,
                $passwordHash,
                $fullName,
                $levelUser,
                $this->currentUserId(),
                $ipAddress,
                $userAgent,
                $this->currentUser()['session_token'],
                $roleValue,
                "@p_user_id",
                "@p_reset_token"
            ];

            $response = $api->request('POST', '/service/proxy/service/alias/create-user', $payload);

            if (isset($response['code']) && $response['code'] == 200) {

                if (isset($response['msg'][0]['ERROR'])) {
                    $this->_helper->json([
                        'success' => false,
                        'code'    => 400,
                        'msg'     => $response['msg'][0]['ERROR']
                    ]);
                    exit;
                }

                $this->_helper->json([
                    'success' => true,
                    'code'    => 200,
                    'msg'     => 'User berhasil ditambahkan.'
                ]);
                exit;
            } else {
                $msgError = isset($response['msg']) ? $response['msg'] : 'Gagal memproses data ke server backend.';
                $this->_helper->json([
                    'success' => false,
                    'code'    => isset($response['code']) ? $response['code'] : 404,
                    'msg'     => $msgError
                ]);
                exit;
            }
        }

        return $this->_helper->redirector->gotoUrl('user/index/index');
    }

    public function editAction()
    {
        // 1. Inisialisasi session cache internal
        $userSession = new Zend_Session_Namespace('UserDetailCache');
        $userDetail = null;

        // 2. Jika diakses dari jalur Detail -> Edit, ambil langsung dari session
        if (isset($userSession->data)) {
            $userDetail = $userSession->data;
        } else {
            // 3. 🔥 JALUR LANGSUNG (Klik Edit dari Halaman Utama): Berikan otentikasi global agar lolos 401
            $api = new App_Service_Api();
            $api->authorization(); // Sinkronisasi signature header AJAX / Direct Click

            // Ambil session token andalan Anda
            $sessionToken = $this->currentUser()['session_token'];
            $idUser = (int)$this->_getParam('id', 0);

            // Susun payload flat ke API detail
            $payload = [$idUser, $sessionToken];

            // Hit ke API Detail User Spesifik
            $response = $api->request('POST', '/service/proxy/service/alias/get-user-detail', $payload);

            if (isset($response['code']) && $response['code'] == 200 && isset($response['msg'][0])) {
                $userDetail = $response['msg'][0];
            }
        }

        // 4. Lempar data aman ke view edit.phtml
        $this->view->userDetail = $userDetail;
        $this->_helper->viewRenderer('edit');
    }

    public function updateAction()
    {
        if ($this->_request->isPost()) {
            $api = new App_Service_Api();

            $api->authorization();

            $idUser    = (int)$this->_getParam('id_user', 0);
            $fullName  = (string)$this->_getParam('fullName', '');
            $email     = (string)$this->_getParam('email', '');
            $levelUser = (int)$this->_getParam('level_user', 1);
            $roleValue = (int)$this->_getParam('role', 1);
            $statusRaw = $this->_getParam('status', '1');

            $isActive = ($statusRaw === '1') ? 1 : 0;

            $ipAddress = $this->_request->getServer('REMOTE_ADDR', '127.0.0.1');
            if ($ipAddress === '::1') {
                $ipAddress = '127.0.0.1';
            }
            $userAgent = "google chrome";

            $payload = [
                $idUser,
                $email,
                $fullName,
                $roleValue,
                $levelUser,
                $isActive,
                (int)$this->currentUserId(),
                $this->currentUser()['session_token'],
                $ipAddress,
                $userAgent
            ];

            $response = $api->request('POST', '/service/proxy/service/alias/update-user', $payload);

            $userSession = new Zend_Session_Namespace('UserDetailCache');
            if (isset($userSession->data)) {
                unset($userSession->data);
            }

            if (isset($response['code']) && $response['code'] == 200) {
                if (isset($response['msg'][0]['ERROR'])) {
                    $this->_helper->json([
                        'success' => false,
                        'code'    => 400,
                        'msg'     => $response['msg'][0]['ERROR']
                    ]);
                    exit;
                }

                $successMessage = isset($response['msg'][0]['message']) ? $response['msg'][0]['message'] : 'Data user berhasil diperbarui.';
                $this->_helper->json([
                    'success' => true,
                    'code'    => 200,
                    'msg'     => $successMessage
                ]);
                exit;
            } else {
                $backendRawMsg = isset($response['msg']) ? json_encode($response['msg']) : 'Gagal memproses perubahan data di server backend.';
                $this->_helper->json([
                    'success' => false,
                    'code'    => isset($response['code']) ? $response['code'] : 500,
                    'msg'     => $backendRawMsg
                ]);
                exit;
            }
        }

        return $this->_helper->redirector->gotoUrl('user/index/index');
    }

    public function sendMailAction()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->getResponse()->setHeader('Content-Type', 'application/json');

        try {
            $idUser    = (int)$this->_getParam('id_user', 0);
            $fullName  = (string)$this->_getParam('fullName', '');
            $email = trim($this->_getParam('email'));
            $roleValue = (int)$this->_getParam('role', 1);


            if (empty($email)) {
                throw new Exception('Email is required');
            }

            $api = new App_Service_Api();
            $_ = $api->authorization();

            $defaultPassword = "!#(@snb83";
            // $passwordHash    = hash('sha256', $defaultPassword);

            $payload = [
                'params' => [
                    $email,
                    App_Log_Context::getIp(),
                    App_Log_Context::getUserAgent(),
                ]
            ];

            $body = App_Service_EmailTemplate::render(
                'success_create',
                [
                    'misId' => $idUser,
                    'misName' => $fullName,
                    'misEmail' => $email,
                    'misRole' => $roleValue,
                    'misPassword' => $defaultPassword,
                ],
                'Kode OTP'
            );
            $emailPayload = [
                'to' => [$email],
                'subject' => 'sent Email',
                'body' => $body,
                'isHtml' => true
            ];

            $emailResponse = $api->request(
                'POST',
                '/service/email',
                $emailPayload
            );

            return $this->_helper->json([
                'success' => true,
                'message' => 'Email has been sent'
            ]);
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'USER_NOT_FOUND') !== false) {
                return $this->_helper->json([
                    'success' => true,
                    'message' => 'Email has been sent'
                ]);
            }

            return $this->_helper->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}
