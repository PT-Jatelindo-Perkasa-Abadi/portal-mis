<?php

class Activity_IndexController extends App_Controller_Base
{
    public function init()
    {
        //ini_set('display_errors', 1);
      	//ini_set('display_startup_errors', 1);
      	//error_reporting(E_ALL);
    }

    public function indexAction()
    {
        $api = new App_Service_Api();
        $_ = $api->authorization();

        $sess = App_Service_Session::get('user');

        $payload = [
            $sess['session_token']
        ];

        $responseLevel = $api->request('POST', '/service/proxy/service/alias/get-levels', $payload);
        $listDataLevel = isset($responseLevel["msg"]) ? $responseLevel["msg"] : [];
        $this->view->listDatalevel = $listDataLevel;

        $responseRoles = $api->request('POST', '/service/proxy/service/alias/get-roles', $payload);
        $listDataRoles = isset($responseRoles["msg"]) ? $responseRoles["msg"] : [];
        $this->view->listDataRoles = $listDataRoles;
    }

    public function listAction()
    {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $this->getResponse()->setHeader('Content-Type', 'application/json');

        try {
            $sess = App_Service_Session::get('user');
            $api = new App_Service_Api();
            $_ = $api->authorization();

            $draw   = (int)$this->_getParam('draw', 1);
            $start  = (int)$this->_getParam('start', 0);
            $length = (int)$this->_getParam('length', 10);

            $tanggal   = $this->_getParam('tanggal', date('Y-m-d'));
            $leveluser = trim($this->_getParam('leveluser', ''));
            $roleuser  = trim($this->_getParam('roleuser', ''));
            $keyword   = trim($this->_getParam('keyword', ''));

            $columns = array(
                0 => 'No',
                1 => 'created_at',
                2 => 'full_name',
                3 => 'email',
                4 => 'level_name',
                5 => 'role_name',
                6 => 'menu',
                7 => 'browser',
                8 => 'deskripsi'
            );

            $orderParam = $this->_getParam('order');
            $orderIndex = isset($orderParam[0]['column']) ? (int)$orderParam[0]['column'] : 1;
            $orderDir   = isset($orderParam[0]['dir']) ? strtoupper($orderParam[0]['dir']) : 'DESC';

            if (!in_array($orderDir, array('ASC', 'DESC'))) {
                $orderDir = 'DESC';
            }

            $orderBy = isset($columns[$orderIndex]) ? $columns[$orderIndex] : 'created_at';

            $payloadT = [$sess['session_token'], $tanggal, $roleuser, $leveluser, $keyword, $orderBy, $orderDir, $length, $start];

            $apiUrlT   = '/service/proxy/service/alias/get-view-total-auditlog';
            $responseT = $api->request('POST', $apiUrlT, $payloadT);
            $payloadN = [$sess['session_token'], $tanggal, $roleuser, $leveluser, $keyword, $orderBy, $orderDir, $length, $start];

            $apiUrlN   = '/service/proxy/service/alias/get-view-auditlog';
            $responseN = $api->request('POST', $apiUrlN, $payloadN);

            $recordsTotal    = isset($responseT['msg'][0]['recordsTotal']) ? (int)$responseT['msg'][0]['recordsTotal'] : 0;
            $recordsFiltered = isset($responseT['msg'][0]['recordsFiltered']) ? (int)$responseT['msg'][0]['recordsFiltered'] : 0;
            $dataList        = isset($responseN['msg']) && is_array($responseN['msg']) ? $responseN['msg'] : array();

            echo Zend_Json::encode(array(
                "draw"            => $draw,
                "recordsTotal"    => $recordsTotal,
                "recordsFiltered" => $recordsFiltered,
                "data"            => $dataList
            ));
        } catch (Exception $e) {
            echo Zend_Json::encode(array(
                "draw"            => 1,
                "recordsTotal"    => 0,
                "recordsFiltered" => 0,
                "data"            => array(),
                "message"         => $e->getMessage()
            ));
        }
        exit;
    }
}
