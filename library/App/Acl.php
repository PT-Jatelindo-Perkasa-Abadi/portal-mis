<?php

class App_Acl extends Zend_Acl
{
    public function __construct()
    {
        /**
         * ======================
         * ROLES
         * ======================
         */
        $this->addRole(new Zend_Acl_Role('guest'));
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
        $this->add(new Zend_Acl_Resource('auth:index'));
        $this->add(new Zend_Acl_Resource('default:index'));
        $this->add(new Zend_Acl_Resource('profile:index'));
        $this->add(new Zend_Acl_Resource('error:index'));
        $this->add(new Zend_Acl_Resource('user:index'));

        /**
         * ======================
         * GUEST (BELUM LOGIN)
         * ======================
         */
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