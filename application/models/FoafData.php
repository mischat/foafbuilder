<?php
//require_once 'helpers/sparql.php';
require_once 'RdfAPI.php';
require_once 'dataset/DatasetMem.php';
require_once 'sparql/SparqlParser.php';
require_once 'helpers/settings.php';
require_once 'helpers/write-utils.php';

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
    var $ljFound = false;
    
    /*New from uri if uri present. if not just new.*/
    public function FoafData ($uri = "",$isPublic = true) {
        
    	//either a private foafData object or a public one

	//Check if authenticated
	$defaultNamespace = new Zend_Session_Namespace('Garlik');

	//Check if authenicated
	if($defaultNamespace->authenticated == true) {
		error_log("AUTHENICATED! ");
		$this->openid = $defaultNamespace->url;
	} else {
		error_log("NOT AUTHENICATED! ");
		$this->openid = "example.com/myopenid";
	}

        $this->isPublic = $isPublic;
        
    	//only set the uri if it isn't already set
	//TODO MISCHA Need to get OpenID from the SESSION HERE
	if ($this->isPublic) {
		//TODO MISCHA
		$this->uri = PUBLIC_URL.$this->openid.'/foaf.rdf';
		//$this->uri = 'http://mischa-foafeditor.qdos.com/people/'.$this->openid.'/foaf.rdf';
	} else {
		//$this->uri = 'http://private-dev.qdos.com/oauth/'.$this->openid.'/data/foaf.rdf';
		$this->uri = PRIVATE_URL.$this->openid.'/data/foaf.rdf';
	}
	error_log('URI is '.$this->uri);
	
        if (!$this->isPublic) {
		//If match then one of ours ...
		if (preg_match('/^http:\/\/[a-zA-Z0-0\-\_]*\.qdos\.com\/oauth/',$this->uri) && $this->uri != PRIVATE_URL.'example/myopenid/data/foaf.rdf') {
			$cachename = cache_filename($this->uri);
			error_log($cachename);
			if (file_exists(PRIVATE_DATA_DIR.$cachename)) {
				$uri = 'file://'.PRIVATE_DATA_DIR.$cachename;
				error_log($uri);	
			} else {
				$uri = "";
			} 
		}
		//TODO MISCHA
		//In future make OAuth dance here ...
    	} 
    	
    	/*
    	 * LUKE if the uri does not exist then create an empty skeleton
    	 * TODO: change this uri to fit the oauth server
    	 */
    	if($uri=='' || !$uri){
    		//create a skeleton empty document
		error_log("Creating an empty document");
    		$this->getEmptyDocument();
		$this->putInSession();
		return;
    	}
		
        /*create a model if there isn't one already*/
	if (!$this->model){
    		$this->model = new NamedGraphMem($this->uri);

	    	/*load the rdf from the passed in uri into the model*/
		$loadValue = $this->model->load($uri);		
		if ($loadValue==1) {
			return;		
		}
	}
		
	/*make sure that the uri and primary topic of the document is consistent*/
        $this->replacePrimaryTopic($this->uri);	

	$this->putInSession();
    }
    
    //replace the existing primary topic with either newPrimaryTopic or a hash of the uri
    public function replacePrimaryTopic($uri){
    	//TODO: probably need to do some de duping at some point
    	
     	/*get primary topics*/
        $query = "SELECT ?prim WHERE {?anything <http://xmlns.com/foaf/0.1/primaryTopic> ?prim}";
        $results = $this->model->sparqlQuery($query);
        
        if (!$results || empty($results)) {
        	//TODO MISCHA should do some error reporting here
            error_log("[foaf_editor] Error no primaryTopic");
            return null;
        }
        //TODO MISCHA ... Need to have some return here to say that the Sub of  PrimaryTopic is just not good enough !
        //TODO must make sure that we handle having a non "#me" foaf:Person URI
        foreach ($results as $row) {
        	if(!isset($row['?prim'])){
        		error_log('[foaf_editor] primary topic not set');
        		continue;
        	}
            	$oldPrimaryTopic = $row['?prim']->uri;
             
	        //if no new uri has been passed in then just set it as the existing primary topic or, if that isn't set then the hash of the uri
	    	$newPrimaryTopic = $this->primaryTopic;			
	    	if(!$newPrimaryTopic){
	    		$newPrimaryTopic = $this->uri."#me"; 
	    	}  	
	       
	    	/*replace the old primary topics with the new one*/
		if (substr($oldPrimaryTopic, 0, 5) == 'bNode') {
			$oldPrimaryTopicRes = new BlankNode($oldPrimaryTopic);
		} else {
			$oldPrimaryTopicRes = new Resource($oldPrimaryTopic);
		}
	        $newPrimaryTopicRes = new Resource($newPrimaryTopic);
	        $this->model->replace($oldPrimaryTopicRes,NULL,NULL,$newPrimaryTopicRes);
	        $this->model->replace(NULL,NULL,$oldPrimaryTopicRes,$newPrimaryTopicRes);
	        
	        /*just to make sure we have the right primary topic down*/
	        $this->primaryTopic = $newPrimaryTopic;
	        
	        //XXX speak to mischa about this one
	        //if (!preg_match("/#me$/",$oldPrimaryTopic,$patterns)) {
	        if ($oldPrimaryTopic != $newPrimaryTopic) {
	        	$this->model->add(new Statement($newPrimaryTopicRes,new Resource("http://www.w3.org/2002/07/owl#sameAs"),$oldPrimaryTopicRes));
	        }
        } 
        
        /*make sure that the document has only one uri*/
        
	//find the triples containing document uris
	$predicate = new Resource('http://www.w3.org/1999/02/22-rdf-syntax-ns#type');
	$object = new Resource('http://xmlns.com/foaf/0.1/PersonalProfileDocument');  
	$foundDocTriples = $this->model->find(NULL,$predicate,$object); 
	$replacementUriRes = new Resource($this->getUri());
	    
	//and replace them
	if($foundDocTriples && property_exists($foundDocTriples,'triples') && !empty($foundDocTriples->triples)){
	    	foreach($foundDocTriples->triples as $triple){
	    		$this->model->replace($triple->subj,NULL,NULL,$replacementUriRes);
	    		$this->model->replace(NULL,NULL,$triple->subj,$replacementUriRes);
	       	}
	 }
    }
    
    public function replaceKnowsSubject(){
    	//TODO: probably need to do some de duping at some point
    	
     	/*get primary topics*/
        $query = "SELECT DISTINCT ?prim WHERE {?prim <http://xmlns.com/foaf/0.1/knows> ?other}";
        $results = $this->model->sparqlQuery($query);
        
        if (!$results || empty($results)) {
            error_log("[foaf_editor] No foaf:knows, no idea who this document is about");
            return null;
        }
        //TODO must make sure that we handle having a non "#me" foaf:Person URI
        foreach($results as $row){
        
            $oldPrimaryTopic = $row['?prim']->uri;
             
	        //if no new uri has been passed in then just set it as the existing primary topic or, if that isn't set then the hash of the uri
	    	$newPrimaryTopic = $this->primaryTopic;			
	    	if(!$newPrimaryTopic){
	    		$newPrimaryTopic = $this->uri."#me"; 
	    	}  	
		error_log("replacing $oldPrimaryTopic with $newPrimaryTopic");
	       
	    	/*replace the old primary topics with the new one*/
		if (substr($oldPrimaryTopic, 0, 5) == 'bNode') {
			$oldPrimaryTopicRes = new BlankNode($oldPrimaryTopic);
		} else {
			$oldPrimaryTopicRes = new Resource($oldPrimaryTopic);
		}
	        $newPrimaryTopicRes = new Resource($newPrimaryTopic);
	        $this->model->replace($oldPrimaryTopicRes,NULL,NULL,$newPrimaryTopicRes);
	        $this->model->replace(NULL,NULL,$oldPrimaryTopicRes,$newPrimaryTopicRes);
	        
	        /*just to make sure we have the right primary topic down*/
	        $this->primaryTopic = $newPrimaryTopic;
	        
	        //XXX speak to mischa about this one
	        if ($oldPrimaryTopic != $newPrimaryTopic) {
			$this->model->add(new Statement($newPrimaryTopicRes,new Resource("http://www.w3.org/2002/07/owl#sameAs"),$oldPrimaryTopicRes));
	        }
        } 
        /*make sure that the document has only one uri*/
        
	    //find the triples containing document uris
	    $predicate = new Resource('http://www.w3.org/1999/02/22-rdf-syntax-ns#type');
	    $object = new Resource('http://xmlns.com/foaf/0.1/PersonalProfileDocument');  
	    $foundDocTriples = $this->model->find(NULL,$predicate,$object); 
	    $replacementUriRes = new Resource($this->getUri());
	    
	    
	 	//and replace them
	    if($foundDocTriples && property_exists($foundDocTriples,'triples') && !empty($foundDocTriples->triples)){
	    
	    	foreach($foundDocTriples->triples as $triple){
	    		$this->model->replace($triple->subj,NULL,NULL,$replacementUriRes);
	    		$this->model->replace(NULL,NULL,$triple->subj,$replacementUriRes);
	       	}
	    }
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
    public function getEmptyDocument(){
    	
       	$graphset = ModelFactory::getDatasetMem('GarlikDataset');
		$model = new NamedGraphMem($this->uri);
		$this->model = $model;
		
		$primaryResource = new Resource($this->uri."#me");
		$personalProfileDocumentTriple = new Statement(new Resource($this->uri), new Resource("http://www.w3.org/1999/02/22-rdf-syntax-ns#type"),new Resource("http://xmlns.com/foaf/0.1/PersonalProfileDocument"));
		$primaryTopicTriple = new Statement(new Resource($this->uri), new Resource("http://xmlns.com/foaf/0.1/primaryTopic"),$primaryResource);
		
		$this->model->add($personalProfileDocumentTriple);
		$this->model->add($primaryTopicTriple);
		
		$this->primaryTopic = $primaryResource->uri;
		$this->graphset = $graphset;
			
    	return;
    }

}
