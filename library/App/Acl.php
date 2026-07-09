<?php

class App_Acl extends Zend_Acl
{
    public function __construct()
    {
        /**
         * ======================
         * BASE: Guest role & public resources
         * (always registered regardless of API data)
         * ======================
         */
        $this->addRole(new Zend_Acl_Role('guest'));

        $this->add(new Zend_Acl_Resource('auth:index'));
        $this->add(new Zend_Acl_Resource('error:index'));

        $this->allow(
            'guest',
            'auth:index',
            [
                'login',
                'forgot-password',
                'verify-otp',
                'reset-password',
                'send-otp',
                'verify-otp-process'
            ]
        );

        /**
         * ======================
         * DYNAMIC: Load from session cache (populated at login)
         * ======================
         */
        $aclConfig = App_Service_Session::get('acl_config');

        // Zend_Debug::dump($aclConfig);

        if (!empty($aclConfig)) {
            $this->buildFromApi($aclConfig);
        } else {
            $this->buildDefault();
        }
    }

    /**
     * Build ACL dynamically from backend API data cached in session.
     *
     * Expected $config structure:
     * [
     *   'roles' => [
     *     ['role_name' => 'maker', 'parent_role' => 'guest'],
     *     ['role_name' => 'admin', 'parent_role' => 'guest'],
     *     ...
     *   ],
     *   'resources' => [
     *     'default:index',
     *     'profile:index',
     *     'user:index',
     *     ...
     *   ],
     *   'permissions' => [
     *     ['role' => 'maker',  'resource' => 'default:index', 'actions' => null],
     *     ['role' => 'maker',  'resource' => 'auth:index',    'actions' => ['logout']],
     *     ['role' => 'admin',  'resource' => null,             'actions' => null],  // full access
     *     ...
     *   ]
     * ]
     */
    protected function buildFromApi(array $config)
    {
        // --- Register roles ---
        if (!empty($config['roles']) && is_array($config['roles'])) {
            foreach ($config['roles'] as $roleData) {
                $roleName = strtolower($roleData['role_name'] ?? '');
                $parentRole = isset($roleData['parent_role']) && $roleData['parent_role'] !== ''
                    ? strtolower($roleData['parent_role'])
                    : null;

                if (empty($roleName) || $this->hasRole($roleName)) {
                    continue;
                }

                // Ensure parent exists before referencing it
                if ($parentRole !== null && !$this->hasRole($parentRole)) {
                    $this->addRole(new Zend_Acl_Role($parentRole));
                }

                $this->addRole(new Zend_Acl_Role($roleName), $parentRole);
            }
        }

        // --- Register resources ---
        if (!empty($config['resources']) && is_array($config['resources'])) {
            foreach ($config['resources'] as $resource) {
                if (!empty($resource) && !$this->has($resource)) {
                    $this->add(new Zend_Acl_Resource($resource));
                }
            }
        }

        // --- Apply permissions ---
        if (!empty($config['permissions']) && is_array($config['permissions'])) {
            foreach ($config['permissions'] as $perm) {
                $role = isset($perm['role']) ? strtolower($perm['role']) : null;
                $resource = isset($perm['resource']) ? $perm['resource'] : null;
                $actions = isset($perm['actions']) ? $perm['actions'] : null;

                if (empty($role) || !$this->hasRole($role)) {
                    continue;
                }

                // Ensure resource exists if specified
                if ($resource !== null && !$this->has($resource)) {
                    $this->add(new Zend_Acl_Resource($resource));
                }

                $this->allow($role, $resource, $actions);
            }
        }
    }

    /**
     * Fallback: hardcoded defaults when no API data is available (guest session).
     * This preserves backward compatibility for non-logged-in users.
     */
    protected function buildDefault()
    {
        /**
         * ======================
         * ROLES
         * ======================
         */
        $this->addRole(new Zend_Acl_Role('maker'), 'guest');
        $this->addRole(new Zend_Acl_Role('checker'), 'guest');
        $this->addRole(new Zend_Acl_Role('admin'), 'guest');
        $this->addRole(new Zend_Acl_Role('admin mis'), 'guest');
        $this->addRole(new Zend_Acl_Role('guest mis'), 'guest');
        $this->addRole(new Zend_Acl_Role('viewer'), 'guest');

        /**
         * ======================
         * RESOURCES
         * ======================
         */
        $this->add(new Zend_Acl_Resource('default:index'));
        $this->add(new Zend_Acl_Resource('profile:index'));
        $this->add(new Zend_Acl_Resource('user:index'));

        /**
         * ======================
         * Admin MIS
         * ======================
         */
        $this->allow('admin mis', 'default:index');

        /**
         * ======================
         * MAKER
         * ======================
         */
        $this->allow('maker', 'default:index');

        /**
         * ======================
         * CHECKER
         * ======================
         */
        $this->allow('checker', 'default:index');

        /**
         * ======================
         * GUEST MIS
         * ======================
         */
        $this->allow('guest mis', 'default:index');

        /**
         * ======================
         * VIEWER
         * ======================
         */
        $this->allow('viewer', 'default:index');

        /**
         * ======================
         * ADMIN
         * ======================
         */
        $this->allow('admin'); // full akses
        $this->allow('admin mis'); // full akses

        /**
         * ======================
         * PROFILE
         * ======================
         */
        $this->allow('maker', 'profile:index');
        $this->allow('checker', 'profile:index');
        $this->allow('admin', 'profile:index');
        $this->allow('admin mis', 'profile:index');
        $this->allow('guest mis', 'profile:index');
        $this->allow('viewer', 'profile:index');

        /**
         * ======================
         * USER
         * ======================
         */
        $this->allow('maker', 'user:index');
        $this->allow('checker', 'user:index');
        $this->allow('admin', 'user:index');
        $this->allow('admin mis', 'user:index');
        $this->allow('guest mis', 'user:index');
        $this->allow('viewer', 'user:index');

        /**
         * ======================
         * LOGOUT
         * ======================
         */
        $this->allow('maker', 'auth:index', ['logout']);
        $this->allow('checker', 'auth:index', ['logout']);
        $this->allow('admin', 'auth:index', ['logout']);
        $this->allow('admin mis', 'auth:index', ['logout']);
        $this->allow('guest mis', 'auth:index', ['logout']);
        $this->allow('viewer', 'auth:index', ['logout']);
    }
}