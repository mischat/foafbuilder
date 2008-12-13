<?php

require_once 'Zend/Controller/Action.php';
require_once 'helpers/settings.php';

class ContinueController extends Zend_Controller_Action
{
	public function init() {
		$this->view->baseUrl = $this->_request->getBaseUrl();
	}

	public function indexAction(){


	}

    //TODO MISCHA DIRTY
    public function doOpenidAction() {

	$defaultNamespace = new Zend_Session_Namespace('Garlik');
	$defaultNamespace->authenticated = false;
	
	if (isset($_POST['openid_action']) && $_POST['openid_action'] == "login" && !empty($_POST['openid_identifier'])) {
    		$openid = $_POST['openid_identifier'];	
		$consumer = new Zend_OpenId_Consumer();

		if (!$consumer->login($openid)) {
			error_log("OpenID login failed.");
			$this->_helper->redirector('index?status=fail');
		} 
	} else if (isset($_GET['openid_mode'])) {
	       if ($_GET['openid_mode'] == "id_res") {
			$consumer = new Zend_OpenId_Consumer();
			if ($consumer->verify($_GET, $id)) {
				require_once 'WriterController.php';
				error_log("OpenID Authenication pass!");
				$defaultNamespace->authenticated = true;
				$defaultNamespace->url = $this->makeOpenIDUrl($id);
				error_log("SO here is id $id, and there is the url".$this->makeOpenIdUrl($id));
				WriterController::writeFoafGarlikServersAction();
				$this->_helper->redirector('../builder');
			}
		} else if ($_GET['openid_mode'] == "cancel") {
			error_log("OpenID login Cancelled");
			$this->_helper->redirector('index?status=cancelled');
		}
	} else {
		error_log("Openid login attempt with no value");
	}
    } //end openid

    private function makeOpenIDUrl ($url) { 
	$url = preg_replace('/^https{0,1}:\/\//','',$url); 
	$url = urlencode ($url); 
	$url = preg_replace('/%2F/',"/",$url); 
	return $url;  
    }

}
