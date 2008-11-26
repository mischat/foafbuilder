<?php
require_once 'helpers/settings.php';
require_once 'helpers/sparql.php';
require_once 'Zend/Controller/Action.php';

class BuilderController extends Zend_Controller_Action
{
    public function init() {
       $this->view->baseUrl = $this->_request->getBaseUrl();
    }
	public static function getForm()
    {
    	
    }
	public function indexAction(){	
		$url = @$_GET['url'];
    	
    	if($url){
    		$this->view->uri = $url;
    	}
	}
}

