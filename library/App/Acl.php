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
        $this->addRole(new Zend_Acl_Role('rekon'), 'guest');

        /**
         * ======================
         * RESOURCES
         * ======================
         */
        $this->add(new Zend_Acl_Resource('auth:index'));
        $this->add(new Zend_Acl_Resource('default:index'));
        $this->add(new Zend_Acl_Resource('profile:index'));
        $this->add(new Zend_Acl_Resource('error:index'));

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
         * REKON
         * ======================
         */


        /**
         * ======================
         * ADMIN
         * ======================
         */
        $this->allow('admin'); // full akses


        /**
         * ======================
         * PROFILE
         * ======================
         */
        $this->allow('maker', 'profile:index');
        $this->allow('checker', 'profile:index');
        $this->allow('rekon', 'profile:index');
        $this->allow('admin', 'profile:index');

        /**
         * ======================
         * LOGOUT
         * ======================
         */
        $this->allow('maker', 'auth:index', ['logout']);
        $this->allow('checker', 'auth:index', ['logout']);
        $this->allow('rekon', 'auth:index', ['logout']);
        $this->allow('admin', 'auth:index', ['logout']);
    }
}