<?php

//require_once 'helpers/sparql.php';

require_once 'RdfAPI.php';
require_once 'dataset/DatasetMem.php';

class FoafData {
    private $uri;
    private $model;
    private $graphset;
    private $primaryTopic;
    
    /*New from uri if uri present. if not just new.*/
    public function FoafData($uri = "") {
        if($uri){
			//$model = new MemModel();
	        //$model->load($uri);
	        //TODO This name shouldnt be hardcoded.
	        $graphset = ModelFactory::getDatasetMem('Dataset1');
	        $model = new NamedGraphMem($uri);
	        $model->load($uri);
	        $graphset->addNamedGraph($model);
	        if (!($graphset->containsNamedGraph($uri))) {
	        	print "Triples model not add to the modelfactory\n";
	        }
	        if($model!=null) { 
	            $this->model = $model;
	            $this->uri = $uri;
	            $this->graphset = $graphset;
	        }
    	
    		$this->putInSession();
        } else {
        	//FIXME: sort this out so it isn't an echo
        	echo("Something went wrong, there's no URI!");
        }
    }
    
    public static function getFromSession(){
    	//TODO: use auth session for particular users
    	//if(Zend_Session::sessionExists()){
    		$defaultNamespace = new Zend_Session_Namespace('Default');
    	//}
    	//XXX This probably ought to be changed for production
    	$defaultNamespace->setExpirationSeconds(10000);
    	return $defaultNamespace->foafData;
    }
    
    public function putInSession(){
    	//TODO: use auth session for particular users
    	//if(Zend_Session::sessionExists()){
    		$defaultNamespace = new Zend_Session_Namespace();
  
    	//XXX This probably ought to be changed for production
    	$defaultNamespace->setExpirationSeconds(10000);
    	$defaultNamespace->foafData = $this;  
    }

    public function killSession(){
        Zend_Session::destroy();
    }

    
    public function getModel() {
        return $this->model;	
    }
    
    public function getGraphset() {
        return $this->graphset;	
    }

    public function getUri() {
        return $this->uri;
    }
    
 	public function getPrimaryTopic() {
        return $this->primaryTopic;
    }
    
 	public function setPrimaryTopic($primaryTopic) {
        $this->primaryTopic = $primaryTopic;
    }
    
    public function setModel($model) {
        $this->model = $model;	
    }
    
    public function setUri($uri) {
    	$this->uri = $uri;
    }
    
    public function setGraphset($graphset) {
    	$this->graphset= $graphset;
    }
}
/* vi:set expandtab sts=4 sw=4: */
