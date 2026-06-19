<?php

class App_View_Helper_ActiveMenu extends Zend_View_Helper_Abstract
{
    /**
     * Generate active class for sidebar/menu
     *
     * @param array|string $modules
     * @param array|string|null $controllers
     * @param array|string|null $actions
     * @param string $class
     * @return string
     */
    public function activeMenu(
        $modules,
        $controllers = null,
        $actions = null,
        $class = 'active'
    ) {
        $request = Zend_Controller_Front::getInstance()->getRequest();

        $currentModule     = strtolower($request->getModuleName());
        $currentController = strtolower($request->getControllerName());
        $currentAction     = strtolower($request->getActionName());

        $modules     = array_map('strtolower', (array) $modules);
        $controllers = $controllers
            ? array_map('strtolower', (array) $controllers)
            : [];

        $actions = $actions
            ? array_map('strtolower', (array) $actions)
            : [];

        // check module
        if (!in_array($currentModule, $modules)) {
            return '';
        }

        // check controller
        if (!empty($controllers)
            && !in_array($currentController, $controllers)) {
            return '';
        }

        // check action
        if (!empty($actions)
            && !in_array($currentAction, $actions)) {
            return '';
        }

        return $class;
    }
}