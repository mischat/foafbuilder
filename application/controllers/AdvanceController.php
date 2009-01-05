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
		//shove the uris into the view for display
		$defaultNamespace = new Zend_Session_Namespace('Garlik');

        	if($defaultNamespace->authenticated){

                	$foafData = FoafData::getFromSession();
                	if($foafData && $foafData->getUri() && $foafData->getUri() != 'http://foafbuilder.qdos.com/people/example.com/myopenid/foaf.rdf'){
                        	$this->view->publicUri = $foafData->getUri();
                	}
               
                	$privateFoafData = FoafData::getFromSession(false);
               	 	if($privateFoafData && $privateFoafData->getUri() && $privateFoafData->getUri() != 'http://private.qdos.com/oauth/example.com/myopenid/data/foaf.rdf'){
                        	$this->view->privateUri = $privateFoafData->getUri();
                	}
        
		}
	}
}
