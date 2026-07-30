<?php

class User_IndexController extends App_Controller_Base
{
    protected $_modelUser;

    public function init()
    {
        parent::init();
        $this->_modelUser = new User_Model_Aps();
    }

    public function indexAction()
    {
        $limit  = (int) $this->_getParam('limit', 10);
        $page   = (int) $this->_getParam('page', 1);
        $level  = (int) $this->_getParam('level');
        $role   = (int) $this->_getParam('role');
        $status = $this->_getParam('status');
        $search = trim($this->_getParam('search', ''));

        $sessionToken = $this->currentUser()['session_token'];

        $response = $this->_modelUser->getAllFiltered($sessionToken, $level, $role, $status);

        if (isset($response['code']) && $response['code'] == 200 && isset($response['msg']) && is_array($response['msg'])) {

            $userData = $response['msg'];

            if ($search !== '') {
                $searchLower = strtolower($search);

                $userData = array_filter($userData, function ($user) use ($searchLower) {
                    $idUser    = isset($user['id_user']) ? strtolower($user['id_user']) : '';
                    $fullName  = isset($user['fullName']) ? strtolower($user['fullName']) : '';
                    $email     = isset($user['email']) ? strtolower($user['email']) : '';
                    $levelUser = isset($user['level_user']) ? strtolower($user['level_user']) : '';
                    $roleName  = isset($user['role']) ? strtolower($user['role']) : '';

                    $statusText = 'non-aktif';
                    if (isset($user['is_blocked']) && $user['is_blocked'] == 1) {
                        $statusText = 'blokir';
                    } elseif (isset($user['is_active']) && $user['is_active'] == 1) {
                        $statusText = 'aktif';
                    }

                    return (
                        strpos($idUser, $searchLower) !== false ||
                        strpos($fullName, $searchLower) !== false ||
                        strpos($email, $searchLower) !== false ||
                        strpos($levelUser, $searchLower) !== false ||
                        strpos($roleName, $searchLower) !== false ||
                        strpos($statusText, $searchLower) !== false
                    );
                });
            }

            $paginator = Zend_Paginator::factory($userData);
            $paginator->setItemCountPerPage($limit);
            $paginator->setCurrentPageNumber($page);
            $paginator->setPageRange(3);

            $this->view->users = $paginator;
        } else {
            $this->view->users = [];
        }

        $responseRoles = $this->_modelUser->getRoles($sessionToken);
        $responseLevel = $this->_modelUser->getLevels($sessionToken);

        if (isset($responseRoles['msg'][0]['ERROR']) && $responseRoles['msg'][0]['ERROR'] == 'Invalid or expired session') {
            return $this->_redirect('/auth/logout');
        }

        if (isset($responseLevel['msg'][0]['ERROR']) && $responseLevel['msg'][0]['ERROR'] == 'Invalid or expired session') {
            return $this->_redirect('/auth/logout');
        }

        $rolesData = (isset($responseRoles['code']) && $responseRoles['code'] == 200 && isset($responseRoles['msg']))
            ? $responseRoles['msg']
            : [];

        $this->view->rolesData = $rolesData;
        $this->view->listLevel = isset($responseLevel['msg']) && is_array($responseLevel['msg']) ? $responseLevel['msg'] : [];
        $this->view->listItp   = $this->_modelUser->fetchItpList();
    }

    public function detailAction()
    {
        $idUser       = (int) $this->_getParam('id', 0);
        $sessionToken = $this->currentUser()['session_token'];

        $userDetail = $this->_modelUser->getDetail($idUser, $sessionToken);

        $this->view->userDetail = $userDetail;
        $this->view->listItp    = $this->_modelUser->fetchItpList();
        $this->view->tpCode     = $this->_modelUser->extractTpCode($userDetail);
    }

    public function createAction()
    {
        $sessionToken = $this->currentUser()['session_token'];

        $responseRoles = $this->_modelUser->getRoles($sessionToken);
        $responseLevel = $this->_modelUser->getLevels($sessionToken);

        if (isset($responseRoles['msg'][0]['ERROR']) && $responseRoles['msg'][0]['ERROR'] == 'Invalid or expired session') {
            return $this->_redirect('/auth/logout');
        }

        if (isset($responseLevel['msg'][0]['ERROR']) && $responseLevel['msg'][0]['ERROR'] == 'Invalid or expired session') {
            return $this->_redirect('/auth/logout');
        }

        $rolesData = (isset($responseRoles['code']) && $responseRoles['code'] == 200 && isset($responseRoles['msg']))
            ? $responseRoles['msg']
            : [];

        $this->view->listRoles = $rolesData;
        $this->view->listLevel = isset($responseLevel['msg']) && is_array($responseLevel['msg']) ? $responseLevel['msg'] : [];
        $this->view->listItp   = $this->_modelUser->fetchItpList();
    }

    public function saveAction()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->getResponse()->setHeader('Content-Type', 'application/json');

        if ($this->_request->isPost()) {
            $fullName  = (string) $this->_getParam('fullName', '');
            $email     = trim((string) $this->_getParam('email', ''));
            $levelUser = (int) $this->_getParam('level_user', 1);
            $roleValue = (int) $this->_getParam('role', 1);
            $itpCode   = trim((string) $this->_getParam('itp_code', ''));

            if (empty($email) || empty($fullName)) {
                return $this->_helper->json([
                    'success' => false,
                    'code'    => 400,
                    'msg'     => 'Nama dan Email wajib diisi!'
                ]);
            }

            $defaultPassword = "Biller123!";
            $passwordHash    = hash('sha256', $defaultPassword);

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

            $response = $this->_modelUser->createUser($dbPayload);

            if (isset($response['code']) && $response['code'] == 200) {

                if (isset($response['msg'][0]['ERROR'])) {
                    return $this->_helper->json([
                        'success' => false,
                        'code'    => 400,
                        'msg'     => $response['msg'][0]['ERROR']
                    ]);
                }

                try {
                    $newUserId   = $response['msg'][0]['id'] ?? ($response['msg']['id'] ?? 0);
                    $roleDisplay = ($roleValue === 1) ? 'Administrator' : 'Viewer';
                    $loginUrl    = $this->getBaseUrl() . '/auth/login';

                    $body = App_Service_EmailTemplate::render(
                        'success_create',
                        [
                            'misId'       => $newUserId,
                            'misName'     => $fullName,
                            'misEmail'    => $email,
                            'misRole'     => $roleDisplay,
                            'misPassword' => $defaultPassword,
                            'misUrl'      => $loginUrl
                        ],
                        'Kode OTP'
                    );

                    $this->_modelUser->sendEmailNotification($email, 'Informasi Akun Baru - Portal MIS', $body);
                } catch (Exception $e) {
                    error_log("CRITICAL ERROR DI BLOK EMAIL: " . $e->getMessage());
                }

                return $this->_helper->json([
                    'success' => true,
                    'code'    => 200,
                    'msg'     => 'User berhasil ditambahkan dan email kredensial telah dikirim.'
                ]);
            } else {
                $msgError = isset($response['msg']) ? $response['msg'] : 'Gagal memproses data ke server backend.';
                return $this->_helper->json([
                    'success' => false,
                    'code'    => isset($response['code']) ? $response['code'] : 404,
                    'msg'     => $msgError
                ]);
            }
        }

        return $this->_helper->redirector->gotoUrl('user/index/index');
    }

    public function editAction()
    {
        $idUser       = (int) $this->_getParam('id', 0);
        $sessionToken = $this->currentUser()['session_token'];

        $userDetail    = $this->_modelUser->getDetail($idUser, $sessionToken);
        $responseRoles = $this->_modelUser->getRoles($sessionToken);
        $responseLevel = $this->_modelUser->getLevels($sessionToken);

        if (isset($responseRoles['msg'][0]['ERROR']) && $responseRoles['msg'][0]['ERROR'] == 'Invalid or expired session') {
            return $this->_redirect('/auth/logout');
        }

        if (isset($responseLevel['msg'][0]['ERROR']) && $responseLevel['msg'][0]['ERROR'] == 'Invalid or expired session') {
            return $this->_redirect('/auth/logout');
        }

        $rolesData = (isset($responseRoles['code']) && $responseRoles['code'] == 200 && isset($responseRoles['msg']))
            ? $responseRoles['msg']
            : [];

        $this->view->userDetail = $userDetail;
        $this->view->listRoles  = $rolesData;
        $this->view->listLevel  = isset($responseLevel['msg']) && is_array($responseLevel['msg']) ? $responseLevel['msg'] : [];
        $this->view->listItp    = $this->_modelUser->fetchItpList();
        $this->view->tpCode     = $this->_modelUser->extractTpCode($userDetail);

        $this->_helper->viewRenderer('edit');
    }

    public function updateAction()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->getResponse()->setHeader('Content-Type', 'application/json');

        if ($this->_request->isPost()) {
            $idUser    = (int) $this->_getParam('id_user', 0);
            $fullName  = (string) $this->_getParam('fullName', '');
            $email     = (string) $this->_getParam('email', '');
            $levelUser = (int) $this->_getParam('level_user', 1);
            $roleValue = (int) $this->_getParam('role', 1);
            $itpCode   = trim((string) $this->_getParam('itp_code', ''));
            $statusRaw = $this->_getParam('status', '1');

            $isActive  = ($statusRaw === '1') ? 1 : 0;

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

            $response = $this->_modelUser->updateUser($payload);

            if (isset($response['code']) && $response['code'] == 200) {
                if (isset($response['msg'][0]['ERROR'])) {
                    return $this->_helper->json([
                        'success' => false,
                        'code'    => 400,
                        'msg'     => $response['msg'][0]['ERROR']
                    ]);
                }

                $successMessage = isset($response['msg'][0]['message']) ? $response['msg'][0]['message'] : 'Data user berhasil diperbarui.';
                return $this->_helper->json([
                    'success' => true,
                    'code'    => 200,
                    'msg'     => $successMessage
                ]);
            } else {
                $backendRawMsg = isset($response['msg']) ? json_encode($response['msg']) : 'Gagal memproses perubahan data di server backend.';
                return $this->_helper->json([
                    'success' => false,
                    'code'    => isset($response['code']) ? $response['code'] : 500,
                    'msg'     => $backendRawMsg
                ]);
            }
        }

        return $this->_helper->redirector->gotoUrl('user/index/index');
    }

    public function sendMailAction()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->getResponse()->setHeader('Content-Type', 'application/json');

        try {
            $idUser      = (int) $this->_getParam('id_user', 0);
            $fullName    = (string) $this->_getParam('fullName', '');
            $email       = trim($this->_getParam('email'));
            $roleValue   = (int) $this->_getParam('role', 1);
            $roleDisplay = ($roleValue === 1) ? 'Administrator' : 'Viewer';

            if (empty($email)) {
                throw new Exception('Email is required');
            }

            $defaultPassword = "!#(@snb83";

            $body = App_Service_EmailTemplate::render(
                'success_create',
                [
                    'misId'       => $idUser,
                    'misName'     => $fullName,
                    'misEmail'    => $email,
                    'misRole'     => $roleDisplay,
                    'misPassword' => $defaultPassword,
                ],
                'Kode OTP'
            );

            $this->_modelUser->sendEmailNotification($email, 'Informasi Akun Baru - Portal MIS', $body);

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
            $idUser      = (int) $this->_getParam('id', 0);
            $fullName    = (string) $this->_getParam('fullName', '');
            $email       = trim((string) $this->_getParam('email', ''));
            $roleValue   = (int) $this->_getParam('role', 1);
            $roleDisplay = ($roleValue === 1) ? 'Administrator' : 'Viewer';

            if (empty($email)) {
                return $this->_helper->json([
                    'success' => false,
                    'code'    => 400,
                    'msg'     => 'Data email tidak ditemukan!'
                ]);
            }

            $newPassword  = "!#(@snb83";
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

            $response = $this->_modelUser->resetPassword($dbPayload);

            if (isset($response['code']) && $response['code'] == 200) {

                if (isset($response['msg'][0]['ERROR'])) {
                    return $this->_helper->json([
                        'success' => false,
                        'code'    => 400,
                        'msg'     => $response['msg'][0]['ERROR']
                    ]);
                }

                try {
                    $realIdUser = $response['msg'][0]['user_id'] ?? $idUser;
                    $loginUrl   = $this->getBaseUrl() . '/auth/login';

                    $body = App_Service_EmailTemplate::render(
                        'reset_password_admin',
                        [
                            'misId'       => $realIdUser,
                            'misName'     => $fullName,
                            'misEmail'    => $email,
                            'misRole'     => $roleDisplay,
                            'misPassword' => $newPassword,
                            'misUrl'      => $loginUrl
                        ],
                        'Reset Kata Sandi'
                    );

                    $this->_modelUser->sendEmailNotification($email, 'Pemberitahuan Perubahan Kata Sandi - Portal MIS', $body);
                } catch (Exception $e) {
                    error_log("Gagal memproses notifikasi email reset password: " . $e->getMessage());
                }

                return $this->_helper->json([
                    'success' => true,
                    'code'    => 200,
                    'msg'     => 'Kata sandi berhasil disetel ulang dan email notifikasi telah dikirim.'
                ]);
            } else {
                $msgError = isset($response['msg']) ? $response['msg'] : 'Gagal memproses reset password ke server backend.';
                return $this->_helper->json([
                    'success' => false,
                    'code'    => isset($response['code']) ? $response['code'] : 404,
                    'msg'     => $msgError
                ]);
            }
        }

        return $this->_helper->redirector->gotoUrl('user/index/index');
    }
}
