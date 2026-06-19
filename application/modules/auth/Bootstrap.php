<?php
class Auth_Bootstrap extends Zend_Application_Module_Bootstrap
{
    protected function _initRoutes()
    {
        $front  = Zend_Controller_Front::getInstance();
        $router = $front->getRouter();

        $router->addRoute(
            'auth_login',
            new Zend_Controller_Router_Route(
                'auth/login',
                [
                    'module'     => 'auth',
                    'controller' => 'index',
                    'action'     => 'login'
                ]
            )
        );

        $router->addRoute(
            'auth_login_submit',
            new Zend_Controller_Router_Route(
                'auth/submit-login',
                [
                    'module'     => 'auth',
                    'controller' => 'index',
                    'action'     => 'submit-login'
                ]
            )
        );

        $router->addRoute(
            'auth_forgot',
            new Zend_Controller_Router_Route(
                'auth/forgot-password',
                [
                    'module'     => 'auth',
                    'controller' => 'index',
                    'action'     => 'forgot-password'
                ]
            )
        );

        $router->addRoute(
            'auth_verify_otp',
            new Zend_Controller_Router_Route(
                'auth/verify-otp',
                [
                    'module'     => 'auth',
                    'controller' => 'index',
                    'action'     => 'verify-otp'
                ]
            )
        );

        $router->addRoute(
            'auth_reset',
            new Zend_Controller_Router_Route(
                'auth/reset-password',
                [
                    'module'     => 'auth',
                    'controller' => 'index',
                    'action'     => 'reset-password'
                ]
            )
        );

        $router->addRoute(
            'auth_logout',
            new Zend_Controller_Router_Route(
                'auth/logout',
                [
                    'module'     => 'auth',
                    'controller' => 'index',
                    'action'     => 'logout'
                ]
            )
        );

        $router->addRoute(
            'auth_send_otp',
            new Zend_Controller_Router_Route(
                'auth/send-otp',
                [
                    'module'     => 'auth',
                    'controller' => 'index',
                    'action'     => 'send-otp'
                ]
            )
        );

        $router->addRoute(
            'auth_verify_otp_process',
            new Zend_Controller_Router_Route(
                'auth/verify-otp-process',
                [
                    'module'     => 'auth',
                    'controller' => 'index',
                    'action'     => 'verify-otp-process'
                ]
            )
        );
    }
}