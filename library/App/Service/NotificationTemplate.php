<?php

class App_Service_NotificationTemplate
{
    /**
     * Disbursement Approved
     */
    public static function disbursementApproved($disbursementCode, $title)
    {
        return sprintf(
            'Disbursement "%s - %s" is approved',
            $disbursementCode,
            $title
        );
    }

    /**
     * Disbursement Rejected
     */
    public static function disbursementRejected($disbursementCode, $title)
    {
        return sprintf(
            'Disbursement "%s - %s" is rejected',
            $disbursementCode,
            $title
        );
    }

    /**
     * Disbursement Edited by Maker
     */
    public static function disbursementEdited($disbursementCode, $title, $makerName)
    {
        return sprintf(
            'Disbursement "%s - %s" is edited by %s',
            $disbursementCode,
            $title,
            $makerName
        );
    }

    /**
     * Disbursement Created
     */
    public static function disbursementCreated($disbursementCode, $title, $makerName)
    {
        return sprintf(
            'Disbursement "%s - %s" is created by Maker %s',
            $disbursementCode,
            $title,
            $makerName
        );
    }

    /**
     * Disbursement Canceled
     */
    public static function disbursementCanceled($disbursementCode, $title, $checkerName)
    {
        return sprintf(
            'Disbursement "%s - %s" is canceled by Checker %s',
            $disbursementCode,
            $title,
            $checkerName
        );
    }
}