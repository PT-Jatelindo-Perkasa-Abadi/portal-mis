<?php

class Profile_IndexController extends App_Controller_Base
{
    
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

        $userData = App_Service_Session::get('user');
        $data = $this->_request->getPost();

        if ($data) {
            try {
                $payloadcp = [
                    $userData['session_token'] ?? '',
                    $userData['email'] ?? '',
                    hash('sha256', $data['currentPassword'] ?? ''),
                    hash('sha256', $data['newPassword'] ?? ''),
                    App_Log_Context::getIp() ?? '',
                    App_Log_Context::getUserAgent() ?? ''
                ];

                $api = new App_Service_Api();
                $_ = $api->authorization();
                $cp = $api->request('POST', '/service/proxy/service/alias/change-password', $payloadcp);

                return $this->jsonSuccess($cp);
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