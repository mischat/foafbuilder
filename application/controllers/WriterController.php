<?php

require_once 'Zend/Controller/Action.php';

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

  /* This function should be used to download a FOAF file to your desktop */
  public function writeFoafn3PublicAction() {
        require_once 'FoafData.php';
	//this is inside an action in one of your controllers:
    	$publicFoafData = FoafData::getFromSession(true);

	$this->view->model = $publicFoafData->getModel();
	$this->view->model->setBaseUri(NULL);
	$result = $this->view->model->find(NULL, NULL, NULL);

	$result = $this->removeLanguageTags($result);

	$data = $result->writeRdfToString();
	$hash = $publicFoafData->getURI();

 	//very dirty !
	$data = str_replace($hash,"",$data);

	$this->_helper->layout->disableLayout();
	$response = $this->getResponse();
        $response->setHeader('Content-Type', 'text/plain', true)
            ->setHeader('Content-Disposition', 'attachment;filename=foaf.rdf', true)
	    ->setHeader('Content-Length', strlen($data), true)
	    ->appendBody($data);
    }
    
  public function writeFoafAction() {
        require_once 'FoafData.php';
        
        /*get the private and public stuff from the session*/
        $privateFoafData = FoafData::getFromSession(false);
    	$publicFoafData = FoafData::getFromSession(true);
    	
    	/*if there is a uri then we need to use that*/
        $newDocUri = @$_POST['uri'];
        
        /*where the data will be stored in the view*/
        $this->view->data = new Object();
        
        if($privateFoafData) {
        	$this->doWrite($privateFoafData,$newDocUri);
        }
        if($publicFoafData){
        	$this->doWrite($publicFoafData,$newDocUri);
        }
    }
    
    public function writeFoafPrivateAction() {
	//TODO MISCHA, need to fix this! 
	echo("Writing private triples to oauth");
	error_log("Writing private triples to oauth");
	$uri = 'http://private-dev.qdos.com/oauth/mmt.me.uk/blog/data/foaf.rdf';
	$data_dir = '/usr/local/data/public';

    	$privateFoafData = FoafData::getFromSession(true);
	 //TODO MISCHA get private URI
        if (!$privateFoafData->isPublic) {
		error_log("THIS SHOULDNT HAPPEN! public data written as private");
		exit(0);
	}

	$this->view->model = $privateFoafData->getModel();
	$this->view->model->setBaseUri(NULL);
	$result = $this->view->model->find(NULL, NULL, NULL);

	$result = $this->removeLanguageTags($result);

	$data = $result->writeRdfToString();

	file_put_contents($this->cache_filename($uri),$data);	
	error_log('FILE WRITTEN YAY!');
	error_log('FILE WRITTEN YAY!');
	error_log('FILE WRITTEN YAY!');
    }

    //Create the filename used for the hashing of rdf
    function cache_filename($uri) {
        $hash = md5($uri);
        preg_match('/(..)(..)(.*)/', $hash, $matches);
        return '/'.$matches[1].'/'.$matches[2].'/'.$matches[3];
    } //end cache filename
    
    //Create the cache file directory structure needed
    function create_cache($filename,$datadir) {
            if (preg_match('/\/(..)\/(..)\/(.*)/',$filename,$matches)) {
                    if (!(file_exists("$datadir/$matches[1]"))) {
                            mkdir("$datadir/$matches[1]");
                    }
                    if (!(file_exists("$datadir/$matches[1]/$matches[2]"))) {
                            mkdir("$datadir/$matches[1]/$matches[2]");
                    }
                    return true;
            } else {
                    //Incorrect cache filestructure passed
                    return false;
            }
    }

    public function writeFoafNodownloadAction(){
	//this is inside an action in one of your controllers:
    	$publicFoafData = FoafData::getFromSession(true);
    	$tempmodel = unserialize(serialize($publicFoafData->getModel()));

    	$tempuri = $publicFoafData->getURI();
    	$tempprimaryTopic = $publicFoafData->getPrimaryTopic();
        $newDocUriRes = new Resource($tempuri);
        $newPersonUriRes = new Resource($tempuri."#me");
        $oldPersonUriRes = new Resource($tempprimaryTopic);
        $oldDocUriRes = new Resource($tempuri);
        
        $tempmodel->replace($oldDocUriRes,new Resource("<http://xmlns.com/foaf/0.1/primaryTopic>"),NULL,$newDocUriRes);
        $tempmodel->replace($oldPersonUriRes,NULL,NULL,$newPersonUriRes);
        $tempmodel->replace(NULL,NULL,$oldPersonUriRes,$newPersonUriRes);
	$result = $tempmodel->find(NULL, NULL, NULL);

	$data = $result->writeRdfToString();

	if (strlen($data) > 0 ) {
		//TODO MISCHA
		$data_dir = '/usr/local/data/public';
		$cachefilename = $this->cache_filename($tempuri);
		error_log($cachefilename);

		$this->create_cache($cachefilename,$data_dir);
		file_put_contents($data_dir.$cachefilename, $data);
		error_log('[foafeditor] We have created a new file at the following url:'.$tempuri);
	} else {
		error_log('[foafeditor] rdf stream empty nothing to write to:'.$tempuri);
	}
    }
    
    private function doWrite($foafData,$newDocUri,$writeNtriples){
	    if (!$foafData) {
		return;
	    }
	    $tempmodel = unserialize(serialize($foafData->getModel()));

	    //TODO MISCHA get private URI
	    if (!$foafData->isPublic) {
		$tempuri = "http://private-dev.qdos.com/oauth/mmt.me.uk/blog/data/foaf.rdf";
	    } else {
            	$tempuri = $foafData->getURI();
	    }
            $tempgraph= $foafData->getGraphset();
            $tempprimaryTopic = $foafData->getPrimaryTopic();
	    
            $newDocUriRes = new Resource($tempuri);
            $newPersonUriRes = new Resource($tempuri."#me");
            $oldPersonUriRes = new Resource($tempprimaryTopic);
            $oldDocUriRes = new Resource($tempuri);
	    
            $tempmodel->replace($oldDocUriRes,new Resource("<http://xmlns.com/foaf/0.1/primaryTopic>"),NULL,$newDocUriRes);
            $tempmodel->replace($oldPersonUriRes,NULL,NULL,$newPersonUriRes);
            $tempmodel->replace(NULL,NULL,$oldPersonUriRes,$newPersonUriRes);
            $tempmodel->setBaseUri(NULL);
            
            $result = $tempmodel->find(NULL, NULL, NULL);
	
            if($foafData->isPublic){
            	if($writeNtriples){
            		$this->view->data->public = $result->writeRdfToString('nt');
            	} else {
            		$this->view->data->public = $result->writeRdfToString();
            	}
            } else {
            	if($writeNtriples){
            		$this->view->data->private = $result->writeRdfToString('nt');
            	} else {
            		$this->view->data->private = $result->writeRdfToString();
            	}	
            }    	
    }

}
