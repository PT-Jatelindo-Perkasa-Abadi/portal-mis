<?php

class Reports_ItpController extends App_Controller_Base
{
	public function init()
    {

        var_dump('dasdasdasdasdasdasdasdasdasdas');
    }

    public function indexAction()
    {
        /*
        $api = new App_Service_Api();
        $_ = $api->authorization();

        $layanan = $this->_getParam('layanan', 'ALL');
        $this->view->selectedLayanan = $layanan;


        $activeTab = $this->_getParam('type', 'it-provider');

        $this->view->layanan = array(
            'ALL'            => 'Semua Layanan',
            'POSTPAID'    => 'Postpaid',
            'PREPAID'     => 'Prepaid',
            'NON_TAGLIST' => 'Non-Taglist'
        );

        $sess = App_Service_Session::get('user');
        
        $payload = [
            $sess['id'],
            $sess['session_token']
        ];

        $response = $api->request('POST', '/service/proxy/service/alias/get-user-detail', $payload);
        $listData = isset($response["msg"]) ? $response["msg"] : [];

        var_dump($listData);



        $apiUrlPartner   = '/service/proxy/service/alias/get-partner-ip';
        $payloadPartner   = [];

        $responsePartner  = $api->request('POST', $apiUrlPartner , $payloadPartner );
        $this->view->listDataPartner  = isset($responsePartner ["msg"]) ? $responsePartner ["msg"] : [];

        */
    }

    public function itpreportAction()
    {
        var_dump('dasdasdasdasdasdasdasdas');
    }
}
