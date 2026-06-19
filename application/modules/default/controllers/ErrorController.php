<?php
class Default_ErrorController extends Zend_Controller_Action
{
    public function errorAction()
    {
        $errors = $this->_getParam('error_handler');

        if (!$errors) {
            return $this->render('error');
        }

        $this->view->exception = $errors->exception;
        $this->view->request   = $errors->request;

        switch ($errors->type) {

            // 🔍 404 - controller/action tidak ditemukan
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ROUTE:
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:

                $this->getResponse()->setHttpResponseCode(404);
                return $this->render('404');

            // ❌ 403 - forbidden (dari ACL)
            case 'FORBIDDEN':

                $this->getResponse()->setHttpResponseCode(403);
                return $this->render('403');

            // 💥 500 - general error
            default:

                $this->getResponse()->setHttpResponseCode(500);
                return $this->render('500');
        }
    }

    public function forbiddenAction()
    {
        $this->_helper->layout->setLayout('error-layout');
    }

    public function generalAction()
    {
        $this->_helper->layout->setLayout('error-layout');
    }
}