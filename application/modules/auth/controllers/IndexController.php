<?php

class Auth_IndexController extends Zend_Controller_Action
{
    public function indexAction()
    {
        $this->_helper->redirector->gotoUrl('/auth/login');
    }

    /**
     * @var Zend_Controller_Action
     */

    public function loginAction()
    {
        $this->view->headTitle('Login');

        $notice = new Zend_Session_Namespace('login_notice');
        if (isset($notice->errorInactive) && $notice->errorInactive === true) {
            $this->view->errorInactive = true;
            unset($notice->errorInactive); // Hapus flag setelah dibaca
        }

        if (App_Service_Session::getExpiredFlag()) {
            $this->view->errorMessage = 'Session expired, silakan login kembali';
        }

        if (!$this->_request->isPost()) {
            return;
        }

        $data      = $this->_request->getPost();
        $validator = new Zend_Validate_EmailAddress();

        if (!$validator->isValid($data['email'])) {
            $this->view->error = 'Format email salah.';
            return;
        }

        $api     = new App_Service_Api();
        $_       = $api->authorization();
        $ip      = App_Log_Context::getIp();
        $payload = [
            $data['email'],
            hash('sha256', $data['password']),
            $ip,
            App_Log_Context::getUserAgent(),
            App_Log_Context::getDeviceType(),
            App_Log_Context::getDeviceType(),
            App_Log_Context::getUserAgent(),
            App_Log_Context::getClientOS(),
            "@p_session_token",
            "@p_refresh_token"
        ];

        $response          = $api->request(
            'POST',
            '/service/proxy/service/alias/login-session',
            $payload
        );
        $this->view->email = $data['email'] ?? '';

        if (!isset($response['code']) || $response['code'] != '200' || empty($response['msg'])) {
            $this->view->error = $response['msg'] ?? 'Gagal terhubung ke server autentikasi.';
            return;
        }

        $user = $response['msg'][0] ?? [];

        if (!empty($user['ERROR'])) {
            $errMessage      = $user['ERROR'];
            $errMessageLower = strtolower($errMessage);

            if ($errMessage == 'Kata Sandi Salah.' || strpos($errMessageLower, 'sandi salah') !== false) {
                $this->view->errorPassword = "Kata Sandi Salah. Coba lagi atau klik 'Lupa kata sandi' untuk mengatur ulang.";
            } elseif ($errMessage == 'Akun Tidak Ditemukan' || strpos($errMessageLower, 'tidak ditemukan') !== false) {
                $this->view->errorAccount = true;
            } elseif (
                strpos($errMessageLower, 'blokir') !== false ||
                strpos($errMessageLower, 'block') !== false ||
                $errMessage == 'User sudah gagal login 3 kali, akun diblokir'
            ) {
                $this->view->errorBlocked = true;
            } elseif (
                strpos($errMessageLower, 'nonaktif') !== false ||
                strpos($errMessageLower, 'non-aktif') !== false ||
                strpos($errMessageLower, 'inactive') !== false ||
                strpos($errMessageLower, 'dinonaktifkan') !== false ||
                strpos($errMessageLower, 'deactive') !== false ||
                strpos($errMessageLower, 'account') !== false
            ) {
                $this->view->errorInactive = true;
            } else {
                $this->view->error = $errMessage;
            }
            return;
        }

        if (isset($user['is_blocked']) && (int)$user['is_blocked'] === 1) {
            $this->view->errorBlocked = true;
            return;
        }

        if (isset($user['is_active']) && (int)$user['is_active'] === 0) {
            $this->view->errorInactive = true;
            return;
        }

        $session = new Zend_Session_Namespace('forgot_password');
        unset($session->isNewUser);
        unset($session->isUser);

        if (isset($user['has_changed_password']) && (int)$user['has_changed_password'] === 0) {
            $session->otp          = (string)$user['session_id'];
            $session->verified     = true;
            $session->email        = $user['email'];
            $session->verified_at = time();
            $session->isNewUser    = true;
            $this->view->isNewUser = true;

            return;
        } elseif (isset($user['has_changed_password']) && (int)$user['has_changed_password'] === 2) {
            $session->otp          = (string)$user['session_id'];
            $session->verified     = true;
            $session->email        = $user['email'];
            $session->verified_at = time();
            $session->isUser       = true;
            $this->view->isUser    = true;

            return;
        }

        $userProfile = [
            'id'            => $user['id'],
            'username'      => $user['username'] ?? '',
            'fullName'      => $user['full_name'] ?? '',
            'email'         => $user['email'] ?? '',
            'role'          => strtolower($user['role_name'] ?? 'guest'),
            'session_token' => $user['session_token'] ?? '',
            'level'         => $user['level_name'] ?? '',
            'tp_code'       => $user['tp_code'] ?? ""
        ];

        try {
            $aclResponse = $api->request(
                'POST',
                '/service/proxy/service/alias/get-acl-config',
                [$userProfile['session_token']]
            );

            $aclError = '';
            if (isset($aclResponse['msg'][0]['ERROR'])) {
                $aclError = $aclResponse['msg'][0]['ERROR'];
            } elseif (is_string($aclResponse['msg'] ?? null)) {
                $aclError = $aclResponse['msg'];
            }

            if (!empty($aclError)) {
                App_Service_Session::destroy();
                $aclErrorLower = strtolower($aclError);

                if (strpos($aclErrorLower, 'block') !== false || strpos($aclErrorLower, 'blokir') !== false) {
                    $this->view->errorBlocked = true;
                } else {
                    $this->view->errorInactive = true;
                }
                return;
            }

            if (
                isset($aclResponse['code']) && $aclResponse['code'] == 200
                && !empty($aclResponse['msg']) && is_array($aclResponse['msg'])
            ) {
                $aclRows = $aclResponse['msg'];

                App_Service_Session::set(
                    'acl_config',
                    App_Service_AclBuilder::build($aclRows, $userProfile['role'])
                );
                App_Service_Session::set(
                    'menus',
                    App_Service_MenuBuilder::build($aclRows)
                );
            }
        } catch (Exception $e) {
            App_Service_Session::destroy();
            $this->view->errorInactive = true;
            return;
        }

        App_Service_Session::set('user', $userProfile);
        App_Service_Session::refreshActivity();

        if (strtolower($user['role_name'] ?? '') === 'rekon') {
            $this->_helper->redirector->gotoUrl('/history');
        } else {
            $this->_helper->redirector->gotoUrl('/');
        }
    }

    public function forgotPasswordAction()
    {
        $this->view->headTitle('Forgot Password');
    }

    public function sendOtpAction()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->getResponse()->setHeader('Content-Type', 'application/json');

        try {
            $email = trim($this->_getParam('email'));

            if (empty($email)) {
                throw new Exception('Email is required');
            }

            $api = new App_Service_Api();
            $_ = $api->authorization();

            $payload = [
                $email,
                App_Log_Context::getIp(),
                App_Log_Context::getUserAgent(),
            ];
            $response = $api->request(
                'POST',
                '/service/proxy/service/alias/otp-forgotpassword',
                $payload
            );

            if ($response['code'] != '200') {
                return $this->_helper->json([
                    'success' => false,
                    'message' => $response['msg']
                ]);
            }

            if (!empty($response['msg'][0]['ERROR'])) {
                return $this->_helper->json([
                    'success' => false,
                    'message' => $response['msg'][0]['ERROR']
                ]);
            }

            $body = App_Service_EmailTemplate::render(
                'otp',
                [
                    'title' => 'Reset Password OTP',
                    'email' => $email,
                    'name'  => $response['msg'][0]['username'] ?? '',
                    'otp'   => $response['msg'][0]['reset_token'] ?? ''
                ],
                'Kode OTP'
            );
            $emailPayload = [
                'to'      => [$email],
                'subject' => 'Kode OTP',
                'body'    => $body,
                'isHtml'  => true
            ];

            $emailResponse = $api->request(
                'POST',
                '/service/email',
                $emailPayload
            );

            return $this->_helper->json([
                'success' => true,
                'message' => 'OTP has been sent'
            ]);
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'USER_NOT_FOUND') !== false) {
                return $this->_helper->json([
                    'success' => true,
                    'message' => 'OTP has been sent'
                ]);
            }

            return $this->_helper->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function verifyOtpProcessAction()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->getResponse()->setHeader('Content-Type', 'application/json');

        try {
            $email = trim($this->_getParam('email'));
            $otp   = trim($this->_getParam('otp'));

            if (empty($email) || empty($otp)) {
                throw new Exception('OTP is required');
            }

            $api = new App_Service_Api();
            $_   = $api->authorization();

            $payload = [
                $otp,
                App_Log_Context::getIp(),
                App_Log_Context::getUserAgent(),
            ];
            $response = $api->request(
                'POST',
                '/service/proxy/service/alias/otp-forgotpassword-validation',
                $payload
            );

            if ($response['code'] != '200') {
                return $this->_helper->json([
                    'success' => false,
                    'message' => $response['msg']
                ]);
            }

            if (empty($response['msg'][0])) {
                return $this->_helper->json([
                    'success' => false,
                    'message' => $response['msg']
                ]);
            }

            if (!empty($response['msg'][0]['ERROR'])) {
                return $this->_helper->json([
                    'success' => false,
                    'message' => $response['msg'][0]['ERROR']
                ]);
            }

            $session = new Zend_Session_Namespace('forgot_password');

            $session->verified    = true;
            $session->email       = $email;
            $session->otp         = $otp;
            $session->verified_at = time();

            return $this->_helper->json([
                'success'  => true,
                'redirect' => $this->view->baseUrl('auth/reset-password')
            ]);
        } catch (Exception $e) {
            return $this->_helper->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function resetPasswordAction()
    {
        $this->view->headTitle('Reset Password');

        $session = new Zend_Session_Namespace('forgot_password');

        if (empty($session->verified)) {
            return $this->_redirect('/auth/forgot-password');
        }

        if ((time() - $session->verified_at) > 600) {
            Zend_Session::namespaceUnset('forgot_password');

            return $this->_redirect('/auth/forgot-password');
        }

        if ($this->getRequest()->isPost()) {

            $this->_helper->viewRenderer->setNoRender(true);
            $this->getResponse()->setHeader('Content-Type', 'application/json');

            try {
                $password        = trim($this->_getParam('newPassword'));
                $confirmPassword = trim($this->_getParam('confirmPassword'));

                if (empty($password)) {
                    throw new Exception('Password is required');
                }

                if (strlen($password) < 8) {
                    throw new Exception('Password minimum 8 characters');
                }

                if ($password !== $confirmPassword) {
                    throw new Exception('Password confirmation mismatch');
                }

                $hashedPassword = hash('sha256', $password);

                $api = new App_Service_Api();
                $_   = $api->authorization();

                $payload = [
                    $session->otp,
                    $hashedPassword,
                    App_Log_Context::getIp(),
                    App_Log_Context::getUserAgent()
                ];

                $url = '/service/proxy/service/alias/forgotpassword';

                if (!empty($session->isNewUser) || !empty($session->isUser)) {
                    $url = '/service/proxy/service/alias/reset-password-newuser';

                } 

                $response = $api->request(
                    'POST',
                    $url,
                    $payload
                );

                if ($response['code'] != '200') {
                    throw new Exception($response['msg']);
                }

                Zend_Session::namespaceUnset('forgot_password');

                return $this->_helper->json([
                    'success' => true,
                    'message' => 'Password updated successfully'
                ]);
            } catch (Exception $e) {
                return $this->_helper->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ]);
            }
        }
    }

    public function logoutAction()
    {
        App_Service_Session::destroy();
        return $this->_helper->redirector('index', 'login', 'auth');
    }
}
