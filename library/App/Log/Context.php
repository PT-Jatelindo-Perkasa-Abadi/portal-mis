<?php
class App_Log_Context
{
    public static function getIp()
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        return $ip === '::1' ? '127.0.0.1' : $ip;
    }

    public static function getActivityId()
    {
        if (!Zend_Registry::isRegistered('activity_id')) {
            Zend_Registry::set('activity_id', uniqid());
        }

        return Zend_Registry::get('activity_id');
    }

    public static function mask($data)
    {
        $sensitive = ['password', 'token', 'authorization'];

        if (is_array($data)) {
            foreach ($data as $k => $v) {
                if (in_array(strtolower($k), $sensitive)) {
                    $data[$k] = '*****';
                } elseif (is_array($v)) {
                    $data[$k] = self::mask($v);
                }
            }
        }

        return $data;
    }
}