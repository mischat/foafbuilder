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
		error_log("[builder] The user has AUTHENTICATED! ");
		error_log("the openid is ".$this->openid);
		$this->openid = $defaultNamespace->url;
	} else {
		error_log("[builder] The user has NOT AUTHENTICATED! ");
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
	
        if (!$this->isPublic) {
		//If match then one of ours ...
		if (preg_match('/^http:\/\/[a-zA-Z0-0\-\_]*\.qdos\.com\/oauth/',$this->uri) && $this->uri != PRIVATE_URL.'example.com/myopenid/data/foaf.rdf') {
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
		if (preg_match('/^http:\/\/[a-zA-Z0-0\-\_]*\.qdos\.com\/people/',$this->uri) && $this->uri != PRIVATE_URL.'example.com/myopenid/data/foaf.rdf') {
			$cachename = cache_filename($this->uri);
			if (file_exists(PUBLIC_DATA_DIR.$cachename)) {
				$uri = 'file://'.PUBLIC_DATA_DIR.$cachename;
				error_log('[builder] found local url to load'.$uri);	
			} 
		}
    	} 
    	
    	/*
    	 * LUKE if the uri does not exist then create an empty skeleton
    	 * TODO: change this uri to fit the oauth server
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
        $this->replaceGeneratorAgent();
	$this->putInSession();
    }

    public function addLJRDFtoModel($uri) {
	$tempmodel = new NamedGraphMem($uri);
	$loadValue = $tempmodel->load($uri);
	if ($loadValue==1) {
		return 1;		
	}

     	/*get primary topics*/
        $query = "SELECT DISTINCT ?prim WHERE {?prim <http://xmlns.com/foaf/0.1/knows> ?other}";
        $results = $tempmodel->sparqlQuery($query);
        
        if (!$results || empty($results)) {
            error_log("[foaf_editor] No foaf:knows, no idea who this document is about!");
            return null;
        }
        foreach ($results as $row) {
                $oldPrimaryTopic = $row['?prim']->uri;
	    	/*replace the old primary topics with the new one*/
		if (substr($oldPrimaryTopic, 0, 5) == 'bNode') {
			$oldPrimaryTopicRes = new BlankNode($oldPrimaryTopic);
		} else {
			$oldPrimaryTopicRes = new Resource($oldPrimaryTopic);
		}

	}

	$fragment = "#me";
	//If the current one is #me
	if (preg_match('/#me$/',$this->getPrimaryTopic())) {
		if (preg_match('/#(.*?)$/',$oldPrimaryTopic,$fragmatch)) {
			if ($fragmatch[1] != "me") {
				$fragment = "#".$fragmatch[1];
			}
		}
	}


	//TODO must make sure that we handle having a non "#me" foaf:Person URI
	$newPrimaryTopic = $this->getUri().$fragment;
	$newPrimaryTopicRes = new Resource($newPrimaryTopic);

	$tempmodel->replace(new Resource($uri),NULL,NULL,new Resource($this->getUri()));
	$tempmodel->replace(NULL,NULL,new Resource($uri),new Resource($this->getUri()));
	$tempmodel->replace(NULL,NULL,$oldPrimaryTopicRes,$newPrimaryTopicRes);
	$tempmodel->replace($oldPrimaryTopicRes,NULL,NULL,$newPrimaryTopicRes);
	
	$result = $tempmodel->find(NULL, NULL, NULL);

	foreach($result->triples as $triple){
		$this->model->addWithoutDuplicates($triple);

	}

	$this->replacePrimaryTopic($newPrimaryTopic);

	return 0;
    }

   public function addRDFtoModel($uri) {

	$olderrorhandler = set_error_handler("myErrorHandler");
	$tempmodel = new NamedGraphMem($uri);

	try {
		$loadValue = $tempmodel->load($uri);
		if ($loadValue==1) {
			return 1;		
		}

		$primaryTopic = $tempmodel->find(new Resource($uri),new Resource("http://xmlns.com/foaf/0.1/primaryTopic"),NULL);
		
		$fragment = "#me";
		$string = $uri.$fragment;

		//Get the primaryTopic if none
		foreach ($primaryTopic->triples as $triple) {
			$string = $triple->obj->uri;
		}

		//If the current one is #me
		if (preg_match('/#me$/',$this->getPrimaryTopic())) {
			if (preg_match('/#(.*?)$/',$string,$fragmatch)) {
				if ($fragmatch[1] != "me") {
					$fragment = "#".$fragmatch[1];
				}
			}
		}
		//TODO must make sure that we handle having a non "#me" foaf:Person URI
		$newPrimaryTopic = $this->uri.$fragment;

		$tempmodel->replace(new Resource($uri),NULL,NULL,new Resource($this->getUri()));
		$tempmodel->replace(NULL,NULL,new Resource($uri),new Resource($this->getUri()));
		$tempmodel->replace(NULL,NULL,new Resource($newPrimaryTopic),new Resource($this->getPrimaryTopic()));
		$tempmodel->replace(new Resource($newPrimaryTopic),NULL,NULL,new Resource($this->getPrimaryTopic()));

		$result = $tempmodel->find(NULL, NULL, NULL);

		foreach($result->triples as $triple){
			/*TODO MISCHA 
			if ($triple->pred->uri == "http://xmlns.com/foaf/0.1/nick") {
				$triple->obj->lang = NULL;
			}
			*/
			$this->model->addWithoutDuplicates($triple);
		}
		$this->replacePrimaryTopic($newPrimaryTopic);

	} catch (exception $e) {
		echo "false";
		exit;
	}

	set_error_handler($olderrorhandler);
	return 0;
    }

    //Remove the generator agent and add our own
    public function replaceGeneratorAgent() {
	if (!$this->getUri()) {
		$gen_agent = new Resource('http://webns.net/mvcb/generatorAgent');
		$reports_to = new Resource('http://webns.net/mvcb/errorReportsTo');
		$mailto_admin = new Resource('mailto:admin.qdos.com');
		$builder = new Resource(BUILDER_URL);
		$primary_topic_resource = new Resource($this->getUri());
		
		//find existing triples
		$foundModel = $this->model->find($primary_topic_resource,$gen_agent,NULL);
		
		//remove existing triples
		foreach($foundModel->triples as $triple){
			error_log('[foafeditor] found generator agent triple and removing now');
			$this->model->remove($triple);
		}

		$statement = new Statement($primary_topic_resource,$gen_agent,$builder);
		$this->model->addWithoutDuplicates($statement);

		$foundModel = $this->model->find($primary_topic_resource,$reports_to,NULL);
		//remove existing triples
		foreach($foundModel->triples as $triple){
			$this->model->remove($triple);
		}

		$statement = new Statement($primary_topic_resource,$reports_to,$mailto_admin);
		$this->model->addWithoutDuplicates($statement);
	}
    }	
    
    private function addRdfsSeeAlso() {
	//TODO in the future I need to query the Oauth_servers database to check what their private URL is
	error_log('The user openid is '.$this->openid);
	if ($this->isPublic) {
		$this->model->addWithoutDuplicates(new Statement (new Resource ($this->getUri()),new Resource('http://www.w3.org/2002/07/owl#sameAs'), new Resource(PRIVATE_URL.$this->openid.'/data/foaf.rdf#me')));
		$this->model->addWithoutDuplicates(new Statement (new Resource ($this->getUri()),new Resource('http://www.w3.org/2000/01/rdf-schema#seeAlso'), new Resource(PRIVATE_URL.$this->openid.'/data/foaf.rdf')));
	}

    }


    //replace the existing primary topic with either newPrimaryTopic or a hash of the uri
    public function replacePrimaryTopic($uri){
	/*get primary topics*/
	$query = "SELECT ?prim WHERE {?anything <http://xmlns.com/foaf/0.1/primaryTopic> ?prim}";
	$results = $this->model->sparqlQuery($query);
		
	if (!$results || empty($results)) {
		//TODO MISCHA should do some error reporting here
		return null;
	}
        //TODO MISCHA ... Need to have some return here to say that the Sub of  PrimaryTopic is just not good enough !
        foreach ($results as $row) {
        	if(!isset($row['?prim'])){
        		error_log('[foaf_editor] primary topic not set');
        		continue;
        	}
            	$oldPrimaryTopic = $row['?prim']->uri;
		$fragment = "#me";
		if (preg_match('/#me$/',$this->getPrimaryTopic())) {
			if (preg_match('/#(.*?)$/',$oldPrimaryTopic,$fragmatches)) {
				$fragment = "#".$fragmatches[1];
			}
		} else {
			if (preg_match('/#(.*?)$/',$this->getPrimaryTopic(),$fragmatches)) {
				$fragment = "#".$fragmatches[1];
			}
		}
        	//TODO must make sure that we handle having a non "#me" foaf:Person URI
	    	$newPrimaryTopic = $this->uri.$fragment;
	       
	    	/*replace the old primary topics with the new one*/
		if (substr($oldPrimaryTopic, 0, 5) == 'bNode') {
			$oldPrimaryTopicRes = new BlankNode($oldPrimaryTopic);
		} else {
			$oldPrimaryTopicRes = new Resource($oldPrimaryTopic);
		}
	        $newPrimaryTopicRes = new Resource($newPrimaryTopic);
		$foafDataRes = new Resource($this->uri."#me");
		
	        $this->model->replace($oldPrimaryTopicRes,NULL,NULL,$newPrimaryTopicRes);
	        $this->model->replace(NULL,NULL,$oldPrimaryTopicRes,$newPrimaryTopicRes);
	        $this->model->replace(NULL,NULL,$foafDataRes,$newPrimaryTopicRes);
	        $this->model->replace(NULL,NULL,$foafDataRes,$newPrimaryTopicRes);

	        /*just to make sure we have the right primary topic down*/
	        $this->setPrimaryTopic($newPrimaryTopic);
	        
	        //XXX speak to mischa about this one
	        if ($oldPrimaryTopic != $newPrimaryTopic && $oldPrimaryTopic != PUBLIC_URL.'example.com/myopenid/foaf.rdf#me' && $oldPrimaryTopic != PRIVATE_URL.'example.com/myopenid/data/foaf.rdf#me') {
	        	$this->model->add(new Statement($newPrimaryTopicRes,new Resource("http://www.w3.org/2000/01/rdf-schema#seeAlso"),$oldPrimaryTopicRes));
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
	$lame = $this->getPrimaryTopic();
	
	$newDocUriRes = new Resource($uri);
	$newPersonUriRes = new Resource($uri.$ending);
	$oldPersonUriRes = new Resource($first);
	$oldDocUriRes = new Resource($page);

error_log("uri = $uri ending $ending first $first and page $page and the new primaryTopic is $lame");
		
	//$this->getModel()->replace($oldDocUriRes,new Resource("<http://xmlns.com/foaf/0.1/primaryTopic>"),NULL,$newDocUriRes);
	$this->getModel()->replace($oldDocUriRes,NULL,NULL,$newDocUriRes);
	$this->getModel()->replace($oldPersonUriRes,NULL,NULL,$newPersonUriRes);
	$this->getModel()->replace(NULL,NULL,$oldPersonUriRes,$newPersonUriRes);
    }

	//replaces bNodes (to be called in between loading models into this) to make sure they're unique.
    public function mangleBnodes(){
	
	if(!$this->model || !property_exists($this->model,'triples') || !$this->model->triples || empty($this->model->triples)){
		return;
	}
	
	$firstLoop = true;
	$additionalNumber = false;

	foreach($this->model->triples as $triple){
		
		if($triple->subj instanceof BlankNode){
			$uriArray = explode('_',$triple->subj->uri);
			if(!$additionalNumber){
				$additionalNumber = 0;
				if(isset($uriArray[1])){
					$additionalNumber = $uriArray[1];	
				}
			}
			if(isset($uriArray[0])){
				$triple->subj->uri = $uriArray[0].'_'.$additionalNumber;
			}
		}
		if($triple->pred instanceof BlankNode){
			$uriArray = explode('_',$triple->pred->uri);
			if(!$additionalNumber){
				$additionalNumber = 0;
				if(isset($uriArray[1])){
					$additionalNumber = $uriArray[1];	
				}
			}
			if(isset($uriArray[0])){
				$triple->pred->uri = $uriArray[0].'_'.$additionalNumber;
			}
		}
		if($triple->obj instanceof BlankNode){
			$uriArray = explode('_',$triple->obj->uri);
			if(!$additionalNumber){
				$additionalNumber = 0;
				if(isset($uriArray[1])){
					$additionalNumber = $uriArray[1];	
				}
			}
			if(isset($uriArray[0])){
				$triple->obj->uri = $uriArray[0].'_'.$additionalNumber;
			}
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
    	
//	$graphset = ModelFactory::getDatasetMem('GarlikDataset');
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
