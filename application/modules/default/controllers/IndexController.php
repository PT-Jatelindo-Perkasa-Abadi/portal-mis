<?php
class Default_IndexController extends Zend_Controller_Action
{
    public function init()
    {
        Zend_Session::start();
    }
    public function indexAction()
    {
        $this->view->headTitle('Dashboard');
    }
}