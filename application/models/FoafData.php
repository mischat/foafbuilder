<?php
//require_once 'helpers/sparql.php';
require_once 'RdfAPI.php';
require_once 'dataset/DatasetMem.php';
require_once 'sparql/SparqlParser.php';

class FoafData {
    private $uri;
    private $model;
    private $graphset;
    private $primaryTopic;
    private $randomStringToBnodeArray = array();
    public $isPublic;
    
    /*stuff to say whether we've imported from the various reflectors or not*/
    var $flickrFound = false;
	var $deliciousFound = false;
	var $lastfmFound = false;
    
    /*New from uri if uri present. if not just new.*/
    public function FoafData ($uri = "",$isPublic = true) {
        
    	//either a private foafData object or a public one
        $this->isPublic = $isPublic;
        
    	/*
    	 * TODO MISCHA this empty instantiation stuff is for new private models.  In future it should try to fetch	
    	 * the private model from an oauth server or similar.
    	 */
        if(!$this->isPublic){
        	//create a skeleton empty document
        	$this->getEmptyDocument($uri);
			$this->putInSession(true);
			return;
    	}
    	
    	/*
    	 * LUKE if the uri does not exist then create an empty skeleton
    	 * TODO: change this uri to fit the oauth server
    	 */
    	if($uri=='' || !$uri){
    		//create a skeleton empty document
    		$uri = sha1(microtime()*microtime());
    		$uri = '';
    		$this->getEmptyDocument($uri);
			$this->putInSession();
			return;
    	}
    	
    	if (!$uri) {
        	error_log("Foaf data called with no uri\n");
        	return;
    	}

		$graphset = ModelFactory::getDatasetMem('GarlikDataset');
		$model = new NamedGraphMem($uri);
		$model->load($uri);
		
		$graphset->addNamedGraph($model);     
		if (!($graphset->containsNamedGraph($uri))) {
		    print "Triples model not added to the modelfactory\n";
		}
	        
        $this->replacePrimaryTopic($uri,$model);
		
		if ($model!=null) { 
			$this->model = $model;
		 	$this->uri = $uri;
		    $this->graphset = $graphset;
		}
		
	   	$this->putInSession();
    }
    
    //replace the existing primary topic (olduri) with the new one (newUri or a sha1 of the old one) in the model
    public function replacePrimaryTopic($uri, $model, $newUri = false){
    	
    	
     	/*get primary topic*/
        $query = "SELECT ?prim WHERE {<$uri> <http://xmlns.com/foaf/0.1/primaryTopic> ?prim}";
        $result = $model->sparqlQuery($query);
		
        //TODO MISCHA ... Need to have some return here to say that the Sub of  PrimaryTopic is just not good enough !
        //TODO must make sure that we handle having a non "#me" foaf:Person URI
         if (isset($result[0]['?prim'])) {
            $oldUri = $result[0]['?prim']->uri;
            error_log("[foaf_editor] PrimaryTopic: $oldUri");
         } else {
            //TODO MISCHA should do some error reporting here
            error_log("[foaf_editor] Error no primaryTopic");
            return null;
        }      
        
        //if no new uri has been passed in then just set it as the existing primary topic or, if that isn't set then the hash of the uri
    	if(!$newUri){
    		$primaryTopic = $this->getPrimaryTopic();
    			
    		if(!$primaryTopic){
    			$newUri = "http://".md5($oldUri); 
    		}
    	}
       
    	/*replace the old resource with some new ones*/
        $oldUriRes = new Resource($oldUri);
        $newUriRes = new Resource($newUri);
        $model->replace($oldUriRes,NULL,NULL,$newUriRes);
        $model->replace(NULL,NULL,$oldUriRes,$newUriRes);
        $this->primaryTopic = $newUri;
        
        if (!preg_match("/#me$/",$oldUri,$patterns)) {
        	$model->add(new Statement($newUriRes,new Resource("http://www.w3.org/2002/07/owl#sameAs"),$oldUriRes));
        }
        
        return $model;
    }
    
    public static function getFromSession($isPublic = true) {
    	//TODO: use auth session for particular users
        $defaultNamespace = new Zend_Session_Namespace('Garlik');
    	//XXX This probably ought to be changed for production
    	
        $ret;
        
        if($isPublic){
        	$ret = $defaultNamespace->foafData;
        } else {
        	$ret = $defaultNamespace->privateFoafData;
        }
        
    	return $ret;
    }
    
    public function putInSession() {
    	//TODO: use auth session for particular users
    	$defaultNamespace = new Zend_Session_Namespace('Garlik');
    	//XXX This probably ought to be changed for production
    	//$defaultNamespace->setExpirationSeconds(9999999999);
    	if($this->isPublic){	
    		$defaultNamespace->foafData = $this; 
    	} else{
    		$defaultNamespace->privateFoafData = $this; 
    	}
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
    
    public function getRandomStringToBnodeArray() {
        return $this->randomStringToBnodeArray;
    }
    
    public function setRandomStringToBnodeArray($randomStringToBnodeArray) {
        $this->randomStringToBnodeArray = $randomStringToBnodeArray;
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
    public function getEmptyDocument($uri){
    	
       	$graphset = ModelFactory::getDatasetMem('GarlikDataset');
		$model = new NamedGraphMem($uri);
		$graphset->addNamedGraph($model);
		$this->model = $model;
		
		$primaryResource = new Resource("http://".md5($uri."#me"));
		$personalProfileDocumentTriple = new Statement(new Resource($uri), new Resource("http://www.w3.org/1999/02/22-rdf-syntax-ns#type"),new Resource("http://xmlns.com/foaf/0.1/PersonalProfileDocument"));
		$primaryTopicTriple = new Statement(new Resource($uri), new Resource("http://xmlns.com/foaf/0.1/primaryTopic"),$primaryResource);
		
		$this->model->add($personalProfileDocumentTriple);
		$this->model->add($primaryTopicTriple);
		
		$this->primaryTopic = $primaryResource->uri;
		$this->graphset = $graphset;
		$this->uri = $uri;
			
    	return;
    }
}
/* vi:set expandtab sts=4 sw=4: */
