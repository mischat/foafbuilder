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
        if($uri) {
	        //TODO This name shouldnt be hardcoded.
	        $graphset = ModelFactory::getDatasetMem('Dataset1');
	        $model = new NamedGraphMem($uri);
	        $model->load($uri);
	        $graphset->addNamedGraph($model);
	        if (!($graphset->containsNamedGraph($uri))) {
	        	print "Triples model not add to the modelfactory\n";
	        }
                $result = $model->find(new Resource($uri),new Resource('http://xmlns.com/foaf/0.1/primaryTopic'),NULL);
                $oldUri = "";
                $it = $result->getStatementIterator();
                while ($it->hasNext()) {
                    $statement = $it->next();
                    if ($it->getCurrentPosition() == 0) {
                        $oldUri = (string) $statement->getLabelObject();
                    }
                } 
/*
            $query = "SELECT ?prim WHERE {<$uri> <http://xmlns.com/foaf/0.1/primaryTopic> ?prim}";
            $result = $model->sparqlQuery($query);

            //TODO must make sure that we handle having a non "#me" foaf:Person URI
            $oldUri = $result[0]['?prim']->uri;
*/
            if (!$oldUri) {
                echo ("No primarytopic set in foaf file!");
            }


            $oldUriRes = new Resource($oldUri);
            $newUri = "http://".md5($oldUri);
            $newUriRes = new Resource($newUri);
            $model->replace($oldUriRes,NULL,NULL,$newUriRes);
            $model->replace(NULL,NULL,$oldUriRes,$newUriRes);
            if (!preg_match("/#me$/",$oldUri,$patterns)) {
                $model->add(new Statement($newUriRes,new Resource("http://www.w3.org/2002/07/owl#sameAs"),$oldUriRes));
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
        $defaultNamespace = new Zend_Session_Namespace('Garlik');
    	//}
    	//XXX This probably ought to be changed for production
    	$defaultNamespace->setExpirationSeconds(10000);
    	return $defaultNamespace->foafData;
    }
    
    public function putInSession(){
    	//TODO: use auth session for particular users
    	//if(Zend_Session::sessionExists()){
    		$defaultNamespace = new Zend_Session_Namespace('Garlik');
  
    	//XXX This probably ought to be changed for production
    	$defaultNamespace->setExpirationSeconds(10000);
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
