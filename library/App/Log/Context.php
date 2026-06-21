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

    public static function getUserAgent()
    {
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        $browser = "Unknown";

        // Deteksi Firefox
        if (preg_match('/Firefox/i', $user_agent)) {
            $browser = 'Firefox';
        } 
        // Deteksi Chrome / Edge berbasis Chromium
        elseif (preg_match('/Edg/i', $user_agent)) {
            $browser = 'Microsoft Edge';
        } 
        elseif (preg_match('/Chrome/i', $user_agent)) {
            $browser = 'Chrome';
        } 
        // Deteksi Safari
        elseif (preg_match('/Safari/i', $user_agent)) {
            $browser = 'Safari';
        } 
        // Deteksi Opera
        elseif (preg_match('/Opera|OPR/i', $user_agent)) {
            $browser = 'Opera';
        }

        return $browser;
    }

    public static function getDeviceType()
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'];
        $device = "Unknown";

        if (preg_match('/(android|iphone|ipad|ipod|blackberry|windows phone)/i', $userAgent)) {
            $device = "Mobile Device";
        } else {
            $device = "Desktop/PC";
        }

        return $device;
    }

    public static function getClientOS() {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $osPlatform = "Unknown OS";

        $osArray = [
            '/windows nt 10/i'      =>  'Windows 10 / 11',
            '/windows nt 6.3/i'     =>  'Windows 8.1',
            '/windows nt 6.2/i'     =>  'Windows 8',
            '/windows nt 6.1/i'     =>  'Windows 7',
            '/macintosh|mac os x/i' =>  'Mac OS X',
            '/mac_powerpc/i'        =>  'Mac OS 9',
            '/linux/i'              =>  'Linux',
            '/ubuntu/i'             =>  'Ubuntu',
            '/iphone/i'             =>  'iPhone',
            '/ipod/i'               =>  'iPod',
            '/ipad/i'               =>  'iPad',
            '/android/i'            =>  'Android',
            '/blackberry/i'         =>  'BlackBerry',
        ];

        foreach ($osArray as $regex => $value) {
            if (preg_match($regex, $userAgent)) {
                $osPlatform = $value;
                break;
            }
        }

        return $osPlatform;
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