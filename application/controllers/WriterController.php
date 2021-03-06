<?php

require_once 'Zend/Controller/Action.php';
require_once 'helpers/settings.php';
require_once 'helpers/write-utils.php';
require_once 'helpers/security_utils.php';
require_once 'helpers/sparql.php';


class WriterController extends Zend_Controller_Action
{
    public function init() {
        $this->view->baseUrl = $this->_request->getBaseUrl();
    }

    public function writeFoafn3Action() {
        require_once 'FoafData.php';
        
        /*get the private and public stuff from the session*/
        $privateFoafData = FoafData::getFromSession(false);
    	$publicFoafData = FoafData::getFromSession(true);
    	
    	/*if there is a uri then we need to use that*/
        $newDocUri = @$_POST['uri'];

	if (!check_key('post')) {
		error_log("POST hijack attempt ");
		exit();
	}
        
        /*where the data will be stored in the view*/
        $this->view->data = new Object();
        
        if($privateFoafData) {
        	$this->doWrite($privateFoafData,$newDocUri,true);
        }
        if($publicFoafData){
        	$this->doWrite($publicFoafData,$newDocUri,true);
        }
    }

  /* This should remove all unnessary Language Tags from the outputted RDF */
  public function removeLanguageTags($results) {

	foreach ($results->triples as $result) {
	
		if ($result->pred->uri == "http://xmlns.com/foaf/0.1/jabberID") {
			$result->obj->lang = NULL;
		} else if ($result->pred->uri == "http://xmlns.com/foaf/0.1/aimChatID") {
			$result->obj->lang = NULL;
		} else if ($result->pred->uri == "http://xmlns.com/foaf/0.1/msnChatID") {
			$result->obj->lang = NULL;
		} else if ($result->pred->uri == "http://xmlns.com/foaf/0.1/mbox_sha1sum") {
			$result->obj->lang = NULL;
		} else if ($result->pred->uri == "http://purl.org/dc/elements/1.1/format") {
			$result->obj->lang = NULL;
		} else if ($result->pred->uri == "http://xmlns.com/foaf/0.1/myersBriggs") {
			$result->obj->lang = NULL;
		} else if ($result->pred->uri == "http://xmlns.com/foaf/0.1/accountName") {
			$result->obj->lang = NULL;
		} else if ($result->pred->uri == "http://xmlns.com/wot/0.1/keyid") {
			$result->obj->lang = NULL;
		} else if ($result->pred->uri == "http://purl.org/net/inkel/rdf/schemas/lang/1.1#masters") {
			$result->obj->lang = NULL;
		} else if ($result->pred->uri == "http://www.megginson.com/exp/ns/airports#icao") {
			$result->obj->lang = NULL;
		} else if ($result->pred->uri == "http://www.megginson.com/exp/ns/airports#iata") {
			$result->obj->lang = NULL;
		}
	}
	return $results;
  }

  public function writeWebDavAction() {
        if (!check_key('post')) {
                error_log("POST hijack attempt ");
                exit();
        }
	$uri_ori = $_POST['uri'];
	if (!preg_match('/^https{0,1}:\/\/.*\/[^\/]*$/',$uri_ori)) {
		echo "You need to point to a the FOAF Document URI desired e.g. http://example.com/foaf.rdf";
		return true;
	}
	$username = $_POST['username'];
	if ($username == "") {
		echo "You need to input a username";
		return true;
	}
	$password = $_POST['password'];
	if ($username == "") {
		echo "You need to input a password";
		return true;
	}

	$uri = preg_replace('/^http:\/\//','',$uri_ori);

	if (preg_match('/\/$/',$uri)) {
		/*If it ends in a slash, then we should add foaf.rdf to the end of it!*/
		$uri .= "foaf.rdf";
	}

	$remote_path = "webdav://$username:$password@$uri";
	require_once( 'HTTP/WebDAV/Client.php' );
	$mode = 'w';
	$options = array();
	$opend_path = array();
	$webdav_client = new HTTP_WebDAV_Client_Stream();
	$status = $webdav_client->stream_open( $remote_path, $mode, $options, $opend_path );
	if ( $status === false ) {
		error_log( 'Webdav stream_open failed for private foaf' );
		echo ( 'Webdav stream_open failed for private foaf' );
		return true;
	}

        require_once 'FoafData.php';

	if ($_POST['privacy'] == "private") {
		$publicFoafData = FoafData::getFromSession(false);
	} else {
		$publicFoafData = FoafData::getFromSession(true);
	}
	$this->view->model = $publicFoafData->getModel();
	$this->view->model->setBaseUri(NULL);
	$result = $this->view->model->find(NULL, NULL, NULL);

        $this->doPostProcessing($publicFoafData);
	$result = $this->removeLanguageTags($result);

	$data = $result->writeRdfToString();
	$hash = $publicFoafData->getURI();

 	//very dirty !
	$data = str_replace($hash,"",$data);

	$status = $webdav_client->stream_write( $data );
	if ( $status === false ) {
		error_log( 'Webdav stream_write failed for public foaf' );
		echo( 'Webdav stream_write failed for public foaf' );
		return true;
	}

	$webdav_client->stream_close();
	error_log("WebDAV success writing to webdav");
	return false;
  }

  /* This function should be used to download a FOAF file to your desktop */
  public function writeFoafn3PrivateAction() {
        require_once 'FoafData.php';
	//this is inside an action in one of your controllers:
    	$publicFoafData = FoafData::getFromSession(false);

	$this->view->model = $publicFoafData->getModel();
	$this->view->model->setBaseUri(NULL);
	$result = $this->view->model->find(NULL, NULL, NULL);

        $this->doPostProcessing($publicFoafData);
	$result = $this->removeLanguageTags($result);

	$data = $result->writeRdfToString();
	$hash = $publicFoafData->getURI();

 	//very dirty !
	$data = str_replace($hash,"",$data);

	$this->_helper->layout->disableLayout();
	$response = $this->getResponse();
        $response->setHeader('Content-Type', 'application/xml', true)
            ->setHeader('Content-Disposition', 'attachment;filename=private_foaf.rdf', true)
	    ->setHeader('Content-Length', strlen($data), true)
	    ->appendBody($data);
    }

  /* This function should be used to download a FOAF file to your desktop */
  public function writeFoafn3PublicAction() {
        require_once 'FoafData.php';
	//this is inside an action in one of your controllers:
    	$publicFoafData = FoafData::getFromSession(true);

	$this->view->model = $publicFoafData->getModel();
	$this->view->model->setBaseUri(NULL);
	$result = $this->view->model->find(NULL, NULL, NULL);

	$this->doPostProcessing($publicFoafData);
	$result = $this->removeLanguageTags($result);

	$data = $result->writeRdfToString();
	$hash = $publicFoafData->getURI();

 	//very dirty !
	$data = str_replace($hash,"",$data);

	$this->_helper->layout->disableLayout();
	$response = $this->getResponse();
        $response->setHeader('Content-Type', 'application/xml', true)
            ->setHeader('Content-Disposition', 'attachment;filename=public_foaf.rdf', true)
	    ->setHeader('Content-Length', strlen($data), true)
	    ->appendBody($data);
    }
    
  public function doPostProcessing(&$foafData) {
	$docUriRes = new Resource($foafData->getURI());
	$predUriRes = new Resource("http://purl.org/dc/terms/modified");
	$results = $foafData->getModel()->find($docUriRes,$predUriRes,NULL);

	if ($results) {
		foreach ($results->triples as $result) {
			$foafData->getModel()->remove($result);
		}
	}
	$foafData->getModel()->addWithoutDuplicates(new Statement($docUriRes,$predUriRes,new Literal(date("Y-m-d"))));
  }

 
  public function writeFoafAction() {
        require_once 'FoafData.php';
        
        /*get the private and public stuff from the session*/
        $privateFoafData = FoafData::getFromSession(false);
    	$publicFoafData = FoafData::getFromSession(true);
    	
    	/*if there is a uri then we need to use that*/
        $newDocUri = @$_POST['uri'];

	if (!check_key('post')) {
		error_log("POST hijack attempt ");
		exit();
	}
        
        /*where the data will be stored in the view*/
        $this->view->data = new Object();
        
        if($privateFoafData) {
        	$this->doWrite($privateFoafData,$newDocUri);
        }
        if($publicFoafData){
        	$this->doWrite($publicFoafData,$newDocUri);
        }
    }
  
    public static function writeFoafGarlikServersAction() {
        $defaultNamespace = new Zend_Session_Namespace('Garlik');
	$privateFoafData = FoafData::getFromSession(false);
    	$publicFoafData = FoafData::getFromSession(true);

        if ($defaultNamespace->authenticated == true) {
		WriterController::writeFoafPublicAction();
		WriterController::writeFoafPrivateAction();
	} else {
		$_SESSION['task'] = "both";
		return null;
	}

    }

    public static function writeFoafPrivateAction() {
	$privateFoafData = FoafData::getFromSession(false);
        $defaultNamespace = new Zend_Session_Namespace('Garlik');

        //Check if authenicated
        if ($defaultNamespace->authenticated == true) {
		error_log("Writing private triples to oauth");
		$uri = $privateFoafData->getURI();
		$tempmodel = unserialize(serialize($privateFoafData->getModel()));
		$tempmodel->setBaseUri(NULL);
		$result = $tempmodel->find(NULL, NULL, NULL);

		$data = $result->writeRdfToString('nt');
		$xml = $result->writeRdfToString();
		if (strlen($data) > 0 ) {
			$cachename = cache_filename($uri);
			if (!file_exists(PRIVATE_DATA_DIR.$cachename)) {
				create_cache($cachename,PRIVATE_DATA_DIR);
			}
			file_put_contents(PRIVATE_DATA_DIR.$cachename,$xml);	
			$result = sparql_put_string(PRIVATE_EP,$uri,$data);
			if ($result == "201") {
				error_log("data created in the private model $uri");
			}
		} else {
			error_log('[foafeditor] rdf stream empty for the private data nothing to write to:'.$uri);
		}
		echo "true";
	} else {
		$_SESSION['task'] = "private";
		return null;
	}
    }

    public static function writeFoafPublicAction() {
    	$publicFoafData = FoafData::getFromSession(true);
        $defaultNamespace = new Zend_Session_Namespace('Garlik');

        //Check if authenicated
        if ($defaultNamespace->authenticated == true) {
		$tempmodel = null;
		
		$uri = $publicFoafData->getURI();
		$tempmodel = unserialize(serialize($publicFoafData->getModel()));
		$tempmodel->setBaseUri(NULL);
		$result = $tempmodel->find(NULL, NULL, NULL);

		$data = $result->writeRdfToString('nt');
		$xml = $result->writeRdfToString();
		if (strlen($data) > 0 ) {
			$cachefilename = cache_filename($uri);
			error_log($cachefilename);
			if (!file_exists(PUBLIC_DATA_DIR.$cachefilename)) {
				create_cache($cachefilename,PUBLIC_DATA_DIR);
			}
			file_put_contents(PUBLIC_DATA_DIR.$cachefilename, $xml);
			$result = sparql_put_string(PUBLIC_EP,$uri,$data);
			if ($result == "201") {
				error_log("data created in public ep $uri");
			}
		} else {
			error_log('[foafeditor] rdf stream empty nothing to write to:'.$tempuri);
		}
		echo "true";
	} else {
		$_SESSION['task'] = "public";
		return null;
	}
    }

    private function doWrite($foafData,$newDocUri,$writeNtriples){
	    if (!$foafData) {
		return;
	    }
	    $tempmodel = unserialize(serialize($foafData->getModel()));
            $tempmodel->setBaseUri(NULL);
            
            $result = $tempmodel->find(NULL, NULL, NULL);
	
            if($foafData->isPublic){
            	if($writeNtriples){
            		$this->view->data->public = $result->writeRdfToString('n3');
            	} else {
            		$this->view->data->public = $result->writeRdfToString();
            	}
            } else {
            	if($writeNtriples){
            		$this->view->data->private = $result->writeRdfToString('n3');
            	} else {
            		$this->view->data->private = $result->writeRdfToString();
            	}	
            }    	
    }

}
