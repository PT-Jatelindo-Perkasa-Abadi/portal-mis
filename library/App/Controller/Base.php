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
        $this->logger = Zend_Registry::get('logger');
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

        // if ($role === 'checker') {
        //     return ['created_by' => '', 'group_id' => (string) ($user['groupId'] ?? 0)];
        // }

        // return [
        //     'created_by' => (string) ($this->currentUserId() ?? 0),
        //     'group_id' => ''
        // ];
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