<?php

/**
 * Base controller providing shared helper methods for all modules.
 *
 * Extend this class instead of Zend_Controller_Action to gain access to:
 *  - api()            – lazy-loaded App_Service_Api with authorization
 *  - parseJsonPost()  – parse & validate JSON POST bodies (disables layout/view)
 *  - requireFields()  – assert + extract required payload keys
 *  - currentUser()    – current session user (or null)
 *  - currentUserId()  – current session user id (or null)
 *  - jsonSuccess()    – standardized success JSON response
 *  - jsonError()      – standardized error JSON response
 */
abstract class App_Controller_Base extends Zend_Controller_Action
{
    protected $logger;

    public function init()
    {
        // 1. Inisialisasi Logger Bawaan Anda
        $this->logger = Zend_Registry::get('logger');

        // 2. Cegah Infinite Redirect jika Controller yang Mengakses adalah 'auth'
        $controllerName = strtolower($this->getRequest()->getControllerName());
        if ($controllerName === 'auth') {
            return;
        }

        // 3. Ambil Session User & Token
        $user = $this->currentUser();
        $sessionToken = $user['session_token'] ?? null;

        if (empty($sessionToken)) {
            $this->handleInvalidSession('Sesi tidak ditemukan.');
            return;
        }

        // 4. Validasi Session Token Real-Time ke Backend Database (Berlaku untuk Navigasi & AJAX)
        try {
            $api = new App_Service_Api();
            $api->authorization();
            $response = $api->request('POST', '/service/proxy/service/alias/get-acl-config', [$sessionToken]);

            $rawMsg = '';
            if (isset($response['msg'])) {
                if (is_string($response['msg'])) {
                    $rawMsg = $response['msg'];
                } elseif (is_array($response['msg'])) {
                    $rawMsg = $response['msg'][0]['ERROR'] ?? ($response['msg']['ERROR'] ?? '');
                }
            }

            $rawMsgLower = strtolower($rawMsg);

            // 🎯 Deteksi jika token ditolak/dinonaktifkan/diblokir/expired oleh backend
            if (
                strpos($rawMsgLower, 'invalid') !== false ||
                strpos($rawMsgLower, 'expired') !== false ||
                strpos($rawMsgLower, 'session') !== false ||
                strpos($rawMsgLower, 'nonaktifkan') !== false ||
                strpos($rawMsgLower, 'block') !== false
            ) {
                $this->handleInvalidSession('Akun Anda telah dinonaktifkan atau sesi telah berakhir.');
                return;
            }
        } catch (Exception $e) {
            $this->handleInvalidSession('Terjadi kesalahan otentikasi sesi.');
            return;
        }
    }

    /**
     * 🎯 Helper untuk Menangani Logout Otomatis (Mendukung Navigasi Normal & Request AJAX)
     */
    protected function handleInvalidSession(string $message = '')
    {
        // 1. Simpan penanda ke Session Namespace 'login_notice'
        $notice = new Zend_Session_Namespace('login_notice');
        $notice->errorInactive = true;

        // 2. Hapus data autentikasi user SAJA (JANGAN destroy seluruh sesi PHP)
        App_Service_Session::set('user', null);
        App_Service_Session::set('acl_config', null);
        App_Service_Session::set('menus', null);

        // 3. Jika Request berupa AJAX, kirim JSON HTTP 401 agar frontend bisa redirect
        if ($this->getRequest()->isXmlHttpRequest()) {
            $this->_helper->layout->disableLayout();
            $this->_helper->viewRenderer->setNoRender(true);
            $this->getResponse()
                ->setHttpResponseCode(401)
                ->setHeader('Content-Type', 'application/json');

            echo json_encode([
                'success'  => false,
                'code'     => 401,
                'msg'      => $message ?: 'Sesi Anda telah berakhir, silakan login kembali.',
                'redirect' => $this->getBaseUrl() . '/auth/login'
            ]);
            exit;
        }

        // 4. Jika Request Normal (Navigasi Halaman), lakukan Redirect
        $this->_helper->redirector->gotoSimple('login', 'index', 'auth');
        exit;
    }

    private $_api;

    /**
     * Lazy-loaded API service with authorization.
     */
    protected function api(): App_Service_Api
    {
        if (!$this->_api) {
            $this->_api = new App_Service_Api();
        }

        $this->_api->authorization();

        return $this->_api;
    }

    /**
     * Parse and validate a JSON POST request body.
     * Also disables layout/view rendering for AJAX responses.
     */
    protected function parseJsonPost(): array
    {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        if (!$this->getRequest()->isPost()) {
            throw new Exception('Invalid request method');
        }

        $raw = $this->getRequest()->getRawBody();
        if (empty($raw)) {
            throw new Exception('Empty payload');
        }

        $data = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON format');
        }

        return $data;
    }

    /**
     * Assert that each $required key exists and is non-empty in $payload,
     * and return their values in the same order for destructuring.
     *
     * Example: [$id, $bank] = $this->requireFields($payload, ['id', 'bank_code']);
     */
    protected function requireFields(array $payload, array $required): array
    {
        $values = [];
        foreach ($required as $key) {
            if (!isset($payload[$key]) || $payload[$key] === '') {
                throw new Exception("Field '$key' is required");
            }
            $values[] = $payload[$key];
        }
        return $values;
    }

    /**
     * Current session user as an array, or null when not signed in.
     */
    protected function currentUser(): ?array
    {
        $user = App_Service_Session::get('user');
        return is_array($user) ? $user : null;
    }

    /**
     * Current session user id, or null when not signed in.
     */
    protected function currentUserId(): ?int
    {
        $user = $this->currentUser();
        return isset($user['id']) ? (int) $user['id'] : null;
    }

    /**
     * Row-level ownership scope for list queries, as
     * ['created_by' => string, 'group_id' => string] where '' means
     * "no restriction on that dimension".
     *
     *  - admin / rekon : see every row.
     *  - checker       : see rows created by anyone in their own group.
     *  - maker (others): see only the rows they created.
     *
     * Unknown ids fall back to '0' (matches nothing) so a missing session
     * value never accidentally exposes all rows.
     */
    protected function ownerScope(): array
    {
        $user = $this->currentUser() ?? [];
        $role = $user['role'] ?? '';

        if (in_array($role, ['admin', 'rekon'], true)) {
            return ['created_by' => '', 'group_id' => ''];
        }

        return [
            'created_by' => '',
            'group_id' => (string) ($user['groupId'] ?? 0)
        ];
    }

    /**
     * Whether the current user may access a single detail row, using the same
     * owner scope as the list queries. Calls an access-check stored procedure
     * that returns { allowed: 0 | 1 }. Fails closed (returns false) when the
     * row is out of scope, missing, or the call errors.
     */
    protected function canAccess(string $spName, $id): bool
    {
        $scope = $this->ownerScope();
        $res = $this->api()->sp($spName, [$id, $scope['created_by'], $scope['group_id']]);

        return !empty($res['data'][0]['allowed']);
    }

    /**
     * Return a standardized success JSON response.
     */
    protected function jsonSuccess($data = null, int $code = 200)
    {
        $this->getResponse()->setHttpResponseCode($code);
        return $this->_helper->json(['status' => 'success', 'data' => $data]);
    }

    /**
     * Return a standardized error JSON response.
     */
    protected function jsonError(string $message, int $errorCode = 500)
    {
        $this->getResponse()->setHttpResponseCode($errorCode);
        return $this->_helper->json(['status' => 'error', 'message' => $message]);
    }

    /**
     * Check if response code is success
     */
    protected function isSuccess($result): bool
    {
        $responseCode = $result["responseCode"] ?? null;

        if (empty($responseCode)) {
            throw new Exception("Invalid response format");
        }

        return str_starts_with($responseCode, "2");
    }

    /**
     * Get full path base url
     */
    protected function getBaseUrl()
    {
        return $this->getRequest()->getScheme()
            . '://'
            . $this->getRequest()->getHttpHost();
    }
}
