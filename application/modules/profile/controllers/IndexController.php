<?php
class Profile_IndexController extends App_Controller_Base
{
    public function init() {}

    public function indexAction()
    {
        $this->view->user = App_Service_Session::get('user');
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
