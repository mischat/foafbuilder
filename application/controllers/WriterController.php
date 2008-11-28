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

  public function writeFoafn3PublicAction() {
        require_once 'FoafData.php';
	//this is inside an action in one of your controllers:
    	$publicFoafData = FoafData::getFromSession(true);

	$this->view->model = $publicFoafData->getModel();
	$result = $this->view->model->find(NULL, NULL, NULL);
        
	$data = $result->writeRdfToString();

    $response = $this->getResponse();
        $response->setHeader('Cache-Control', 'public', true)
            ->setHeader('Content-Description', 'File Transfer', true)
            ->setHeader('Content-Type', 'application/rdf+xml', true)
            ->setHeader('Content-Disposition', 'attachment;filename=foaf.rdf', true);
        
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true) ;

	echo $data;
/*
        Zend_Layout::getMvcInstance()->disableLayout(true);
        $this->_helper->viewRenderer->setNoRender();
        $this->getResponse()->setHeader("Cache-Control","no-cache, must-revalidate");
        $this->getResponse()->setHeader("Content-Type","application/force-download");
        $this->getResponse()->setHeader("Content-Disposition","attachment; filename='foaf.rdf'");
//    	$this->getResponse()->appendBody("LAME LAME ");
	echo "FUCK FUCK";

/*
// Within an action controller action:
// Set a header
$this->getResponse()
    ->setHeader('Content-Type', 'application/zip')
*/


/*
	$this->view->model = $publicFoafData->getModel();
	$result = $this->view->model->find(NULL, NULL, NULL);
        
	$file = $result->writeRdfToString();
 
	$this->getResponse()->setBody($file);
	$this->getResponse()->setHeader('Content-Type', 'application/zip');
	$this->getResponse()->setHeader('Content-Disposition', 'attachment; filename="foaf.rdf"');
	$this->getResponse()->setHeader('Content-Length', strlen($file));
 
	//If using Zend_Layout, we need to disable it:
	//$this->_helper->layout->disableLayout();
	 
	//Disable ViewRenderer:
	//$this->_helper->viewRenderer->setNoRender(true);
        
    	$publicFoafData = FoafData::getFromSession(true);
	$this->view->model = $publicFoafData->getModel();
	$result = $this->view->model->find(NULL, NULL, NULL);
        
	//$result->writeRdfToString();

        header("Content-type: application/octet-stream");
        header("Content-Disposition: attachment; filename=\"foaf.rdf\"");
	echo $result->writeRdfToString();
	exit();

	header("Content-type: application/octet-stream");
	header("Content-Disposition: attachment; filename=\"my-data.csv\"");
	echo $result->writeRdfToString();
*/
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

	/*
    	    $this->view->model = $foafData->getModel();
            $this->view->uri = $foafData->getURI();
            $this->view->graphset= $foafData->getGraphset();
            $this->view->primaryTopic = $foafData->getPrimaryTopic();

            if (!$newDocUri) {
                $newDocUri = $this->view->uri;
            }
			
            //do some mangling with the primary topic to set it to the passed uri
            $newDocUriRes = new Resource($newDocUri);
            $newPersonUriRes = new Resource($newDocUri."#me");
            $oldPersonUriRes = new Resource($this->view->primaryTopic);
            $oldDocUriRes = new Resource($this->view->uri);
            
            $this->view->model->replace($oldDocUriRes,new Resource("<http://xmlns.com/foaf/0.1/primaryTopic>"),NULL,$newDocUriRes);
            $this->view->model->replace($oldPersonUriRes,NULL,NULL,$newPersonUriRes);
            $this->view->model->replace(NULL,NULL,$oldPersonUriRes,$newPersonUriRes);
            $this->view->model->setBaseUri(NULL);
            
            /*get everything and put it in the view
            $result = $this->view->model->find(NULL, NULL, NULL);

	*/
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
