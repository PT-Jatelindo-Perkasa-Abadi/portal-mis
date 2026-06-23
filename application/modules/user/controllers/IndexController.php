<?php

class User_IndexController extends App_Controller_Base
{
    public function indexAction()
    {
    
    $response = $api->request(
        'POST', '/service/proxy/service/alias/get-all-user');

    }

    $this->view->users = $response['msg'][0];
}



