<?php

class User_IndexController extends App_Controller_Base
{
    public function indexAction()
    {
        $api = new App_Service_Api();
        $_ = $api->authorization();
        $response = $api->request('POST', '/service/proxy/service/alias/get-all-user');

        if (isset($response['code']) && $response['code'] == 200 && isset($response['msg']) && is_array($response['msg'])) {
            $this->view->users = $response['msg']; // Melempar seluruh daftar user
        } else {
            $this->view->users = []; // Fallback kosong jika API error agar tidak crash
        }
    }


    // public function detailAction()
    // {
    //     // 1. Ambil ID User dari parameter URL
    //     $idUser = $this->_request->getParam('id', '');

    //     // 2. Tembak API detail (ganti dengan endpoint detail asli Anda jika berbeda)
    //     // Contoh di bawah mengirimkan parameter id ke endpoint API
    //     $response = $this->api()->request('POST', '/service/proxy/service/alias/get-detail-user', [
    //         'id_user' => $idUser
    //     ]);

    //     // 3. Validasi respon data dari API sebelum dilempar ke view detail.phtml
    //     if (isset($response['msg'][0]) && is_array($response['msg'][0])) {
    //         $this->view->userDetail = $response['msg'][0];
    //     } else {
    //         // SEMENTARA/FALLBACK: Jika API database kosong, gunakan data dummy agar halaman tidak blank saat di-test
    //         $this->view->userDetail = [
    //             'status'     => 'Aktif',
    //             'id_user'    => !empty($idUser) ? $idUser : '12345678',
    //             'fullName'   => 'Daniel Samantha',
    //             'email'      => 'danielsamantha@gmail.com',
    //             'level_user' => 'MIS',
    //             'role'       => 'Administrator'
    //         ];
    //     }
    // }

    public function detailAction()
    {
        $this->view->headTitle('Detail User');

    
        $idUser = $this->_getParam('id');

    
        if (empty($idUser)) {
            return $this->_redirect('user');
        }

        $api = new App_Service_Api();
        $_ = $api->authorization(); 

        
        $payload = [
            'params' => [
                $idUser
            ]
        ];

        
        $response = $api->request(
            'POST',
            '/service/proxy/service/alias/get-user-detail',
            $payload
        );

        $userData = null;

        
        if ($response && isset($response['code']) && $response['code'] == '200' && !empty($response['msg'])) {
            
            $userFromApi = $response['msg'][0];

            
            $userData = [
                'id_user'    => $userFromApi['id_user'] ?? $idUser,
                'fullName'   => $userFromApi['full_name'] ?? '-',
                'email'      => $userFromApi['email'] ?? '-',
                'level_user' => $userFromApi['level_user'] ?? '-',
                'role'       => $userFromApi['role_name'] ?? '-',
                'status'     => (isset($userFromApi['is_active']) && $userFromApi['is_active'] == 1) ? 'Aktif' : 'Non-Aktif'
            ];
        }

        $this->view->userDetail = $userData;
    }

    public function createAction()
{
    $this->view->headTitle('Tambah User Baru');
    
    // Tambahkan baris ini untuk memaksa sistem membuka file create.phtml Anda
    $this->_helper->viewRenderer('create');
}
}
