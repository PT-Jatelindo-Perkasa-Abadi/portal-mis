<?php

/**
 * Transforms the flat permission rows returned by sp_get_acl_config
 * into the acl_config structure consumed by App_Acl::buildFromApi().
 *
 * Model:
 *   - Menu row (action_id NULL): page access. can_view = 1 lets the
 *     role open the page (the 'index' action of the menu's resource).
 *   - Action row (action_id set): a named action inside the parent
 *     menu (menus_action) — a tab, a button, a view. The action name
 *     comes from action_code (last path segment, e.g. 'create',
 *     'send-mail', 'tab-riwayat'). Granted when the row's can_view = 1.
 *
 * Controller-action names gate requests through App_Plugin_Acl;
 * virtual names (tabs, buttons) are checked in views via
 * $this->isAllowed('user:index', 'tab-riwayat').
 *
 * Everything not granted is denied by default.
 */
class App_Service_AclBuilder
{
    /**
     * @param array  $rows     Rows from sp_get_acl_config
     * @param string $roleName The logged-in user's role (must match App_Auth::role())
     * @return array acl_config structure for App_Acl::buildFromApi()
     */
    public static function build(array $rows, $roleName)
    {
        $role = strtolower($roleName);

        // Baseline access every authenticated role gets, regardless of
        // menu configuration (mirrors App_Acl::buildDefault()).
        $resources = ['default:index', 'profile:index'];
        $permissions = [
            ['role' => $role, 'resource' => 'auth:index',    'actions' => ['logout']],
            ['role' => $role, 'resource' => 'default:index', 'actions' => null],
            ['role' => $role, 'resource' => 'profile:index', 'actions' => null],
        ];

        $actionsByResource = [];

        foreach ($rows as $row) {
            if (empty($row['can_view'])) {
                continue;
            }

            $resource = self::urlToResource($row['menu_url']);
            if ($resource === null) {
                continue;
            }

            if (empty($row['action_id'])) {
                // Menu row: entry access to the page
                $action = 'index';
            } else {
                // Action row: explicit named action inside the menu
                $action = self::codeToAction($row['action_code']);
                if ($action === null) {
                    continue;
                }
            }

            if (!isset($actionsByResource[$resource])) {
                $actionsByResource[$resource] = [];
            }
            $actionsByResource[$resource][] = $action;
        }

        foreach ($actionsByResource as $resource => $actions) {
            $resources[] = $resource;
            $permissions[] = [
                'role'     => $role,
                'resource' => $resource,
                'actions'  => array_values(array_unique($actions)),
            ];
        }

        return [
            'roles' => [
                ['role_name' => $role, 'parent_role' => 'guest'],
            ],
            'resources'   => array_values(array_unique($resources)),
            'permissions' => $permissions,
        ];
    }

    /**
     * Map a menu URL to a Zend ACL resource (module:controller).
     * '/user' => 'user:index', '/user/index/create' => 'user:index'
     */
    protected static function urlToResource($url)
    {
        $path = parse_url(trim((string) $url), PHP_URL_PATH);
        $segments = array_values(array_filter(explode('/', (string) $path), 'strlen'));

        if (empty($segments)) {
            return null; // '/' is covered by the default:index baseline
        }

        $module     = strtolower($segments[0]);
        $controller = isset($segments[1]) ? strtolower($segments[1]) : 'index';

        return $module . ':' . $controller;
    }

    /**
     * Action name from action_code: the last path segment.
     * 'send-mail' => 'send-mail', '/user/index/send-mail' => 'send-mail'
     */
    protected static function codeToAction($code)
    {
        $segments = array_values(array_filter(explode('/', trim((string) $code)), 'strlen'));

        if (empty($segments)) {
            return null;
        }

        return strtolower(end($segments));
    }
}
