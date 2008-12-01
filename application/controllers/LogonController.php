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

	if (isset($_POST['openid_action']) && $_POST['openid_action'] == "login" && !empty($_POST['openid_identifier'])) {
    		$openid = $_POST['openid_identifier'];	
    		error_log("Please say something is happening here $openid");

		$consumer = new Zend_OpenId_Consumer();

		var_dump($consumer);
		//if (!$consumer->login($_POST['openid_identifier'])) {
		if (!$consumer->login($openid)) {
			error_log("OpenID login failed.");
		} 
	} else if (isset($_GET['openid_mode'])) {
    		error_log("Please say something is happening here: ".$_GET['openid_mode']);
	       if ($_GET['openid_mode'] == "id_res") {
			$consumer = new Zend_OpenId_Consumer();
			if ($consumer->verify($_GET, $id)) {
			    error_log("VALID " . htmlspecialchars($id));
				//ADD Authenicated to the Session!
			} else {
			    error_log("INVALID " . htmlspecialchars($id));
				//REMOVE from session
			}
		} else if ($_GET['openid_mode'] == "cancel") {
			error_log("CANCELED");
		}
	} else {
		error_log("Openid login attempt with no value");
	}
    } //end openid
}
