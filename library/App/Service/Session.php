<?php

class App_Service_Session
{
    protected static $namespace = 'app';

    protected static function getNamespace()
    {
        return new Zend_Session_Namespace(self::$namespace);
    }

    // =========================
    // BASIC SESSION
    // =========================
    public static function set($key, $value)
    {
        $session = self::getNamespace();
        $session->$key = $value;

        // update activity setiap set
        $session->last_activity = time();
    }

    public static function get($key)
    {
        $session = self::getNamespace();
        return isset($session->$key) ? $session->$key : null;
    }

    public static function destroy()
    {
        Zend_Session::destroy(true);
    }

    public static function isLoggedIn()
    {
        return self::get('user') !== null;
    }

    // =========================
    // SESSION ACTIVITY / TIMEOUT
    // =========================
    public static function refreshActivity()
    {
        $session = self::getNamespace();
        $session->last_activity = time();
    }

    public static function isExpired($timeout = 1800) // 30 menit
    {
        $session = self::getNamespace();

        if (!isset($session->last_activity)) {
            return false;
        }

        return (time() - $session->last_activity) > $timeout;
    }

    // =========================
    // EXPIRED FLAG (UX MESSAGE)
    // =========================
    public static function setExpiredFlag()
    {
        $session = self::getNamespace();
        $session->expired = true;
    }

    public static function getExpiredFlag()
    {
        $session = self::getNamespace();

        if (!empty($session->expired)) {
            unset($session->expired);
            return true;
        }

        return false;
    }

    // =========================
    // INTENDED URL (SAFE)
    // =========================
    public static function setRedirectUrl($url)
    {
        $session = self::getNamespace();

        if (self::isSafeInternalUrl($url)) {
            $session->redirect_after_login = $url;
        }
    }

    public static function getRedirectUrl($default = '/')
    {
        $session = self::getNamespace();

        if (!empty($session->redirect_after_login)) {
            $url = $session->redirect_after_login;
            unset($session->redirect_after_login);

            if (self::isSafeInternalUrl($url)) {
                return $url;
            }
        }

        return $default;
    }

    protected static function isSafeInternalUrl($url)
    {
        if (!is_string($url) || empty($url)) {
            return false;
        }

        // harus dimulai dengan '/'
        if (strpos($url, '/') !== 0) {
            return false;
        }

        // block external URL
        if (preg_match('#^//|https?://#i', $url)) {
            return false;
        }

        // block newline injection
        if (strpos($url, "\n") !== false || strpos($url, "\r") !== false) {
            return false;
        }

        return true;
    }

    public static function clearUser()
    {
        $session = self::getNamespace();

        unset($session->user);
        unset($session->last_activity);
    }
}