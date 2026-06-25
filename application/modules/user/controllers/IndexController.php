<?php

class User_IndexController extends App_Controller_Base
{
    public function indexAction()
    {
        $api = new App_Service_Api();
        $_ = $api->authorization();

        $limit = (int) $this->_getParam('limit', 10);
        $page  = (int) $this->_getParam('page', 1);

        $response = $api->request('POST', '/service/proxy/service/alias/get-all-user');

        if (isset($response['code']) && $response['code'] == 200 && isset($response['msg']) && is_array($response['msg'])) {

            $paginator = Zend_Paginator::factory($response['msg']);

            $paginator->setItemCountPerPage($limit);
            $paginator->setCurrentPageNumber($page);

            $this->view->users = $paginator;
        } else {
            $this->view->users = [];
        }
    }

    // public function detailAction()
    // {
    //     $api = new App_Service_Api();
    //     $_ = $api->authorization();

    //     $idUser = $this->_getParam('id');
    //     $response = $api->request('POST', '/service/proxy/service/alias/get-all-user');

    //     $userDetail = null;

    //     if (isset($response['code']) && $response['code'] == 200 && isset($response['msg']) && is_array($response['msg'])) {
    //         foreach ($response['msg'] as $user) {
    //             if (isset($user['id_user']) && $user['id_user'] == $idUser) {
    //                 $userDetail = $user;
    //                 break;
    //             }
    //         }
    //     }

    //     if ($userDetail !== null) {
    //         if (isset($userDetail['is_blocked']) && $userDetail['is_blocked'] == 1) {
    //             $userDetail['status'] = 'Blokir';
    //         } elseif (isset($userDetail['is_active']) && $userDetail['is_active'] == 1) {
    //             $userDetail['status'] = 'Aktif';
    //         } else {
    //             $userDetail['status'] = 'Non-Aktif';
    //         }
    //     }

    //     $this->view->userDetail = $userDetail;
    // }

    public function detailAction()
    {
        $api = new App_Service_Api();

        // 1. Tembak langsung ke library authorization global Anda untuk menarik token JWT yang asli
        $sessionData = $api->authorization();
        
        $sessionToken = '';
        if (is_array($sessionData)) {
            if (isset($sessionData['access_token'])) {
                $sessionToken = $sessionData['access_token'];
            } elseif (isset($sessionData['session'])) {
                $sessionToken = $sessionData['session'];
            } elseif (isset($sessionData['token'])) {
                $sessionToken = $sessionData['token'];
            }
        }

        // Fallback: Jika di dalam root array tidak ketemu, coba cek di dalam sub-key 'msg' (seperti log login Anda)
        if (empty($sessionToken) && isset($sessionData['msg']['access_token'])) {
            $sessionToken = $sessionData['msg']['access_token'];
        }

        // 2. Tangkap ID user yang dilempar dari parameter URL list utama
        $idUser = (int) $this->_getParam('id', 0);

        // 3. Susun FLAT PAYLOAD (Total 2 indeks: ID User dan Token JWT Panjang)
        $payload = [
            $idUser,
            // $this->currentUserId(),
            $this->currentUser()['session_token'],
        ];

        // 4. Hit ke API Detail User Spesifik
        $response = $api->request('POST', '/service/proxy/service/alias/get-user-detail', $payload);

        $userDetail = null;

        // 5. Validasi response sukses dari backend Go
        if (isset($response['code']) && $response['code'] == 200 && isset($response['msg']) && is_array($response['msg'])) {
            $userDetail = isset($response['msg'][0]) ? $response['msg'][0] : null;
        }

        // 6. Parsing label status visual untuk kebutuhan tampilan detail.phtml
        if ($userDetail !== null) {
            if (isset($userDetail['is_blocked']) && $userDetail['is_blocked'] == 1) {
                $userDetail['status'] = 'Blokir';
            } elseif (isset($userDetail['is_active']) && $userDetail['is_active'] == 1) {
                $userDetail['status'] = 'Aktif';
            } else {
                $userDetail['status'] = 'Non-Aktif';
            }
        }

        // Kirim object array data user ke file detail.phtml
        $this->view->userDetail = $userDetail;
    }

    public function createAction()
    {
        $this->view->headTitle('Tambah User Baru');
        $this->_helper->viewRenderer('create');
    }

    public function saveAction()
    {
        if ($this->_request->isPost()) {
            $api = new App_Service_Api();
            
            $sessionData = $api->authorization(); 

            $sessionToken = '';
            if (is_array($sessionData)) {
                if (isset($sessionData['access_token'])) {
                    $sessionToken = $sessionData['access_token'];
                } elseif (isset($sessionData['session'])) {
                    $sessionToken = $sessionData['session'];
                } elseif (isset($sessionData['token'])) {
                    $sessionToken = $sessionData['token'];
                }
            }

            $fullName  = (string)$this->_getParam('fullName', '');
            $email     = (string)$this->_getParam('email', '');
            $levelUser = (int)$this->_getParam('level_user', 1);
            $roleValue = (int)$this->_getParam('role', 1);

            $defaultPassword = "Biller123!"; 
            $passwordHash    = hash('sha256', $defaultPassword);

            $ipAddress = $this->_request->getServer('REMOTE_ADDR', '127.0.0.1');
            if ($ipAddress === '::1') {
                $ipAddress = '127.0.0.1'; 
            }
            $userAgent = $this->_request->getServer('HTTP_USER_AGENT', 'Mozilla/5.0');

            $payload = [
                $email,               
                $passwordHash,        
                $fullName,             
                $levelUser,            
                $this->currentUserId(),
                $ipAddress,           
                $userAgent,           
                $this->currentUser()['session_token'],
                $roleValue,            
                "@p_user_id",          
                "@p_reset_token"       
            ];

            $response = $api->request('POST', '/service/proxy/service/alias/create-user', $payload);

            if (isset($response['code']) && $response['code'] == 200) {
                
                if (isset($response['msg'][0]['ERROR'])) {
                    $this->_helper->json([
                        'success' => false,
                        'code'    => 400,
                        'msg'     => $response['msg'][0]['ERROR']
                    ]);
                    exit;
                }

                $this->_helper->json([
                    'success' => true,
                    'code'    => 200,
                    'msg'     => 'User berhasil ditambahkan.'
                ]);
                exit;
            } else {
                $msgError = isset($response['msg']) ? $response['msg'] : 'Gagal memproses data ke server backend.';
                $this->_helper->json([
                    'success' => false,
                    'code'    => isset($response['code']) ? $response['code'] : 404,
                    'msg'     => $msgError
                ]);
                exit;
            }
        }

        return $this->_helper->redirector->gotoUrl('user/index/index');
    }

    public function editAction()
    {
        $api = new App_Service_Api();
        $_ = $api->authorization();

        $idUser = $this->_getParam('id');

        $response = $api->request('POST', '/service/proxy/service/alias/get-all-user');
        $userDetail = null;

        if (isset($response['code']) && $response['code'] == 200 && isset($response['msg']) && is_array($response['msg'])) {
            foreach ($response['msg'] as $user) {
                if (isset($user['id_user']) && (int)$user['id_user'] == (int)$idUser) {
                    $userDetail = $user;
                    break;
                }
            }
        }

        $this->view->userDetail = $userDetail;
        $this->_helper->viewRenderer('edit');
    }
}
