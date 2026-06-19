<?php
class Profile_IndexController extends App_Controller_Base
{
    public function indexAction()
    {
        $user = $this->currentUser();

        if ($this->_request->isPost()) {
            $data = $this->_request->getPost();

            if ($data['newPassword'] != $data['confirmPassword']) {
                $this->view->error = "Password did not match";
                return;
            }

            $checkUser = $this->api()->sp('sp_user_get_by_email', [$user['email']]);

            if ($checkUser["responseCode"] != "2002200") {
                $this->view->error = "User not found";
                return;
            }

            ["password" => $currentPassword] = $checkUser["data"][0];

            if (password_verify($data['currentPassword'], $currentPassword)) {
                $response = $this->api()->sp('user_update_password', [$user['email'], password_hash($data['newPassword'], PASSWORD_DEFAULT)]);

                if ($response['responseMessage'] == 'Success') {
                    $this->view->success = "Password success changed";
                } else {
                    $this->view->error = $response['responseMessage'];
                }
            } else {
                $this->view->error = "Password did not match";
            }
        }
    }
}