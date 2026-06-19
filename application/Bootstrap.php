<?php
class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
    protected function _initAutoload()
    {
        $autoloader = new Zend_Application_Module_Autoloader([
            'namespace' => 'App',
            'basePath' => APPLICATION_PATH,
        ]);

        Zend_Loader_Autoloader::getInstance()
            ->registerNamespace('App_');

        return $autoloader;
    }

    protected function _initConfig()
    {
        $config = new Zend_Config($this->getOptions(), true);
        Zend_Registry::set('config', $config);
        return $config;
    }

    protected function _initSession()
    {
        Zend_Session::start();
    }

    protected function _initRequestId()
    {
        $front = Zend_Controller_Front::getInstance();
        $request = $front->getRequest();

        $requestId = $request ? $request->getHeader('X-Request-ID') : null;

        if (!$requestId) {
            $requestId = uniqid();
        }

        Zend_Registry::set('request_id', $requestId);

        return $requestId;
    }

    protected function _initActivityId()
    {
        $activityId = uniqid();
        Zend_Registry::set('activity_id', $activityId);

        return $activityId;
    }

    protected function _initLogger()
    {
        Zend_Session::start();
        date_default_timezone_set('Asia/Jakarta');
        $date = date('M-d-Y');

        $activityLogs = APPLICATION_PATH . "/logs";
        $logfile_format = "$date.log";

        if (!is_dir($activityLogs)) {
            mkdir($activityLogs, 0777, TRUE);
        }

        if (!file_exists($activityLogs . "/" . $logfile_format)) {
            $_ = fopen($activityLogs . "/" . $logfile_format, 'w') or die("Cannot open file:  $logfile_format");
            chmod($activityLogs . "/" . $logfile_format, 0777);
        }

        $log_Writer_Stream = new Zend_Log_Writer_Stream($activityLogs . "/" . $logfile_format);
        $log_format = '%timestamp% %priorityName%: %message%' . PHP_EOL;
        $zend_Log_Formatter = new Zend_Log_Formatter_Simple($log_format);
        $log_Writer_Stream->setFormatter($zend_Log_Formatter);
        $zend_Log = new Zend_Log($log_Writer_Stream);
        $zend_Log->setTimestampFormat("d-M-Y H:i:s");
        Zend_Registry::set('logger', $zend_Log);
    }

    protected function _initAcl()
    {
        $front = Zend_Controller_Front::getInstance();
        $front->registerPlugin(new App_Plugin_Acl());
    }

    protected function _initPlugins()
    {
        $front = Zend_Controller_Front::getInstance();

        $front->registerPlugin(new App_Plugin_Layout());
        $front->registerPlugin(new App_Plugin_SecurityHeaders());
    }

    protected function _initViewHelpers()
    {
        $view = new Zend_View();

        $view->addHelperPath(
            APPLICATION_PATH . '/../library/App/View/Helper',
            'App_View_Helper'
        );

        Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer')->setView($view);
    }

    protected function _initErrorHandler()
    {
        $front = Zend_Controller_Front::getInstance();

        $front->registerPlugin(
            new Zend_Controller_Plugin_ErrorHandler([
                'module' => 'default',
                'controller' => 'error',
                'action' => 'error'
            ])
        );
    }
}