<?php

/**
 * Builds the flat sidebar menu list from the rows returned by
 * sp_get_acl_config (the same rows App_Service_AclBuilder consumes).
 *
 * Only menu rows (action_id NULL) with can_view = 1 become sidebar
 * items; action rows from menus_action (tabs, buttons, views) are
 * ACL-only and never rendered in the sidebar.
 *
 * Output (cached in session as 'menus', rendered by main-layout.phtml):
 * [
 *   [
 *     'name'       => 'Kelola User',
 *     'icon'       => 'ic-users',
 *     'url'        => '/user',
 *     'module'     => 'user',      // for the activeMenu() view helper
 *     'controller' => 'index',
 *     'order'      => 4,
 *   ],
 *   ...
 * ]
 */
class App_Service_MenuBuilder
{
    /**
     * @param array $rows Rows from sp_get_acl_config
     * @return array Flat menu list sorted by menu_order
     */
    public static function build(array $rows)
    {
        $menus = [];

        foreach ($rows as $row) {
            if (!empty($row['action_id'])              // action rows are ACL-only
                || empty($row['can_view'])
                || empty($row['menu_id'])
                || isset($menus[$row['menu_id']])
            ) {
                continue;
            }

            list($module, $controller) = self::urlToModuleController($row['menu_url']);

            $menus[$row['menu_id']] = [
                'name'       => $row['menu_name'],
                'icon'       => !empty($row['menu_icon']) ? $row['menu_icon'] : 'ic-circle',
                'url'        => $row['menu_url'],
                'module'     => $module,
                'controller' => $controller,
                'order'      => (int) $row['menu_order'],
            ];
        }

        usort($menus, function ($a, $b) {
            return $a['order'] - $b['order'];
        });

        return array_values($menus);
    }

    /**
     * '/user' => ['user', 'index'], '/user/index/create' => ['user', 'index'],
     * '/' => ['default', 'index']
     */
    protected static function urlToModuleController($url)
    {
        $path = parse_url(trim((string) $url), PHP_URL_PATH);
        $segments = array_values(array_filter(explode('/', (string) $path), 'strlen'));

        $module     = isset($segments[0]) ? strtolower($segments[0]) : 'default';
        $controller = isset($segments[1]) ? strtolower($segments[1]) : 'index';

        return [$module, $controller];
    }
}
