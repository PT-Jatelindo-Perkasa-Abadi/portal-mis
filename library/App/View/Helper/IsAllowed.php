<?php

class App_View_Helper_IsAllowed extends Zend_View_Helper_Abstract
{
    protected $_acl;

    public function __construct()
    {
        $this->_acl  = new App_Acl();
    }

    public function isAllowed($resource, $privilege)
    {
        $user = App_Service_Session::get('user');

        $role = 'guest';

        if (!empty($user) && isset($user['role'])) {
            $role = $user['role'];
        }

        if (!$this->_acl->has($resource)) {
            return false;
        }

        return $this->_acl->isAllowed($role, $resource, $privilege);
    }
}