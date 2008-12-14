<?php

require_once 'Zend/Controller/Action.php';
require_once 'helpers/oauth_settings.php';

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
	
	if (isset($_POST['openid_action']) && $_POST['openid_action'] == "login" && !empty($_POST['openid_identifier'])) {
    		$openid = $_POST['openid_identifier'];	
    		error_log("Please say something is happening here $openid");
		$consumer = new Zend_OpenId_Consumer();

		if (!$consumer->login($openid)) {
			error_log("OpenID login failed.");
			$this->_helper->redirector('index?status=fail');
		} 

	} else if (isset($_GET['openid_mode'])) {
	       if ($_GET['openid_mode'] == "id_res") {
			$consumer = new Zend_OpenId_Consumer();
			if ($consumer->verify($_GET, $id)) {
				error_log("OpenID authenication success");
				$defaultNamespace->authenticated = true;
				$myopenidurl = $this->makeOpenIDUrl($id);
				$defaultNamespace->url = $myopenidurl;
				error_log("SO here is id $id, and there is the url".$myopenidurl);

				$store = OAuthStore::instance();
				$uid = $store->checkOpenIDExists($id);
				if ($uid) {
					error_log('[oauth] OpenID success but account is alredy setup.');
					//TODO MISCHA ... load in any existing info !
				} else {
					error_log('[oauth] OpenID AND NO ACCOUNT EXISTS! .');
					//TODO MISCHA .... create a new account
        				try {
				                $store = OAuthStore::instance();
						if ($id != "" && $myopenidurl != "") {
				                        $store->addUser($id,$myopenidurl);
							error_log("[oauth] created a new OAuth for OpenID $id");
                				}
        				} catch (OAuthException $e) {
				                error_log('[oauth] Error: ' . $e->getMessage());
					}
				}	
				$this->_helper->redirector('../index');
			}
		} else if ($_GET['openid_mode'] == "cancel") {
			error_log("Openid login cancelled");
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
