<?php

class App_Plugin_Acl extends Zend_Controller_Plugin_Abstract
{
    protected $_acl;

    public function __construct()
    {
        $this->_acl = new App_Acl();
    }

    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
        $role    = App_Auth::role();
        $isLogin = App_Auth::check();

        $module     = $request->getModuleName();
        $controller = $request->getControllerName();
        $action     = $request->getActionName();

        /**
         * =========================
         * HANDLE SESSION EXPIRED
         * =========================
         */
        if ($isLogin && App_Auth::isExpired()) {

            if (!$this->isIgnoredRequest($request)) {
                App_Service_Session::setRedirectUrl($request->getRequestUri());
            }
            App_Service_Session::setExpiredFlag();
            App_Service_Session::clearUser();

            $this->getResponse()
                ->setRedirect('/auth/login')
                ->sendResponse();
            exit;
        }

        // refresh activity (sliding session)
        if ($isLogin) {
            App_Service_Session::refreshActivity();
        }

        /**
         * =========================
         * REDIRECT JIKA SUDAH LOGIN
         * =========================
         */
        if ($isLogin && $module === 'auth') {

            if ($controller === 'index' && $action === 'login') {
                $this->getResponse()
                    ->setRedirect('/')
                    ->sendResponse();
                exit;
            }

            if (in_array($action, [
                'login',
                'forgot-password',
                'verify-otp',
                'reset-password',
                'send-otp',
                'verify-otp-process'
            ])) {
                $this->getResponse()
                    ->setRedirect('/')
                    ->sendResponse();
                exit;
            }
        }

        /**
         * =========================
         * ACL CHECK
         * =========================
         */
        $resource = $module . ':' . $controller;

        if (!$this->_acl->has($resource)) {
            $resource = 'error:index';
        }

        if (!$this->_acl->isAllowed($role, $resource, $action)) {

            // BELUM LOGIN
            if (!$isLogin) {

                if (!$this->isIgnoredRequest($request)) {
                    App_Service_Session::setRedirectUrl($request->getRequestUri());
                }

                $this->getResponse()
                    ->setRedirect('/auth/login')
                    ->sendResponse();
                exit;
            }

            // SUDAH LOGIN TAPI TIDAK ADA AKSES
            $request->setModuleName('default')
                ->setControllerName('error')
                ->setActionName('forbidden');

            return;
        }
    }

    protected function isAssetRequest($request)
    {
        $uri = $request->getRequestUri();

        return preg_match('#\.(css|js|png|jpg|jpeg|gif|svg|ico)$#i', $uri);
    }

    protected function isIgnoredRequest($request)
    {
        $uri = $request->getRequestUri();

        // 1. static assets
        if (preg_match('#\.(css|js|png|jpg|jpeg|gif|svg|ico)$#i', $uri)) {
            return true;
        }

        // 2. chrome devtools / well-known
        if (strpos($uri, '/.well-known/') === 0) {
            return true;
        }

        // 3. file umum
        if (in_array($uri, [
            '/favicon.ico',
            '/robots.txt'
        ])) {
            return true;
        }

        return false;
    }
}