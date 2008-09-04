<?php

require_once 'helpers/sparql.php';
require_once 'RdfAPI.php';
require_once 'dataset/NamedGraphMem.php';
    	
class FoafData {
    private $uri;
    private $model;
    
    /*New from uri if uri present. if not just new.*/
    public function FoafData($uri = "") {
        if($uri) {	
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
                //TODO Couldnt these be replaces by the below functions
                $this->model = $model;
                $this->uri = $uri;
                $this->graphset = $graphset;
    	    }
        } else {
            $defaultNamespace = new Zend_Session_Namespace('Default');
            $defaultNamespace->foafData = $this;
        }
    }

    public function putInSession() {
        $defaultNamespace = new Zend_Session_Namespace('Default');
        $defaultNamespace->foafData = $this;	
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
