<?php

class App_Service_Notification
{
    /**
     * Get unread notification count by role id
     *
     * SP:
     * count_unread_notifications
     *
     * @param int $roleId
     * @return int
     */
    public static function getUnreadCount($roleId)
    {
        $api = new App_Service_Api();

        $api->authorization();

        $result = $api->sp(
            'count_unread_notifications',
            [$roleId]
        );

        return (int) ($result['data'][0]['total'] ?? 0);
    }
}