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
    
    private function doWrite($foafData,$newDocUri,$writeNtriples){
  
    		if(!$foafData){
    			return;
    		}

    		/*put some stuff in the view*/
    		//XXX is this really necessary
    	    $this->view->model = $foafData->getModel();
            $this->view->uri = $foafData->getURI();
            $this->view->graphset= $foafData->getGraphset();
            $this->view->primaryTopic = $foafData->getPrimaryTopic();

            if (!$newDocUri) {
                $newDocUri = $this->view->uri;
            }
			
            /*do some mangling with the primary topic to set it to the passed uri*/
            $newDocUriRes = new Resource($newDocUri);
            $newPersonUriRes = new Resource($newDocUri."#me");
            $oldPersonUriRes = new Resource($this->view->primaryTopic);
            $oldDocUriRes = new Resource($this->view->uri);
            
            $this->view->model->replace($oldDocUriRes,new Resource("<http://xmlns.com/foaf/0.1/primaryTopic>"),NULL,$newDocUriRes);
            $this->view->model->replace($oldPersonUriRes,NULL,NULL,$newPersonUriRes);
            $this->view->model->replace(NULL,NULL,$oldPersonUriRes,$newPersonUriRes);
            $this->view->model->setBaseUri(NULL);
            
            /*get everything and put it in the view*/
            $result = $this->view->model->find(NULL, NULL, NULL);
            
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
