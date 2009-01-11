<?php
require_once 'helpers/settings.php';
require_once 'helpers/sparql.php';
require_once 'Zend/Controller/Action.php';
require_once 'FoafData.php';

class BuilderController extends Zend_Controller_Action
{
    public function init() {
       $this->view->baseUrl = $this->_request->getBaseUrl();
    }

    public static function getForm()
    {
    	
    }

    public function indexAction(){	

	$defaultNamespace = new Zend_Session_Namespace('Garlik');
	
	if($defaultNamespace->authenticated){
		$this->view->authenticated = 'true';
	
		$foafData = FoafData::getFromSession();
		if($foafData && $foafData->getUri() && $foafData->getUri() != 'http://foafbuilder.qdos.com/people/example.com/myopenid/foaf.rdf'){
			$this->view->publicUri = $foafData->getUri();
		}
		
		$privateFoafData = FoafData::getFromSession(false);
		if($privateFoafData && $privateFoafData->getUri() && $privateFoafData->getUri() != 'http://private.qdos.com/oauth/example.com/myopenid/data/foaf.rdf'){
			$this->view->privateUri = $privateFoafData->getUri();
		}

	} else {
		$this->view->authenticated = 'false';
	}
	
    }
}

