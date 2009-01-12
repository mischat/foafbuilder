<?php

require_once 'Zend/Controller/Action.php';

class CrossDomainController extends Zend_Controller_Action
{
    public function init()
    {
        $this->view->baseUrl = $this->_request->getBaseUrl();
        
    }

    public function editIframeAction()
    {
    }
}
