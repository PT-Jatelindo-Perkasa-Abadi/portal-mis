<?php
class App_Service_EmailTemplate
{
    public static function render($template, array $data = [], $subject = 'Notification') {
        $contentView = new Zend_View();

        $contentView->setScriptPath(
            APPLICATION_PATH .
            '/views/emails/templates'
        );

        foreach ($data as $key => $value) {
            $contentView->$key = $value;
        }

        $content = $contentView->render($template . '.phtml');
        $layoutView = new Zend_View();

        $layoutView->setScriptPath(
            APPLICATION_PATH .
            '/views/emails'
        );

        $layoutView->subject = $subject;
        $layoutView->content = $content;

        $layoutView->logoUrl = self::getAssetUrl('/assets/img/logo-mis-email.png');
        $layoutView->backgroundHeader = self::getAssetUrl('/assets/img/background-header-email.png');

        return $layoutView->render('layouts/default.phtml');
    }

    protected static function getAssetUrl($path) {

        $request = Zend_Controller_Front::getInstance()->getRequest();

        /**
         * SCHEME
         */
        $scheme = $request->getScheme();

        /**
         * HOST
         */
        $host = $request->getHttpHost();

        /**
         * BASE URL
         */
        $baseUrl = $request->getBaseUrl();

        return
            $scheme .
            '://' .
            $host .
            $baseUrl .
            $path;
    }
}