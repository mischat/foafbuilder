<?php

require_once 'helpers/sparql.php';
require_once 'RDFAPI.php';
    	
class FoafData
{
	private $uri;
	private $model;
    
	/*New from uri if uri present. if not just new.*/
    public function FoafData($uri = ""){
    	if($uri){	
	    	$model = new MemModel();
			$model->load($uri);
			if($model!=null){ 
				$this->model = $model;
				$this->uri = $uri;
				$this->putInSession();
			}
    	} else {
    		$defaultNamespace = new Zend_Session_Namespace('Default');
			$defaultNamespace->foafData = $this;
    	}
    }
    public function putInSession(){
    	$defaultNamespace = new Zend_Session_Namespace('Default');
    	$defaultNamespace->foafData = $this;	
    }
    
    public function getModel(){
    	return $this->model;	
    }
    
    public function getUri(){
    	return $this->uri;
    }
    
	public function setModel($model){
    	$this->model = $model;	
    }
    
    public function setUri($uri){
    	$this->uri = $uri;
    }
    
}