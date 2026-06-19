<?php

class App_Auth
{
    public static function user()
    {
        return App_Service_Session::get('user');
    }

    public static function role()
    {
        $user = self::user();

        return (!empty($user) && isset($user['role']))
            ? strtolower($user['role'])
            : 'guest';
    }

    public static function check()
    {
        return !empty(self::user());
    }

    public static function isExpired()
    {
        return App_Service_Session::isExpired();
    }
}