<?php

require_once 'Zend/Controller/Action.php';

class AboutController extends Zend_Controller_Action
{
    public function init()
    {
        $this->view->baseUrl = $this->_request->getBaseUrl();
        
    }

    public function indexAction()
    {
    	$this->view->intro_text = "This is the Foaf Editor"; 
    }
}