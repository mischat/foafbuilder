<?php

require_once 'Zend/Controller/Action.php';

class IndexController extends Zend_Controller_Action
{
    public function init() {
        $this->view->baseUrl = $this->_request->getBaseUrl();
    }
    
    public function indexAction(){
    	
    }
}
