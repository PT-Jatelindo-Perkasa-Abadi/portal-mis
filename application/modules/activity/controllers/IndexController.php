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

        try{

                $sess = App_Service_Session::get('user');
                $api = new App_Service_Api();
                $_ = $api->authorization();


                $draw   = (int)$this->_getParam('draw', 1);
                $start  = (int)$this->_getParam('start', 0);
                $length = (int)$this->_getParam('length', 10);


                $tanggal     = $this->_getParam('tanggal', date('Y-m-d'));
                $leveluser   = trim($this->_getParam('leveluser', ''));
                $roleuser    = trim($this->_getParam('roleuser', ''));
                $keyword     = trim($this->_getParam('keyword', ''));

                $columns = array(
                        0 => 'No',
                        1 => 'Tanggal&Waktu',
                        2 => 'ID User',
                        3 => 'Nama User',
                        4 => 'Email',
                        5 => 'Level User',
                        6 => 'Role',
                        7 => 'Menu',
                        8 => 'Tab Menu',
                        9 => 'Browser',
                        10 => 'Deskripsi'
                    );

               

                $orderIndex = (int)$this->_getParam('order')[0]['column'];
                $orderDir   = strtoupper($this->_getParam('order')[0]['dir']);

                if (!in_array($orderDir, array('ASC', 'DESC'))) {
                    $orderDir = 'ASC';
                }

                $orderBy = isset($columns[$orderIndex])
                    ? $columns[$orderIndex]
                    : 'Tanggal&Waktu';

                $apiUrlT      = '/service/proxy/service/alias/get-view-total-auditlog';
                $payloadT     = [$sess['session_token'],$tanggal,$roleuser,$leveluser,$keyword,$orderBy,$orderDir,$length,$start];
                $responseT    = $api->request('POST', $apiUrlT, $payloadT);

                $apiUrlN      = '/service/proxy/service/alias/get-view-auditlog';
                $payloadN     = [$sess['session_token'],$tanggal,$roleuser,$leveluser,$keyword,$orderBy,$orderDir,$length,$start];
                $responseN    = $api->request('POST', $apiUrlN, $payloadN);

                
                echo Zend_Json::encode(array(
                    "draw" => $draw,
                    "recordsTotal" => $responseT['msg'][0]['recordsTotal'],
                    "recordsFiltered" => $responseT['msg'][0]['recordsFiltered'],
                    "data" => $responseN['msg']
                ));
                
                
        }catch (Exception $e) {
            
            echo Zend_Json::encode(array(
                "draw" => 1,
                "recordsTotal" => 0,
                "recordsFiltered" => 0,
                "data" => array(),
                "message" => $e->getMessage()
            ));

        }
        exit;
    }
}