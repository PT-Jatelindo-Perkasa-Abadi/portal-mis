<?php
class App_Plugin_Layout extends Zend_Controller_Plugin_Abstract
{
    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
        $module = $request->getModuleName();

        if ($module != 'auth') {
            $view = Zend_Layout::getMvcInstance()->getView();
            $user = App_Service_Session::get('user');
    
            $view->user = $user;
            $view->initialUser = $this->_initial($user['fullName']) ?? "";
        }
    }
    public function postDispatch(Zend_Controller_Request_Abstract $request)
    {
        $layout = Zend_Layout::getMvcInstance();
        $module = $request->getModuleName();
        $controller = $request->getControllerName();

        if ($module === 'auth') {
            $layout->setLayout('auth-layout');
        }

        if ($module != 'auth' && $controller != 'error') {
            $layout->setLayout('main-layout');
        }

        if ($controller === 'error') {
            $layout->setLayout('error-layout');
        }
    }

    protected function _initial($name)
    {
        preg_match_all('/\b\w/', $name, $matches);
        return strtoupper(implode('', array_slice($matches[0], 0, 2)));
    }
}