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
			echo('getting empty');
			return;
    	}
    	
    	/*set some parameters*/
    	//XXX - this graphset is never used - is it necessary, and is uri necessary?
		$this->graphset = ModelFactory::getDatasetMem('GarlikDataset');
		$this->model = new NamedGraphMem($uri);
		
		/*load the passed foaf file and generate a new primary topic for it*/
		$loadValue = $this->model->load($uri);		
		if($loadValue==1){
			echo('no passed foaf file');
			return;		
		}
		
		//only set the uri if it isn't already set
		if(!$this->uri){
			$this->uri = $uri;
		}
		
        $this->replacePrimaryTopic($uri);	
        //var_dump($this);
	   	$this->putInSession();
    }
    
    //replace the existing primary topic with either newPrimaryTopic or a hash of the uri
    public function replacePrimaryTopic($uri){
    	
     	/*get primary topic*/
        $query = "SELECT ?prim WHERE {<$uri> <http://xmlns.com/foaf/0.1/primaryTopic> ?prim}";
        $results = $this->model->sparqlQuery($query);
        
        if (!$results || empty($results)) {
        	//TODO MISCHA should do some error reporting here
            error_log("[foaf_editor] Error no primaryTopic");
            echo('no primary topic');
            return null;
        }
        //TODO MISCHA ... Need to have some return here to say that the Sub of  PrimaryTopic is just not good enough !
        //TODO must make sure that we handle having a non "#me" foaf:Person URI
        foreach($results as $row){
        
        	if(!isset($row['?prim'])){
        		echo('Primary Topic Is Not Set');
        		error_log('[foaf_editor] primary topic not set');
        		continue;
        	}
        	
            $oldPrimaryTopic = $row['?prim']->uri;
             
	        //if no new uri has been passed in then just set it as the existing primary topic or, if that isn't set then the hash of the uri
	    	$newPrimaryTopic = $this->primaryTopic;			
	    	if(!$newPrimaryTopic){
	    		$newPrimaryTopic = "http://".md5($oldPrimaryTopic); 
	    	}  	
	       
	    	/*replace the old primary topics with the new one*/
	        $oldPrimaryTopicRes = new Resource($oldPrimaryTopic);
	        $newPrimaryTopicRes = new Resource($newPrimaryTopic);
	        $this->model->replace($oldPrimaryTopicRes,NULL,NULL,$newPrimaryTopicRes);
	        $this->model->replace(NULL,NULL,$oldPrimaryTopicRes,$newPrimaryTopicRes);
	        
	        /*just to make sure we have the right primary topic down*/
	        $this->primaryTopic = $newPrimaryTopic;
	        
	        if (!preg_match("/#me$/",$oldPrimaryTopic,$patterns)) {
	        	 $this->model->add(new Statement($newPrimaryTopicRes,new Resource("http://www.w3.org/2002/07/owl#sameAs"),$oldPrimaryTopicRes));
	        }
        } 
        
        /*make sure that the document has the correct uri*/
        
	    //find the triples containing document uris
	    $predicate = new Resource('http://www.w3.org/1999/02/22-rdf-syntax-ns#type');
	    $object = new Resource('http://xmlns.com/foaf/0.1/PersonalProfileDocument');  
	    $foundDocTriples = $this->model->find(NULL,$predicate,$object); 
	            
	   	//remove them
	    if($foundDocTriples && property_exists($foundDocTriples,'triples') && !empty($foundDocTriples->triples)){
	    	foreach($foundDocTriples->triples as $triple){
	       		$this->model->remove($triple);
	       	}
	    }
	        
	    //and add the correct one, from the uri set in foafData
	    $profileStatement = new Statement(new Resource($this->getUri()),$predicate,$object); 
	    $this->model->addWithoutDuplicates($profileStatement);
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
