<?php

require_once 'Zend/Controller/Action.php';
require_once 'helpers/settings.php';
require_once 'helpers/oauth_settings.php';

class AdvanceController extends Zend_Controller_Action
{
	public function init() {
		$this->view->baseUrl = $this->_request->getBaseUrl();
	}

	public function indexAction(){

	}
}
