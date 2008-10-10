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
    private $randomStringToBnodeArray;
    
    /*New from uri if uri present. if not just new.*/
    public function FoafData ($uri = "") {
        if ($uri) {
	    //TODO This name shouldnt be hardcoded.
	    $graphset = ModelFactory::getDatasetMem('Dataset1');
	    $model = new NamedGraphMem($uri);
	    $model->load($uri);
	    $graphset->addNamedGraph($model);
	        
	    if (!($graphset->containsNamedGraph($uri))) {
	        print "Triples model not add to the modelfactory\n";
	    }

	    /*
            $result = $model->find(new Resource($uri),new Resource('http://xmlns.com/foaf/0.1/primaryTopic'),NULL);
            $oldUri = "";
            $it = $result->getStatementIterator();
            while ($it->hasNext()) {
                $statement = $it->next();
                if ($it->getCurrentPosition() == 0) {
                    $oldUri = (string) $statement->getLabelObject();
                }
            } 
            */
	        
	    /*Could swap these two lines with commented out block above*/
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
            }

            $oldUriRes = new Resource($oldUri);
            $newUri = "http://".md5($oldUri);
            $newUriRes = new Resource($newUri);
            $model->replace($oldUriRes,NULL,NULL,$newUriRes);
            $model->replace(NULL,NULL,$oldUriRes,$newUriRes);
            $this->primaryTopic = $newUri;
            $this->randomStringToBnodeArray = array();
            
            if (!preg_match("/#me$/",$oldUri,$patterns)) {
                $model->add(new Statement($newUriRes,new Resource("http://www.w3.org/2002/07/owl#sameAs"),$oldUriRes));
            }
          
	    if ($model!=null) { 
	        $this->model = $model;
	        $this->uri = $uri;
	        $this->graphset = $graphset;
	    }
	    $this->putInSession();
        } else {
            //FIXME: sort this out so it isn't an echo
            //echo("Something went wrong, there's no URI!");
            error_log("Something very lame\n");
            return 0;
        }
    }
    
    public static function getFromSession() {
    	//TODO: use auth session for particular users
        $defaultNamespace = new Zend_Session_Namespace('Garlik');
    	//XXX This probably ought to be changed for production
    	return $defaultNamespace->foafData;
    }
    
    public function putInSession() {
    	//TODO: use auth session for particular users
    	$defaultNamespace = new Zend_Session_Namespace('Garlik');
    	//XXX This probably ought to be changed for production
    	//$defaultNamespace->setExpirationSeconds(9999999999);
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
}
/* vi:set expandtab sts=4 sw=4: */
