<?php

class Profile_IndexController extends App_Controller_Base
{
    public function init()
    {
        parent::init();

        $currentUser = App_Service_Session::get('user');

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

        if (empty($sessionToken)) {
            App_Service_Session::destroy();
            $this->_helper->redirector->gotoSimple('index', 'login', 'auth');
            return;
        }

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
    }

    public function indexAction()
    {
        $userData = App_Service_Session::get('user');

        if ($this->getRequest()->isXmlHttpRequest()) {
            $service = $this->api();
            $response = $service->request(
                'POST',
                '/service/proxy/service/alias/get-itp-name',
                [$userData['tp_code'] ?? '', $this->currentUser()['session_token'] ?? '']
            );

            if (isset($response['code']) && $response['code'] == 200 && isset($response['msg'][0])) {
                return $this->jsonSuccess($response['msg']);
            }

            return $this->jsonError('Failed to fetch data.');
        }

        $this->view->user = $userData;
    }

    public function changepassAction()
    {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        $this->getResponse()->setHeader('Content-Type', 'application/json');

        $data = $this->_request->getPost();

        if ($data) {
            try {
                $payloadcp = [
                    $data['sess'] ?? '',
                    $data['email'] ?? '',
                    hash('sha256', $data['currentPassword'] ?? ''),
                    hash('sha256', $data['newPassword'] ?? ''),
                    $data['ip'] ?? '',
                    $data['useragent'] ?? ''
                ];

                $api = new App_Service_Api();
                $_ = $api->authorization();
                $cp = $api->request('POST', '/service/proxy/service/alias/change-password', $payloadcp);

                echo json_encode($cp);
            } catch (Exception $e) {
                echo json_encode([
                    'code' => 500,
                    'msg'  => $e->getMessage()
                ]);
            }
        }
        exit;
    }
}
