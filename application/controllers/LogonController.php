<?php

require_once 'Zend/Controller/Action.php';

class LogonController extends Zend_Controller_Action
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
	
	if(isset($_POST['openid_action'])){
		$defaultNamespace->url = $this->makeOpenIdUrl($_POST['openid_identifier']);
	}

	if (isset($_POST['openid_action']) && $_POST['openid_action'] == "login" && !empty($_POST['openid_identifier'])) {
    		$openid = $_POST['openid_identifier'];	
    		error_log("Please say something is happening here $openid");
		$consumer = new Zend_OpenId_Consumer();

		if (!$consumer->login($openid)) {
			error_log("OpenID login failed.");
			$this->_helper->redirector('index?status=fail');

		} 
	} else if (isset($_GET['openid_mode'])) {
    		error_log("Please say something is happening here: ".$_GET['openid_mode']);
	       if ($_GET['openid_mode'] == "id_res") {
			$consumer = new Zend_OpenId_Consumer();
			if ($consumer->verify($_GET, $id)) {
				$defaultNamespace->authenticated = true;
				$this->_helper->redirector('../index');
			}
		} else if ($_GET['openid_mode'] == "cancel") {
			error_log("CANCELED");
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
	error_log("DOES THIS HAPPEN! $url");
	return $url;  
    }

}
