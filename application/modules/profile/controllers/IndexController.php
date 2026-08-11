<?php
class Profile_IndexController extends App_Controller_Base
{
    public function init() {}

    public function indexAction()
    {
        $userData = App_Service_Session::get('user');

        if ($this->getRequest()->isXmlHttpRequest()) {
            $service = $this->api();
            $response = $service->request(
                'POST',
                '/service/proxy/service/alias/get-itp-name',
                [$userData['tp_code'], $this->currentUser()['session_token']]
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

        $data = $this->_request->getPost();

        if ($data) {
            $payloadcp = [
                $data['sess'],
                $data['email'],
                hash('sha256', $data['currentPassword']),
                hash('sha256', $data['newPassword']),
                $data['ip'],
                $data['useragent']
            ];

            $api = new App_Service_Api();
            $_ = $api->authorization();
            $cp = $api->request('POST', '/service/proxy/service/alias/change-password', $payloadcp);

            echo json_encode($cp);
        }
    }
}