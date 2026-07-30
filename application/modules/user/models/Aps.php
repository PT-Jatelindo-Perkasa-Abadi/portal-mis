<?php

class User_Model_Aps
{
    protected $_api;

    public function __construct()
    {
        $this->_api = new App_Service_Api();
    }

    /**
     * Mengambil daftar user dengan filter
     */
    public function getAllFiltered($sessionToken, $level = null, $role = null, $status = null)
    {
        $this->_api->authorization();

        $payload = [
            $sessionToken,
            $level ?: null,
            $role ?: null,
            $status ?: null
        ];

        return $this->_api->request('POST', '/service/proxy/service/alias/get-all-user-filtered', $payload);
    }

    /**
     * Mengambil detail user berdasarkan ID
     */
    public function getDetail($idUser, $sessionToken)
    {
        $this->_api->authorization();

        $payload  = [$idUser, $sessionToken];
        $response = $this->_api->request('POST', '/service/proxy/service/alias/get-user-detail', $payload);

        $userDetail = null;
        if (isset($response['code']) && $response['code'] == 200 && isset($response['msg'][0])) {
            $userDetail = $response['msg'][0];
        }

        if ($userDetail !== null) {
            if (isset($userDetail['is_blocked']) && $userDetail['is_blocked'] == 1) {
                $userDetail['status'] = 'Blokir';
            } elseif (isset($userDetail['is_active']) && $userDetail['is_active'] == 1) {
                $userDetail['status'] = 'Aktif';
            } else {
                $userDetail['status'] = 'Non-Aktif';
            }
        }

        return $userDetail;
    }

    /**
     * Mengambil daftar Roles
     */
    public function getRoles($sessionToken)
    {
        return $this->_api->request('POST', '/service/proxy/service/alias/get-roles', [$sessionToken]);
    }

    /**
     * Mengambil daftar Levels
     */
    public function getLevels($sessionToken)
    {
        return $this->_api->request('POST', '/service/proxy/service/alias/get-levels', [$sessionToken]);
    }

    /**
     * Mengambil daftar IT Provider
     */
    public function fetchItpList()
    {
        $response = $this->_api->request('POST', '/service/proxy/service/alias/get-all-itp');

        if (
            !isset($response['code']) || $response['code'] != 200
            || empty($response['msg']) || !is_array($response['msg'])
            || isset($response['msg'][0]['ERROR'])
        ) {
            return [];
        }

        $list = [];
        foreach ($response['msg'] as $row) {
            if (!is_array($row)) continue;

            $code = $row['technical_provider_code'] ?? $row['tp_code'] ?? $row['itp_code'] ?? $row['code'] ?? null;
            $name = $row['nama_technical_provider'] ?? $row['tp_name'] ?? $row['itp_name'] ?? $row['name'] ?? null;

            if ($code === null || $code === '') continue;

            $list[] = [
                'code' => (string) $code,
                'name' => ($name !== null && $name !== '') ? (string) $name : (string) $code,
            ];
        }

        return $list;
    }

    /**
     * Ekstrak tp_code dari JSON additional_info
     */
    public function extractTpCode($userDetail)
    {
        if (empty($userDetail['additional_info'])) {
            return null;
        }

        $additional = json_decode($userDetail['additional_info'], true);

        return (is_array($additional) && isset($additional['tp_code']) && $additional['tp_code'] !== '')
            ? (string) $additional['tp_code']
            : null;
    }

    /**
     * Simpan user baru ke backend
     */
    public function createUser($payload)
    {
        $this->_api->authorization();
        return $this->_api->request('POST', '/service/proxy/service/alias/create-user', $payload);
    }

    /**
     * Update data user ke backend
     */
    public function updateUser($payload)
    {
        $this->_api->authorization();
        return $this->_api->request('POST', '/service/proxy/service/alias/update-user', $payload);
    }

    /**
     * Reset password user via admin
     */
    public function resetPassword($payload)
    {
        $this->_api->authorization();
        return $this->_api->request('POST', '/service/proxy/service/alias/admin-change-password', $payload);
    }

    /**
     * Kirim email notifikasi via API Email
     */
    public function sendEmailNotification($toEmail, $subject, $body)
    {
        $emailPayload = [
            'to'      => [$toEmail],
            'subject' => $subject,
            'body'    => $body,
            'isHtml'  => true
        ];

        return $this->_api->request('POST', '/service/email', $emailPayload);
    }
}