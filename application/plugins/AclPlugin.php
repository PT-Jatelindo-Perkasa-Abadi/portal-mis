<?php
class AclPlugin extends Zend_Controller_Plugin_Abstract
{
    public function preDispatch(Zend_Controller_Request_Abstract $req)
    {
        $role = SessionService::getRole();

        $resource = $req->getModuleName() . ':' . $req->getControllerName();
        $privilege = $req->getActionName();

        if (!AclService::isAllowed($role, $resource, $privilege)) {
            $req->setModuleName('default')
                ->setControllerName('error')
                ->setActionName('forbidden');
        }
    }
}