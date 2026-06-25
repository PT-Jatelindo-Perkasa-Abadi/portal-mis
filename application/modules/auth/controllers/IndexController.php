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

        if (App_Service_Session::getExpiredFlag()) {
            $this->view->errorMessage = 'Session expired, silakan login kembali';
        }

        if (!$this->_request->isPost()) {
            return;
        }

        $data = $this->_request->getPost();
        $validator = new Zend_Validate_EmailAddress();

        if (!$validator->isValid($data['email'])) {
            $this->view->error = 'Invalid email format';
            return;
        }

        $api = new App_Service_Api();
        $_ = $api->authorization();
        $ip = App_Log_Context::getIp();
        $params = [
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
        $payload = $params;

        /**
         * Check email exists
         */
        $response = $api->request(
            'POST',
            '/service/proxy/service/alias/login-session',
            $payload
        );
        $this->view->email = $data['email'] ?? '';

        if ($response['error']) {
            $this->view->connectionError = 'Error : Connection refused';
            return;
        }

        if ($response['code'] != '200') {
            $this->view->error = $response['responseMessage'];
            return;
        }

        if (!$response['msg'][0]['email']) {
            $this->view->error = 'Invalid email or password';
            return;
        }

        $user = $response['msg'][0];

        $userProfile = [
            'id' => $user['id'],
            'username' => $user['username'],
            'fullName' => $user['full_name'],
            'email' => $user['email'],
            'role' => strtolower($user['role_name']),
            'session_token' => $user['session_token']
        ];

        App_Service_Session::set('user', $userProfile);
        App_Service_Session::refreshActivity();
        
        if (strtolower($user['role_name']) === 'rekon') {
            $this->_helper->redirector->gotoUrl('/history');
        } else {
            // PERUBAHAN DI SINI: Memaksa redirect langsung ke halaman Kelola User agar tidak loop ke login kembali
            $this->_helper->redirector->gotoUrl('/user/index/index');
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

            $otp = (string) rand(100000, 999999);
            $expiredAt = date('Y-m-d H:i:s', strtotime('+5 minutes'));
            $api = new App_Service_Api();
            $_ = $api->authorization();

            $payload = [
                'params' => [
                    $email,
                    App_Log_Context::getIp(),
                    App_Log_Context::getUserAgent(),
                ]
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

            $body = App_Service_EmailTemplate::render(
                'otp',
                [
                    'title' => 'Reset Password OTP',
                    'email' => $email,
                    'otp' => $response['msg'][0]['reset_token']
                ],
                'Kode OTP'
            );
            $emailPayload = [
                'to' => [$email],
                'subject' => 'Kode OTP',
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
            $otp = trim($this->_getParam('otp'));

            if (empty($email) || empty($otp)) {
                throw new Exception('OTP is required');
            }

            $api = new App_Service_Api();
            $_ = $api->authorization();

            $payload = [
                'params' => [
                    $otp,
                    App_Log_Context::getIp(),
                    App_Log_Context::getUserAgent(),
                ]
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

            $session = new Zend_Session_Namespace('forgot_password');

            $session->verified = true;
            $session->email = $email;
            $session->otp = $otp;
            $session->verified_at = time();

            return $this->_helper->json([
                'success' => true,
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
                $password = trim($this->_getParam('newPassword'));
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
                $_ = $api->authorization();

                $payload = [
                    'params' => [
                        $session->otp,
                        $hashedPassword,
                        App_Log_Context::getIp(),
                        App_Log_Context::getUserAgent()
                    ]
                ];
                $response = $api->request(
                    'POST',
                    '/service/proxy/service/alias/forgotpassword',
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