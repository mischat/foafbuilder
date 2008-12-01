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
	echo("Writing private triples to oauth");
    }

    public function writeFoafNodownloadAction(){
	echo("Saving public triples to our server");
    }
    
    private function doWrite($foafData,$newDocUri,$writeNtriples){
  
	    if (!$foafData) {
		return;
	    }

	    //TODO MISCHA ... use the __clone() Magic Method instead, this is not optimum
	    $tempmodel = unserialize(serialize($foafData->getModel()));
            $tempuri = $foafData->getURI();
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
