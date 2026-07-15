<?php

class User_IndexController extends App_Controller_Base
{
    public function indexAction()
    {
        $api = new App_Service_Api();
        $_ = $api->authorization();

        $limit = (int) $this->_getParam('limit', 10);
        $page = (int) $this->_getParam('page', 1);
        $level = (int) $this->_getParam('level');
        $role = (int) $this->_getParam('role');
        $status = $this->_getParam('status');
        $search = $this->_getParam('search');

        $sessionToken = $this->currentUser()['session_token'];

        $payload = [
            $sessionToken,
            $level ?: null,
            $role ?: null,
            $status ?: null
        ];

        $response = $api->request('POST', '/service/proxy/service/alias/get-all-user-filtered', $payload);

        if (isset($response['code']) && $response['code'] == 200 && isset($response['msg']) && is_array($response['msg'])) {

            $paginator = Zend_Paginator::factory($response['msg']);

            $paginator->setItemCountPerPage($limit);
            $paginator->setCurrentPageNumber($page);

            $this->view->users = $paginator;
        } else {
            $this->view->users = [];
        }


        $payload = [$sessionToken];
        $responseRoles = $api->request('POST', '/service/proxy/service/alias/get-roles', $payload);
        $responseLevel = $api->request('POST', '/service/proxy/service/alias/get-levels', $payload);

        if (isset($responseRoles['msg'][0]['ERROR']) && $responseRoles['msg'][0]['ERROR'] == 'Invalid or expired session') {
            return $this->_redirect('/auth/logout');
        }

        if (isset($responseLevel['msg'][0]['ERROR']) && $responseLevel['msg'][0]['ERROR'] == 'Invalid or expired session') {
            return $this->_redirect('/auth/logout');
        }

        $rolesData = [];
        if (isset($responseRoles['code']) && $responseRoles['code'] == 200 && isset($responseRoles['msg'])) {
            $rolesData = $responseRoles['msg'];
        }


        Zend_Debug::dump($rolesData);

        $this->view->rolesData = $rolesData;
        $this->view->listLevel = isset($responseLevel['msg']) && is_array($responseLevel['msg']) ? $responseLevel['msg'] : [];
        $this->view->listItp = $this->fetchItpList($api);
    }

    protected function fetchItpList(App_Service_Api $api)
    {
        $response = $api->request('POST', '/service/proxy/service/alias/get-all-itp');

        if (
            !isset($response['code']) || $response['code'] != 200
            || empty($response['msg']) || !is_array($response['msg'])
            || isset($response['msg'][0]['ERROR'])
        ) {
            return [];
        }

        $list = [];
        foreach ($response['msg'] as $row) {
            if (!is_array($row)) {
                continue;
            }

            $code = $row['technical_provider_code']
                ?? $row['tp_code']
                ?? $row['itp_code']
                ?? $row['code']
                ?? null;
            $name = $row['nama_technical_provider']
                ?? $row['tp_name']
                ?? $row['itp_name']
                ?? $row['name']
                ?? null;

            if ($code === null || $code === '') {
                continue;
            }

            $list[] = [
                'code' => (string) $code,
                'name' => ($name !== null && $name !== '') ? (string) $name : (string) $code,
            ];
        }

        return $list;
    }

    /**
     * tp_code from the user detail's additional_info JSON, or null.
     */
    protected function extractTpCode($userDetail)
    {
        if (empty($userDetail['additional_info'])) {
            return null;
        }

        $additional = json_decode($userDetail['additional_info'], true);

        return (is_array($additional) && isset($additional['tp_code']) && $additional['tp_code'] !== '')
            ? (string) $additional['tp_code']
            : null;
    }

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
        $this->view->listItp = $this->fetchItpList($api);
        $this->view->tpCode = $this->extractTpCode($userDetail);
    }

    public function createAction()
    {
        $api = new App_Service_Api();

        $api->authorization();

        $sessionToken = $this->currentUser()['session_token'];
        $payload = [$sessionToken];

        $responseRoles = $api->request('POST', '/service/proxy/service/alias/get-roles', $payload);
        $responseLevel = $api->request('POST', '/service/proxy/service/alias/get-levels', $payload);

        if (isset($responseRoles['msg'][0]['ERROR']) && $responseRoles['msg'][0]['ERROR'] == 'Invalid or expired session') {
            return $this->_redirect('/auth/logout');
        }

        if (isset($responseLevel['msg'][0]['ERROR']) && $responseLevel['msg'][0]['ERROR'] == 'Invalid or expired session') {
            return $this->_redirect('/auth/logout');
        }

        if ($responseLevel['msg'][0]['ERROR'] == 'Invalid or expired session') {
            return $this->_redirect('/auth/logout');
        }

        if ($listItp['msg'][0]['ERROR'] == 'Invalid or expired session') {
            return $this->_redirect('/auth/logout');
        }

        $rolesData = [];
        if (isset($responseRoles['code']) && $responseRoles['code'] == 200 && isset($responseRoles['msg'])) {
            $rolesData = $responseRoles['msg'];
        }

        $this->view->listRoles = $rolesData;
        $this->view->listLevel = isset($responseLevel['msg']) && is_array($responseLevel['msg']) ? $responseLevel['msg'] : [];
        $this->view->listItp = $this->fetchItpList($api);
    }

    public function saveAction()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->getResponse()->setHeader('Content-Type', 'application/json');

        if ($this->_request->isPost()) {
            $api = new App_Service_Api();
            $sessionData = $api->authorization();

            $fullName = (string) $this->_getParam('fullName', '');
            $email = trim((string) $this->_getParam('email', ''));
            $levelUser = (int) $this->_getParam('level_user', 1);
            $roleValue = (int) $this->_getParam('role', 1);
            $itpCode = trim((string) $this->_getParam('itp_code', ''));

            if (empty($email) || empty($fullName)) {
                return $this->_helper->json([
                    'success' => false,
                    'code' => 400,
                    'msg' => 'Nama dan Email wajib diisi!'
                ]);
            }

            $defaultPassword = "Biller123!";
            $passwordHash = hash('sha256', $defaultPassword);

            $ipAddress = $this->_request->getServer('REMOTE_ADDR', '127.0.0.1');
            if ($ipAddress === '::1') {
                $ipAddress = '127.0.0.1';
            }
            $userAgent = "google chrome";

            $dbPayload = [
                $email,
                $passwordHash,
                $fullName,
                $levelUser,
                $this->currentUserId(),
                $ipAddress,
                $userAgent,
                $this->currentUser()['session_token'],
                $roleValue,
                $itpCode,
                "@p_user_id",
                "@p_reset_token"
            ];

            $response = $api->request('POST', '/service/proxy/service/alias/create-user', $dbPayload);

            if (isset($response['code']) && $response['code'] == 200) {

                if (isset($response['msg'][0]['ERROR'])) {
                    return $this->_helper->json([
                        'success' => false,
                        'code' => 400,
                        'msg' => $response['msg'][0]['ERROR']
                    ]);
                }

                try {
                    $newUserId = 0;
                    if (isset($response['msg'][0]['id'])) {
                        $newUserId = $response['msg'][0]['id'];
                    } elseif (isset($response['msg']['id'])) {
                        $newUserId = $response['msg']['id'];
                    }

                    $roleDisplay = ($roleValue === 1) ? 'Administrator' : 'Viewer';
                    $loginUrl = $this->getBaseUrl() . '/auth/login';

                    // Render template .phtml
                    $body = App_Service_EmailTemplate::render(
                        'success_create',
                        [
                            'misId' => $newUserId,
                            'misName' => $fullName,
                            'misEmail' => $email,
                            'misRole' => $roleDisplay,
                            'misPassword' => $defaultPassword,
                            'misUrl' => $loginUrl
                        ],
                        'Kode OTP'
                    );

                    $emailPayload = [
                        'to' => [$email],
                        'subject' => 'Informasi Akun Baru - Portal MIS',
                        'body' => $body,
                        'isHtml' => true
                    ];

                    // Kirim ke API Email
                    $emailResponse = $api->request('POST', '/service/email', $emailPayload);

                    // Cetak log pengiriman email untuk pelacakan internal Anda
                    error_log("=== HIT EMAIL LOG ===");
                    error_log("EMAIL RESP: " . json_encode($emailResponse));
                } catch (Exception $e) {
                    // Jika gagal, log error asli akan tercetak di terminal server PHP Anda
                    error_log("CRITICAL ERROR DI BLOK EMAIL: " . $e->getMessage());
                }

                return $this->_helper->json([
                    'success' => true,
                    'code' => 200,
                    'msg' => 'User berhasil ditambahkan dan email kredensial telah dikirim.'
                ]);
            } else {
                $msgError = isset($response['msg']) ? $response['msg'] : 'Gagal memproses data ke server backend.';
                return $this->_helper->json([
                    'success' => false,
                    'code' => isset($response['code']) ? $response['code'] : 404,
                    'msg' => $msgError
                ]);
            }
        }

        return $this->_helper->redirector->gotoUrl('user/index/index');
    }

    // public function saveAction()
    // {
    //     // Pastikan respons dalam format JSON murni agar AJAX di HTML tidak crash
    //     $this->_helper->viewRenderer->setNoRender(true);
    //     $this->getResponse()->setHeader('Content-Type', 'application/json');

    //     if ($this->_request->isPost()) {
    //         try {
    //             $fullName  = (string)$this->_getParam('fullName', 'User Test');
    //             $email     = trim((string)$this->_getParam('email', ''));
    //             $roleValue = (int)$this->_getParam('role', 1);

    //             if (empty($email)) {
    //                 return $this->_helper->json(['success' => false, 'msg' => 'Email wajib diisi!']);
    //                 exit;
    //             }

    //             // 2. 🚧 BYPASS DATABASE: Kunci utama agar user tidak bertambah terus!
    //             // Kita langsung alihkan (forward) prosesnya ke fungsi sendMailAction
    //             // tanpa memanggil API /create-user ke database Go.
    //             return $this->_forward('send-mail', 'index', 'user', [
    //                 'id_user'  => 999, // Dummy ID untuk sekadar isi variabel template email
    //                 'fullName' => $fullName,
    //                 'email'    => $email,
    //                 'role'     => $roleValue
    //             ]);
    //         } catch (Exception $e) {
    //             return $this->_helper->json([
    //                 'success' => false,
    //                 'msg'     => 'Bypass system error: ' . $e->getMessage()
    //             ]);
    //             exit;
    //         }
    //     }

    //     return $this->_helper->redirector->gotoUrl('user/index/index');
    // }

    public function editAction()
    {
        $userSession = new Zend_Session_Namespace('UserDetailCache');
        $userDetail = null;

        $api = new App_Service_Api();
        $api->authorization();

        $idUser = (int) $this->_getParam('id', 0);

        $cacheId = isset($userSession->data['id']) ? (int)$userSession->data['id'] : (isset($userSession->data['id_user']) ? (int)$userSession->data['id_user'] : 0);

        if (isset($userSession->data) && $cacheId === $idUser && $idUser !== 0) {
            $userDetail = $userSession->data;
        } else {
            $sessionToken = $this->currentUser()['session_token'];

            $payload = [$idUser, $sessionToken];

            $response = $api->request('POST', '/service/proxy/service/alias/get-user-detail', $payload);

            if (isset($response['code']) && $response['code'] == 200 && isset($response['msg'][0])) {
                $userDetail = $response['msg'][0];
                $userSession->data = $userDetail;
            }
        }

        $sessionToken = $this->currentUser()['session_token'];
        $payloadData = [$sessionToken];

        $responseRoles = $api->request('POST', '/service/proxy/service/alias/get-roles', $payloadData);
        $responseLevel = $api->request('POST', '/service/proxy/service/alias/get-levels', $payloadData);

        if (isset($responseRoles['msg'][0]['ERROR']) && $responseRoles['msg'][0]['ERROR'] == 'Invalid or expired session') {
            return $this->_redirect('/auth/logout');
        }

        if (isset($responseLevel['msg'][0]['ERROR']) && $responseLevel['msg'][0]['ERROR'] == 'Invalid or expired session') {
            return $this->_redirect('/auth/logout');
        }

        $rolesData = [];
        if (isset($responseRoles['code']) && $responseRoles['code'] == 200 && isset($responseRoles['msg'])) {
            $rolesData = $responseRoles['msg'];
        }

        $this->view->userDetail = $userDetail;
        $this->view->listRoles = $rolesData;
        $this->view->listLevel = isset($responseLevel['msg']) && is_array($responseLevel['msg']) ? $responseLevel['msg'] : [];
        $this->view->listItp = $this->fetchItpList($api);
        $this->view->tpCode = $this->extractTpCode($userDetail);

        $this->_helper->viewRenderer('edit');
    }

    public function updateAction()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->getResponse()->setHeader('Content-Type', 'application/json');

        if ($this->_request->isPost()) {
            $api = new App_Service_Api();

            $api->authorization();

            $idUser = (int) $this->_getParam('id_user', 0);
            $fullName = (string) $this->_getParam('fullName', '');
            $email = (string) $this->_getParam('email', '');
            $levelUser = (int) $this->_getParam('level_user', 1);
            $roleValue = (int) $this->_getParam('role', 1);
            $itpCode = trim((string) $this->_getParam('itp_code', ''));
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
                (int) $this->currentUserId(),
                $this->currentUser()['session_token'],
                $ipAddress,
                $userAgent,
                $itpCode
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
                        'code' => 400,
                        'msg' => $response['msg'][0]['ERROR']
                    ]);
                    exit;
                }

                $successMessage = isset($response['msg'][0]['message']) ? $response['msg'][0]['message'] : 'Data user berhasil diperbarui.';
                $this->_helper->json([
                    'success' => true,
                    'code' => 200,
                    'msg' => $successMessage
                ]);
                exit;
            } else {
                $backendRawMsg = isset($response['msg']) ? json_encode($response['msg']) : 'Gagal memproses perubahan data di server backend.';
                $this->_helper->json([
                    'success' => false,
                    'code' => isset($response['code']) ? $response['code'] : 500,
                    'msg' => $backendRawMsg
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
            $idUser = (int) $this->_getParam('id_user', 0);
            $fullName = (string) $this->_getParam('fullName', '');
            $email = trim($this->_getParam('email'));
            $roleValue = (int) $this->_getParam('role', 1);
            $roleDisplay = ($roleValue === 1) ? 'Administrator' : 'Viewer';


            if (empty($email)) {
                throw new Exception('Email is required');
            }

            $api = new App_Service_Api();
            $_ = $api->authorization();

            $defaultPassword = "!#(@snb83";
            // $passwordHash    = hash('sha256', $defaultPassword);

            $payload = [
                $email,
                App_Log_Context::getIp(),
                App_Log_Context::getUserAgent(),
            ];


            $body = App_Service_EmailTemplate::render(
                'success_create',
                [
                    'misId' => $idUser,
                    'misName' => $fullName,
                    'misEmail' => $email,
                    'misRole' => $roleDisplay,
                    'misPassword' => $defaultPassword,
                ],
                'Kode OTP'
            );
            $emailPayload = [
                'to' => [$email],
                'subject' => 'Informasi Akun Baru - Portal MIS',
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

    public function resetPasswordAction()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->getResponse()->setHeader('Content-Type', 'application/json');

        if ($this->_request->isPost()) {
            $api = new App_Service_Api();
            $sessionData = $api->authorization();

            $idUser = (int) $this->_getParam('id', 0);
            $fullName = (string) $this->_getParam('fullName', '');
            $email = trim((string) $this->_getParam('email', ''));
            $roleValue = (int) $this->_getParam('role', 1);
            $roleDisplay = ($roleValue === 1) ? 'Administrator' : 'Viewer';

            if (empty($email)) {
                return $this->_helper->json([
                    'success' => false,
                    'code' => 400,
                    'msg' => 'Data email tidak ditemukan!'
                ]);
            }

            $newPassword = "!#(@snb83";
            $passwordHash = hash('sha256', $newPassword);

            $ipAddress = $this->_request->getServer('REMOTE_ADDR', '127.0.0.1');
            if ($ipAddress === '::1') {
                $ipAddress = '127.0.0.1';
            }
            $userAgent = "google chrome";

            $dbPayload = [
                $this->currentUser()['session_token'],
                $passwordHash,
                $email,
                $ipAddress,
                $userAgent
            ];

            $response = $api->request('POST', '/service/proxy/service/alias/admin-change-password', $dbPayload);

            if (isset($response['code']) && $response['code'] == 200) {

                if (isset($response['msg'][0]['ERROR'])) {
                    return $this->_helper->json([
                        'success' => false,
                        'code' => 400,
                        'msg' => $response['msg'][0]['ERROR']
                    ]);
                }

                try {
                    $realIdUser = isset($response['msg'][0]['user_id']) ? $response['msg'][0]['user_id'] : $idUser;

                    $loginUrl = $this->getBaseUrl() . '/auth/login';

                    $body = App_Service_EmailTemplate::render(
                        'reset_password_admin',
                        [
                            'misId' => $realIdUser,
                            'misName' => $fullName,
                            'misEmail' => $email,
                            'misRole' => $roleDisplay,
                            'misPassword' => $newPassword,
                            'misUrl' => $loginUrl
                        ],
                        'Reset Kata Sandi'
                    );

                    $emailPayload = [
                        'to' => [$email],
                        'subject' => 'Pemberitahuan Perubahan Kata Sandi - Portal MIS',
                        'body' => $body,
                        'isHtml' => true
                    ];

                    $api->request('POST', '/service/email', $emailPayload);
                } catch (Exception $e) {
                    error_log("Gagal memproses notifikasi email reset password: " . $e->getMessage());
                }

                return $this->_helper->json([
                    'success' => true,
                    'code' => 200,
                    'msg' => 'Kata sandi berhasil disetel ulang dan email notifikasi telah dikirim.'
                ]);
            } else {
                $msgError = isset($response['msg']) ? $response['msg'] : 'Gagal memproses reset password ke server backend.';
                return $this->_helper->json([
                    'success' => false,
                    'code' => isset($response['code']) ? $response['code'] : 404,
                    'msg' => $msgError
                ]);
            }
        }

        return $this->_helper->redirector->gotoUrl('user/index/index');
    }
}
