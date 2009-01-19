<?php
//require_once 'helpers/sparql.php';
require_once 'RdfAPI.php';
require_once 'dataset/DatasetMem.php';
require_once 'sparql/SparqlParser.php';
require_once 'helpers/settings.php';
require_once 'helpers/write-utils.php';
require_once 'helpers/errorhandler.php';

class FoafData {
    private $bNodeNumber = 0;
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
		error_log("[builder] The user has AUTHENTICATED! and the openid is ".$this->openid);
		$this->openid = $defaultNamespace->url;
	} else {
		error_log("[builder] The user has NOT AUTHENTICATED! ");
		$this->openid = EXAMPLE_OPENID;
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
	
        if (!$this->isPublic) {
		//If match then one of ours ...
		if (preg_match('/^http:\/\/[a-zA-Z0-0\-\_]*\.qdos\.com\/oauth/',$this->uri) && $this->uri != PRIVATE_URL.EXAMPLE_OPENID.'/data/foaf.rdf') {
			$cachename = cache_filename($this->uri);
			if (file_exists(PRIVATE_DATA_DIR.$cachename)) {
				$uri = 'file://'.PRIVATE_DATA_DIR.$cachename;
				error_log('[builder] found local url to load'.$uri);	
			} 
		}
		//TODO MISCHA
		//In future make OAuth dance here ...
    	} else if ($this->isPublic) {
		//If match then one of ours ...
                if (preg_match('/^http:\/\/[a-zA-Z0-0\-\_]*\.qdos\.com\/people/',$this->uri) && $this->uri != PUBLIC_URL.EXAMPLE_OPENID.'/foaf.rdf') {
			$cachename = cache_filename($this->uri);
			if (file_exists(PUBLIC_DATA_DIR.$cachename)) {
				$uri = 'file://'.PUBLIC_DATA_DIR.$cachename;
				error_log('[builder] found local url to load'.$uri);	
			} 
		}
    	} 
    	
    	/*
    	 * LUKE if the uri does not exist then create an empty skeleton
    	 */
    	if($uri=='' || !$uri){
    		//create a skeleton empty document
    		$this->getEmptyDocument();
		$this->putInSession();
		return;
    	}
		
        /*create a model if there isn't one already*/
	if (!$this->model){
    		$this->getEmptyDocument();
    		$this->model = new NamedGraphMem($this->uri);

	    	/*load the rdf from the passed in uri into the model*/
		$loadValue = $this->model->load($uri);		
		//$loadValue = $this->model-addRDFtoModel($this-uri);		
		if ($loadValue==1) {
			return;		
		}
		//TODO MISCHA catch fail ... from load
	}
	/*make sure that the uri and primary topic of the document is consistent*/

        $this->replacePrimaryTopic($this->uri);	
	$this->putInSession();
    }

    public function addLJRDFtoModel($uri,$replacementUri){

	$this->addRDFtoModel($uri,$replacementUri,true);

	return 0;
    }

   public function addRDFtoModel($uri,$replacementUri,$isLJ = false) {

	$olderrorhandler = set_error_handler("myErrorHandler");
	$tempmodel = new NamedGraphMem($uri);
	$tempmodel->load($uri);
	FoafData::replacePrimaryTopicInModel($tempmodel,$replacementUri,$uri,$this->getPrimaryTopic(),false,$isLJ);

	//Add in the generatorAgent triples
	try {
		foreach($tempmodel->triples as $triple){
			$triple = FoafData::cleanTriple($triple);
			if ($triple) {
				$this->model->addWithoutDuplicates($triple);
			}
		}
		$this->model->addWithoutDuplicates(new Statement (new Resource($replacementUri),new Resource ("http://webns.net/mvcb/generatorAgent"), new Resource (BUILDER_URL)));
		$this->model->addWithoutDuplicates(new Statement (new Resource($replacementUri),new Resource ("http://webns.net/mvcb/errorReportsTo"), new Resource (SUPPORT_EMAIL)));

	} catch (exception $e) {
		exit;
	}

	set_error_handler($olderrorhandler);
	return 0;
    }

    static function cleanTriple($triple) {
	if ((property_exists($triple->obj,'label') && $triple->obj->label == '')) {
		$triple = false;
	}

	if ($triple->pred->uri == "http://webns.net/mvcb/generatorAgent") {
		$temptriple = new Statement(new Resource ($triple->subj->uri),new Resource ($triple->pred->uri),new Resource(BUILDER_URL));
		$triple = $temptriple;
	}
	if ($triple->pred->uri == "http://webns.net/mvcb/errorReportsTo") {
		$temptriple = new Statement(new Resource ($triple->subj->uri),new Resource ($triple->pred->uri),new Resource(SUPPORT_EMAIL));
		$triple = $temptriple;
	}
	return $triple;
   }
    
    private function addRdfsSeeAlso() {
	//TODO in the future I need to query the Oauth_servers database to check what their private URL is
	if ($this->isPublic && $this->openid != EXAMPLE_OPENID) {
		$this->model->addWithoutDuplicates(new Statement (new Resource ($this->getUri()),new Resource('http://www.w3.org/2002/07/owl#sameAs'), new Resource(PRIVATE_URL.$this->openid.'/data/foaf.rdf#me')));
		$this->model->addWithoutDuplicates(new Statement (new Resource ($this->getUri()),new Resource('http://www.w3.org/2000/01/rdf-schema#seeAlso'), new Resource(PRIVATE_URL.$this->openid.'/data/foaf.rdf')));
	}

    }

    public static function replacePrimaryTopicInModel(&$model,$replacementUri,$uri,$prim,$foafData = false,$isLJ){
	/*get primary topics*/
	//get the primary topic in a different way for livejournal
	if(!$isLJ){
		$query = "SELECT ?prim WHERE {?anything <http://xmlns.com/foaf/0.1/primaryTopic> ?prim}";
	} else {
		$query = "SELECT DISTINCT ?prim WHERE {?prim <http://xmlns.com/foaf/0.1/knows> ?other}";
	}

	$results = $model->sparqlQuery($query);
		
	if (!$results || empty($results)) {
		error_log('[FoafData] no primary topic found livejournal = '.$isLJ);
		return null;
	}
        //TODO MISCHA ... Need to have some return here to say that the Sub of  PrimaryTopic is just not good enough !
        foreach ($results as $row) {
		
        	if(!isset($row['?prim'])){
        		error_log('[foaf_editor] primary topic not set is livejournal ='.$isLJ);
        		continue;
        	}
            	$oldPrimaryTopic = $row['?prim']->uri;
		$fragment = "#me";
		if (preg_match('/#me$/',$prim)) {
			if (preg_match('/#(.*?)$/',$oldPrimaryTopic,$fragmatches)) {
				$fragment = "#".$fragmatches[1];
			}
		} else {
			if (preg_match('/#(.*?)$/',$prim,$fragmatches)) {
				$fragment = "#".$fragmatches[1];
			}
		}
        	//TODO must make sure that we handle having a non "#me" foaf:Person URI
	    	$newPrimaryTopic = $replacementUri.$fragment;
		//echo($foafData->isPublic()."  ".$replacementUri." dsdsds");
		
	    	/*replace the old primary topics with the new one*/
		if (substr($oldPrimaryTopic, 0, 5) == 'bNode') {
			$oldPrimaryTopicRes = new BlankNode($oldPrimaryTopic);
		} else {
			$oldPrimaryTopicRes = new Resource($oldPrimaryTopic);
		}

	        $newPrimaryTopicRes = new Resource($newPrimaryTopic);
		$foafDataRes = new Resource($uri."#me");
		
	        $model->replace(new Resource($uri),NULL,NULL,new Resource($replacementUri));
	        $model->replace(NULL,NULL,new Resource($uri),new Resource($replacementUri));
	        $model->replace(NULL,NULL,$oldPrimaryTopicRes,$newPrimaryTopicRes);
	        $model->replace($oldPrimaryTopicRes,NULL,NULL,$newPrimaryTopicRes);
	        $model->replace(NULL,NULL,$foafDataRes,$newPrimaryTopicRes);
	        $model->replace($foafDataRes,NULL,NULL,$newPrimaryTopicRes);

	        /*just to make sure we have the right primary topic down*/
		if($foafData){
	        	$foafData->setPrimaryTopic($newPrimaryTopic);
	        }

	        //XXX speak to mischa about this one
	        if ($oldPrimaryTopic != $newPrimaryTopic && $oldPrimaryTopic != PUBLIC_URL.EXAMPLE_OPENID.'/foaf.rdf#me' && $oldPrimaryTopic != PRIVATE_URL.EXAMPLE_OPENID.'/data/foaf.rdf#me') {
	        	$model->addWithoutDuplicates(new Statement($newPrimaryTopicRes, new Resource("http://www.w3.org/2002/07/owl#sameAs"),$oldPrimaryTopicRes));
	        } 

        } 
        
        /*make sure that the document has only one uri*/
        
	//find the triples containing document uris
	$predicate = new Resource('http://www.w3.org/1999/02/22-rdf-syntax-ns#type');
	$object = new Resource('http://xmlns.com/foaf/0.1/PersonalProfileDocument');  
	$foundDocTriples = $model->find(NULL,$predicate,$object); 
	$replacementUriRes = new Resource($uri);
	    
	//and replace them
	if($foundDocTriples && property_exists($foundDocTriples,'triples') && !empty($foundDocTriples->triples)){
	    	foreach($foundDocTriples->triples as $triple){
			$model->remove($triple);
	       	}
	}
	$model->addWithoutDuplicates(new Statement($replacementUriRes,$predicate,$object));


    }

    //replace the existing primary topic with either newPrimaryTopic or a hash of the uri
    //TODO: get rid of $uri in here
    public function replacePrimaryTopic($uri,$isLJ=false){
	
	FoafData::replacePrimaryTopicInModel($this->model, $this->uri, $this->uri,$this->getPrimaryTopic(),$this,$isLJ);

    }

    //This should be called to update the model after login
    public function updateURI($uri) {
	$first = $this->getPrimaryTopic();
	$page = $this->getUri();

	$ending = "#me";
	if (preg_match('/#(.*?)$/',$first,$matches)) {
		if ($matches[1] != "#me") {
			$ending = '#'.$matches[1];
		}
	}

	$this->setUri($uri);
	$this->setPrimaryTopic($uri.$ending);
	
	$newDocUriRes = new Resource($uri);
	$newPersonUriRes = new Resource($uri.$ending);
	$oldPersonUriRes = new Resource($first);
	$oldDocUriRes = new Resource($page);

	$this->getModel()->replace($oldDocUriRes,NULL,NULL,$newDocUriRes);
	$this->getModel()->replace($oldPersonUriRes,NULL,NULL,$newPersonUriRes);
	$this->getModel()->replace(NULL,NULL,$oldPersonUriRes,$newPersonUriRes);
    }

	//replaces bNodes (to be called in between loading models into this) to make sure they're unique.
    public function mangleBnodes(){
	
	if(!$this->model || !property_exists($this->model,'triples') || !$this->model->triples || empty($this->model->triples)){
		return;
	}
	
	foreach($this->model->triples as $triple){
		
		if($triple->subj instanceof BlankNode){
			$triple->subj->uri = 'bNode_'.uniqid();
		}
		if($triple->pred instanceof BlankNode){
			$triple->pred->uri = 'bNode_'.uniqid();
		}
		if($triple->obj instanceof BlankNode){
			$triple->obk->uri = 'bNode_'.uniqid();
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
    	if($this->isPublic){	
    		$defaultNamespace->foafData = $this; 
    	} else{
    		$defaultNamespace->privateFoafData = $this; 
    	}
    }

    public function getModel() {
        return $this->model;	
    }
   
/* 
    public function getGraphset() {
        return $this->graphset;	
    }
*/
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
 /*   
    public function setGraphset($graphset) {
    	$this->graphset= $graphset;
    }

*/
    public function getEmptyDocument(){
	//$graphset = ModelFactory::getDatasetMem('GarlikDataset');
	$model = new NamedGraphMem($this->uri);
	$this->model = $model;
	
	$primaryResource = new Resource($this->uri."#me");
	$personalProfileDocumentTriple = new Statement(new Resource($this->uri), new Resource("http://www.w3.org/1999/02/22-rdf-syntax-ns#type"),new Resource("http://xmlns.com/foaf/0.1/PersonalProfileDocument"));
	$primaryTopicTriple = new Statement(new Resource($this->uri), new Resource("http://xmlns.com/foaf/0.1/primaryTopic"),$primaryResource);
	
	$this->model->addWithoutDuplicates($personalProfileDocumentTriple);
	$this->model->addWithoutDuplicates($primaryTopicTriple);
	
	$this->primaryTopic = $primaryResource->uri;
	//$this->graphset = $graphset;
        $this->addRdfsSeeAlso();
			
    	return;
    }

}
