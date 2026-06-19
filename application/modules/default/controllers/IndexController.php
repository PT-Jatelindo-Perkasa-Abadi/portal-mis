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

        $data = [];

        // Scope the summary to the user's group. Admin & rekon see every group
        // (no restriction) — kept for later; today only maker & checker have a
        // dashboard, and they are scoped to their own group ('0' = none -> zeros).
        $user = App_Service_Session::get('user');
        $role = is_array($user) ? ($user['role'] ?? '') : '';
        $groupId = in_array($role, ['admin', 'rekon'], true)
            ? ''
            : (string) ($user['groupId'] ?? 0);

        $api = new App_Service_Api();
        $_ = $api->authorization();
        $response = $api->sp('dashboard_summary', [$groupId]);

        
        if ($response['responseCode'] == '2002200') {
            if ($response['data']) {
                $data = $response['data'][0];
            }
        }

        $this->view->data = $data;
    }
}