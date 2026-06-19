<?php
class Auth_IndexController extends Zend_Controller_Action
{
    public function indexAction()
    {
        $this->_helper->redirector->gotoUrl('/auth/login');
    }

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

        /**
         * Check email exists
         */
        $response = $api->sp('sp_user_exists_by_email', [$data['email']]);
        $this->view->email = $data['email'] ?? '';

        if ($response['error']) {
            $this->view->connectionError = 'Error : Connection refused';
            return;
        }

        if ($response['responseCode'] !== '2002200') {
            $this->view->error = $response['responseMessage'];
            return;
        }

        if (!$response['data']) {
            $this->view->error = 'Invalid email or password';
            return;
        }

        /**
         * Get user data
         */
        $result = $api->sp('sp_user_get_by_email', [$data['email']]);

        if (!$result['data']) {
            $this->view->error = 'Invalid email or password';
            return;
        }

        $user = $result['data'][0];

        /**
         * Check status LOCKED
         */
        if ($user['status'] === 'LOCKED') {
            $this->view->error = 'Account has been locked. Please contact administrator.';
            return;
        }

        /**
         * Check status INACTIVE
         */
        if ($user['status'] === 'INACTIVE') {
            $this->view->error = 'Account is inactive. Please contact administrator.';
            return;
        }

        /**
         * Verify password
         */
        if (password_verify($data['password'], $user['password'])) {
            /**
             * Reset failed login attempt
             */
            $api->sp('user_reset_failed_login', [$data['email']]);

            $userProfile = [
                'id' => $user['id'],
                'username' => $user['username'],
                'fullName' => $user['full_name'],
                'email' => $user['email'],
                'roleId' => $user['role_id'],
                'role' => strtolower($user['role_name']),
                'groupId' => $user['group_id'] ?? null,
                'credentialIdNonsnap' => $user['credential_id_nonsnap'],
                'createdAt' => $user['created_at'],
                'updatedAt' => $user['updated_at']
            ];

            App_Service_Session::set('user', $userProfile);
            App_Service_Session::refreshActivity();
            if (strtolower($user['role_name']) === 'rekon') {
                $this->_helper->redirector->gotoUrl('/history');
            } else {
                $redirectUrl = App_Service_Session::getRedirectUrl('/');
                $this->_helper->redirector->gotoUrl($redirectUrl);
            }
        }

        /**
         * Password invalid
         */
        $api->sp('user_failed_login', [$data['email']]);

        /**
         * Re-fetch latest user state
         */
        $latestResult = $api->sp('sp_user_get_by_email', [$data['email']]);
        $latestUser = $latestResult['data'][0];

        /**
         * User became LOCKED
         */
        if ($latestUser['status'] === 'LOCKED') {
            $this->view->error = 'Account has been locked because of 3 failed login attempts. Please contact administrator.';
            return;
        }

        $attempt = (int) $latestUser['failed_login_attempt'];
        $remaining = 3 - $attempt;

        $this->view->error = "Invalid email or password. Remaining attempt: {$remaining}";
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

            $response = $api->sp('sp_otp_create', [$email, $otp, $expiredAt]);

            if ($response['responseCode'] == '5002200') {
                $errorMap = [
                    'ACCOUNT_LOCKED' => 'Account has been locked. Please contact administrator.',
                    'USER_NOT_FOUND' => 'Invalid email.',
                    'ACCOUNT_INACTIVE' => 'Account is inactive. Please contact administrator.',
                    'OTP_RESEND_LIMIT_EXCEEDED' => 'You have reached the maximum OTP resend limit. Please try again after 15 minutes.'
                ];

                $message = $response['responseMessage'];

                foreach ($errorMap as $key => $value) {
                    if (str_contains($message, $key)) {
                        $message = $value;
                        break;
                    }
                }

                return $this->_helper->json([
                    'success' => false,
                    'message' => $message
                ]);
            }

            // $result = $api->request(
            //     'POST',
            //     '/service/query',
            //     ['sql' => 'SELECT full_name FROM users WHERE email = ?',  'params' => [$email]]
            // )['data'][0];

            $result = $api->sp('sp_user_get_by_email', [$email])['data'][0];

            $body = App_Service_EmailTemplate::render(
                'otp',
                [
                    'title' => 'Reset Password OTP',
                    'email' => $email,
                    'otp' => $otp,
                    'name' => $result['full_name'],
                    'expiredMinutes' => 5
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

            $response = $api->sp('sp_otp_verify', [$email, $otp]);

            if ($response['responseCode'] == '5002200') {
                return $this->_helper->json([
                    'success' => false,
                    'message' => $response['responseMessage']
                ]);
            }

            if (empty($response['data'])) {
                return $this->_helper->json([
                    'success' => false,
                    'message' => 'Invalid OTP'
                ]);
            }

            $session = new Zend_Session_Namespace('forgot_password');

            $session->verified = true;
            $session->email = $email;
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
                    throw new Exception(
                        'Password is required'
                    );
                }

                if (strlen($password) < 8) {
                    throw new Exception(
                        'Password minimum 8 characters'
                    );
                }

                if ($password !== $confirmPassword) {
                    throw new Exception(
                        'Password confirmation mismatch'
                    );
                }

                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

                $api = new App_Service_Api();
                $_ = $api->authorization();

                $response = $api->sp('user_update_password', [$session->email, $hashedPassword]);

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