<?php
require_once 'Zend/Controller/Action.php';
require_once("helpers/JSON.php");
require_once("helpers/sparql.php");
require_once("helpers/settings.php");
require_once("helpers/Utils.php");
require_once("helpers/security_utils.php");

class AjaxController extends Zend_Controller_Action {

    public function init() {
        $this->view->baseUrl = $this->_request->getBaseUrl();
    }

    private $queryString;
    private $fieldNamesObject; 
    private $foafData;
    private $privateFoafData;
	
	public function loadIfpsAction(){
		set_time_limit(150);
                //find and decode the ifps
                $ifps = urldecode($_GET['ifps']);

                $ifps = substr($ifps,1);
                $json = new Services_JSON();
                $ifps = $json->decode(stripslashes($ifps));

                if(empty($ifps)){
                        return;
                }
                //build a query with them
                $ifps_filter = "FILTER(";
                foreach($ifps as $ifp){
                        $ifps_filter.=' ?z = '.$ifp.' ||';
                }
                $ifps_filter = substr($ifps_filter,0,-2).")";
                $livejournalGraphQuery=
                "PREFIX foaf: <http://xmlns.com/foaf/0.1/>
                PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
                SELECT DISTINCT ?graph
                WHERE { 
                    GRAPH ?graph {
                                ?x ?y ?z . FILTER(?y=foaf:weblog || ?y=foaf:homepage || ?y=foaf:mbox_sha1sum || ?y=foaf:mbox) . ".$ifps_filter." .
                                ?x foaf:knows ?someone .
                    }
                } limit 500";

                $nonLivejournalGraphQuery=
                "PREFIX foaf: <http://xmlns.com/foaf/0.1/>
                PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
                SELECT DISTINCT ?graph
                WHERE { 
                    GRAPH ?graph {
                                ?x ?y ?z . FILTER(?y=foaf:weblog || ?y=foaf:homepage || ?y=foaf:mbox_sha1sum || ?y=foaf:mbox) . ".$ifps_filter." .
                                ?graph foaf:primaryTopic ?x .
                    }
                } limit 500";

                $livejournalRes = sparql_query(FOAF_EP,$livejournalGraphQuery);
                $nonLivejournalRes = sparql_query(FOAF_EP,$nonLivejournalGraphQuery);

		/*separate livejournal and non livejournal stuff*/
                $livejournalGraphs = array();
                $nonLivejournalGraphs = array();
                foreach($livejournalRes as $liveUri){
                        $isLivejournalUri = true;
                        foreach($nonLivejournalRes as $nonLiveUri){
                                if($nonLiveUri == $liveUri){
                                        $isLivejournalUri = false;
                                }
                        }
                        if($isLivejournalUri){
                                array_push($livejournalGraphs,$liveUri);
                        } else {
                                array_push($nonLivejournalGraphs,$liveUri);
                        }
                }
                //query for the actual triples
                if(@$livejournalGraphs && @!empty($livejournalGraphs)){

                        $livejournalTriplesQuery = "PREFIX foaf: <http://xmlns.com/foaf/0.1/>
                                         construct{?a ?b ?c} WHERE { ";
                        foreach($livejournalGraphs as $row){
                                if(!isset($row['?graph']) || strpos($row['?graph'],'http://foaf.qdos.com/delicious')){
                                        continue;
                        }
                                $livejournalTriplesQuery .= " { GRAPH ".$row['?graph']." {?a ?b ?c}} UNION";
                        }
                        $livejournalTriplesQuery = substr($livejournalTriplesQuery,0,-5)."}";
                        $livejournalRes2 = sparql_query_xml(FOAF_EP,$livejournalTriplesQuery);
                }



                if(@$nonLivejournalGraphs && @!empty($nonLivejournalGraphs)){

                        $nonLivejournalTriplesQuery = "PREFIX foaf: <http://xmlns.com/foaf/0.1/>
                                 construct{?a ?b ?c} WHERE { ";
                        foreach($nonLivejournalGraphs as $row){
                                if(!isset($row['?graph']) || strpos($row['?graph'],'http://foaf.qdos.com/delicious')){
                                        continue;
                                }
                                $nonLivejournalTriplesQuery .= " { GRAPH ".$row['?graph']." {?a ?b ?c}} UNION";
                        }
                        $nonLivejournalTriplesQuery = substr($nonLivejournalTriplesQuery,0,-5)."}";
                        $nonLivejournalRes2 = sparql_query_xml(FOAF_EP,$nonLivejournalTriplesQuery);
                }

		 //shove the data into a temporary file
                if(@$livejournalRes2 && @!empty($livejournalRes2)){
                        $filenameLivejournal = BUILDER_TEMP_DIR.md5(uniqid('temp_rdf_lj'));
                        $filehandleLivejournal = fopen($filenameLivejournal,'w+');
                        fwrite($filehandleLivejournal,$livejournalRes2);
                }
                if(@$nonLivejournalRes2 && @!empty($livejournalRes2)){
                        $filenameNonLivejournal = BUILDER_TEMP_DIR.md5(uniqid('temp_rdf_nonlj_'));
                        $filehandleNonLivejournal = fopen($filenameNonLivejournal,'w+');
                        fwrite($filehandleNonLivejournal,$nonLivejournalRes2);
                }

                //if they are logged in then get our stuff
                $defaultNamespace = new Zend_Session_Namespace('Garlik');
                if($defaultNamespace->authenticated && $defaultNamespace->uri){
                        //TODO MISCHA, public and private load
                        error_log('Authenticated ! with an openid!');
                }

                //load it into the required foafdata object
                $this->loadFoaf(true);
                ini_set('memory_limit','128M');

                if(@$filenameNonLivejournal){
                        $this->foafData->addRDFtoModel($filenameNonLivejournal,$this->foafData->getUri());
                        $this->foafData->replacePrimaryTopic($this->foafData->getUri());
                        unlink($filenameNonLivejournal);
                }
                if(@$filenameLivejournal){
                        $this->foafData->mangleBnodes();
                        $this->foafData->addLJRDFtoModel($filenameLivejournal,$this->foafData->getUri());
                        $this->foafData->replacePrimaryTopic($this->foafData->getUri());
                        unlink($filenameLivejournal);
                }
		
		//TODO: this is an innefficient hack
                $filenameTemp = BUILDER_TEMP_DIR.md5(uniqid('temp_rdf_lj'));
                $filehandleTemp = fopen($filenameTemp,'w+');
		$foundStuff = $this->foafData->getModel()->find(NULL,NULL,NULL);

		if($foundStuff){

                	fwrite($filehandleTemp,$foundStuff->writeRdfToString());

			foreach($this->foafData->getModel()->triples as $triple){
                                $this->foafData->getModel()->remove($triple);
                        }

			$this->foafData->addRDFtoModel($filenameTemp,$this->foafData->getUri());
		}
		unlink($filenameTemp);
	
        }


	public function loadExtractorAction(){
    	
	set_time_limit(150);
	$this->loadFoaf();
		
	//some details
        $uri = @$_GET['uri'];
        $flickr = @$_GET['flickr'];
        //$delicious = @$_GET['delicious'];
        $lastfm = @$_GET['lastfmUser'];
        $lj = @$_GET['lj'];

	if (!check_key('get')) {
		error_log("GET hijack attempt load extractor ");
		exit();
	}
        
        //results
	$this->view->results = array();
        $this->view->results['flickrFound'] = $this->foafData->flickrFound;
        //$this->view->results['deliciousFound'] = $this->foafData->deliciousFound;
        $this->view->results['lastfmFound'] = $this->foafData->lastfmFound;
        $this->view->results['ljFound'] = $this->foafData->ljFound;
        $this->view->results['uriFound'] = false;
         
	//if they are logged in then get our stuff
	$defaultNamespace = new Zend_Session_Namespace('Garlik');
	if($defaultNamespace->authenticated && $defaultNamespace->uri){
		//TODO MISCHA, public and private load
		error_log('Authenticated ! with an openid!');
		//$this->foafData->getModel()->load($uri);
		//$this->foafData->replacePrimaryTopic($uri);
	}	

        //grab the foaf from the uri passed
        if($uri){
		$ok = 1;
		if (substr($uri, 0, 7) != "http://") {
			$ok = 0;
		}
		if (preg_match("(//(localhost|127\.0\.0\.1))", $uri)) {
			$ok = 0;
		}
		if (preg_match("(//[^.]*/)", $uri)) {
			$ok = 0;
		}
		if ($ok) {
			//TODO MISCHA, public and private load
			//just to ensure that the bnodes are unique
			$this->foafData->mangleBnodes();
			//$uriLoadOk = $this->foafData->getModel()->load($uri);
			$uriLoadOk = $this->foafData->addRDFtoModel($uri,$this->foafData->getUri());

			$this->foafData->replacePrimaryTopic();
			//TODO MISCHA TO PUT BACK IN
			if ($uriLoadOk != 1){
				$this->view->results['uriFound'] = true;
			}
		}
	}
	//grab the appropriate things if we haven't already
        if($lj && !$this->foafData->ljFound){
        	
		$ljUri = 'http://'.$lj.'.livejournal.com/data/foaf';
		//echo($ljUri);
		$this->foafData->mangleBnodes();
		//$lj = $this->foafData->getModel()->load($ljUri);
		$lj = $this->foafData->addLJRDFtoModel($ljUri,$this->foafData->getUri());
		// LJ are lame and don't set foaf:primaryTopic
  		//$this->foafData->replacePrimaryTopic($ljUri);
		if($lj != 1){
			$this->view->results['ljFound'] = true;
			$this->foafData->ljFound = true;
		}
        } 
	//grab the appropriate things if we haven't already
        if($flickr && !$this->foafData->flickrFound){
        	
        	//scrape the page to get the NSID
        	$flickr = Utils::getFlickrNsid($flickr);
       
		error_log("FLICKR the is the return $flickr");

        	//echo($flickr);
        	if($flickr!=0){
        		$flickrUri = 'http://foaf.qdos.com/flickr/people/'.$flickr;
			$this->foafData->mangleBnodes();
        		//echo($flickrUri);
        		$flickr = $this->foafData->addRDFtoModel($flickrUri,$this->foafData->getUri());
        		$this->foafData->replacePrimaryTopic($flickrUri);
			
        		if($flickr != 1){
					$this->view->results['flickrFound'] = true;
					$this->foafData->flickrFound = true;
				}
        	}
        } /*
        if($delicious && !$this->foafData->deliciousFound){
            
        	$deliciousUri = 'http://foaf.qdos.com/delicious/people/'.$delicious;
			$this->foafData->mangleBnodes();
        	$delicious = $this->foafData->getModel()->load($deliciousUri);
			$this->foafData->replacePrimaryTopic($flickrUri);
			
            if($delicious != 1){
				$this->view->results['deliciousFound'] = true;
				$this->foafData->deliciousFound = true;
			}
		} */
        if($lastfm && !$this->foafData->lastfmFound){
            	
        	$lastfmUri = 'http://foaf.qdos.com/lastfm/people/'.$lastfm; 
        	//$lastfm = $this->foafData->getModel()->load($lastfmUri);
        	$lastfm = $this->foafData->addRDFtoModel($lastfmUri,$this->foafData->getUri());
	        $this->foafData->replacePrimaryTopic($lastfmUri);
		
 		if($lastfm != 1){
			$this->view->results['lastfmFound'] = true;
			$this->foafData->lastfmFound = true;
		}
        }   
        
        //$result = $this->foafData->getModel()->find(NULL, NULL, NULL);
        //echo($result->writeRdfToString('nt'));
	}

	
    public function loadTheBasicsAction() {
	if (!check_key('post')) {
		error_log("POST hijack attempt ");
		exit();
	}
    	$this->loadAnyPage('theBasics');
    }
    public function loadContactDetailsAction() {
	if (!check_key('post')) {
		error_log("POST hijack attempt ");
		exit();
	}
    	$this->loadAnyPage('contactDetails');
    }
    public function loadPicturesAction() {
	if (!check_key('post')) {
		error_log("POST hijack attempt ");
		exit();
	}
    	$this->loadAnyPage('pictures');
    }
    public function loadLocationsAction() {
	if (!check_key('post')) {
		error_log("POST hijack attempt ");
		exit();
	}
    	$this->loadAnyPage('locations');
    }
    public function loadBlogsAction() {
	if (!check_key('post')) {
		error_log("POST hijack attempt ");
		exit();
	}
    	$this->loadAnyPage('blogs');
    }
    public function loadAccountsAction() {
	if (!check_key('post')) {
		error_log("POST hijack attempt ");
		exit();
	}
    	$this->loadAnyPage('accounts');
    }
    public function loadInterestsAction() {
	if (!check_key('post')) {
		error_log("POST hijack attempt ");
		exit();
	}
    	$this->loadAnyPage('interests');
    }
    public function loadFriendsAction() {
	if (!check_key('post')) {
		error_log("POST hijack attempt ");
		exit();
	}
    	/*build up a sparql query to get the values of all the fields we need*/
	$this->loadFoaf();
    	if(!$this->foafData) {   
	    return;
	}
        
	$this->fieldNamesObject = new FieldNames('friends',$this->foafData);  	
        $this->view->results = array();
          
	foreach ($this->fieldNamesObject->getAllFieldNames() as $field) {
            	//need to cope with multiple fields of the same type
            	$this->view->results = array_merge_recursive($this->view->results,$field->getData());
       	}
    } 
    
    public function loadOtherAction() {
	if (!check_key('post')) {
		error_log("POST hijack attempt ");
		exit();
	}

    	/*build up a sparql query to get the values of all the fields we need*/
    	if($this->loadFoaf()) {   
		if(!$this->foafData) {
			return;
		}
		$this->fieldNamesObject = new FieldNames('other',$this->foafData);  	
		$this->view->results = array();

		foreach ($this->fieldNamesObject->getAllFieldNames() as $field) {
			//need to cope with multiple fields of the same type
			$this->view->results = array_merge_recursive($this->view->results,$field->getData());
		}
        } 
    } 
    
    /*Does the mechanics of loading for the given page (e.g. theBasics etc) */
    private function loadAnyPage($fieldName){
	if (!check_key('post')) {
		error_log("POST hijack attempt ");
		exit();
	}
    	/*build up a sparql query to get the values of all the fields we need*/

        $this->loadFoaf();
        //echo($this->foafData->getPrimaryTopic());
        $this->fieldNamesObject = new FieldNames($fieldName,$this->foafData,$this->privateFoafData);  	
        $this->view->results = array();
        $this->view->results['private'] = array();
        $this->view->results['public'] = array();
        foreach($this->fieldNamesObject->getAllFieldNames() as $field){
           	//one day we might need to cope with multiple fields of the same type
        	if($field->getData()){
        		
        		$thisData = $field->getData();
        		if(isset($thisData['private']) && $thisData['private']){
        			$this->view->results['private'] = array_merge_recursive($this->view->results['private'], $thisData['private']);
        		} 
        		if(isset($thisData['public']) && $thisData['public']){
        			$this->view->results['public'] = array_merge_recursive($this->view->results['public'],$thisData['public']);
        		} 
        	}
        } 
    }
    
    /*gets the foaf (either from the uri or from the session) as well as adding stuff to the view*/

    private function loadFoaf($makeAnew=false) {

    	require_once 'FoafData.php';
        require_once 'FieldNames.php';
        require_once 'Field.php';  
        
        /* This returns a null if nothing in session! */
	if(!$makeAnew){
        	$this->foafData = FoafData::getFromSession(true);
		$this->privateFoafData = FoafData::getFromSession(false);
	} 
		
	
	/* Instantiate objects */
        if (!$this->foafData){
        	//echo('new object 1');
            $this->foafData = new FoafData(false,true);	  
        }
    	if (!$this->privateFoafData) {
            //echo('new object 2');
    		$this->privateFoafData = new FoafData(false,false);	
        }
    }
    
    private function putResultsIntoView() {
    	if($this->foafData) {
            $results = $this->view->graphset->sparqlQuery($this->queryString.";");	
            /*get rid of the ?s in the sparql results so they can be used with json*/
            $this->view->results = array();
            foreach($results as $row) {
                $keys = array_keys($row);
                $keys = str_replace('?','',$keys);
                array_push($this->view->results, array_combine($keys,$row));
            }
            $this->foafData->setPrimaryTopic($results[0]['?primaryTopic']->uri);      	
        } else {
            print "Error Instance of FoafData is null!\n";
	    $this->view->isSuccess = 0;
        }     
    }
    
    /*builds a sparql query*/
    private function buildSparqlQuery() {
        require_once 'FieldNames.php';
        $this->queryString = "
            PREFIX foaf: <http://xmlns.com/foaf/0.1/>
            PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
            PREFIX bio: <http://purl.org/vocab/bio/0.1/>
            PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
            SELECT ?primaryTopic ";

        /*Add ?foafName ?foafHomepage etc.*/
        $allFieldNamesArray = $this->fieldNamesObject->getAllFieldNames();
        foreach($allFieldNamesArray as $fieldName => $field) {
            $this->queryString .= "?".$fieldName." ";	
        }

        $this->queryString .= "
            FROM NAMED <".$this->view->uri.">
            WHERE 	
            { 
            ?z foaf:primaryTopic ?x .
            ?z foaf:primaryTopic ?primaryTopic .";

        foreach($allFieldNamesArray as $fieldName => $field) {
            $this->queryString .= " OPTIONAL { ".$field->getQueryBit()." . } .";	
        }
        print $this->queryString;
    }
	
    public function saveFoafAction() {

        $this->view->isSuccess = 0;
        require_once 'FoafData.php';
        $changes_model = @$_POST['model'];

	if (!check_key('post')) {
		error_log("POST hijack attempt ");
		exit();
	}
	
        
        if ($changes_model) {

            $publicFoafData = FoafData::getFromSession(true);	
            $privateFoafData = FoafData::getFromSession(false);
            
            if($publicFoafData) {
                $this->applyChangesToModel($publicFoafData,$changes_model);	
                $this->view->isSuccess = 1;
            }
            if($privateFoafData) {
            	$this->applyChangesToModel($privateFoafData,$changes_model);
            	$this->view->isSuccess = 1;
            }
        }
    }
	
    /*saves stuff to the model*/
    public function applyChangesToModel(&$foafData,&$changes_model) {
        require_once 'Field.php';
        require_once 'SimpleField.php';
        require_once 'FieldNames.php';
	
        //json representing stuff that is to be saved
        $json = new Services_JSON();
        $almost_model = $json->decode(stripslashes($changes_model));      
        //get all the details for each of the fields
        $fieldNames = new FieldNames('all');
        $allFieldNames = $fieldNames->getAllFieldNames();
       
        //var_dump($almost_model);
        //save private and public 
        if(!$foafData->isPublic && property_exists($almost_model,'private') && $almost_model->private){
        	$this->saveAllFields($almost_model->private,$allFieldNames,$foafData);   
  			     
        } else if($foafData->isPublic && property_exists($almost_model,'public') && $almost_model->public){
        	$this->saveAllFields($almost_model->public,$allFieldNames,$foafData);
        } 

    }
    
    private function saveAllFields($privateOrPublicModel,$allFieldNames,&$foafData){
    	/*loop through all the rows in the sparql results style 'almost model'*/
        foreach($privateOrPublicModel as $key => $value) {
            if(isset($allFieldNames[substr($key,0,-6)])) {
                /*get some details about the fields we're dealing with*/
                $field = $allFieldNames[substr($key,0,-6)];
                $field->saveToModel($foafData, $value);
            } else if($key == 'fields'){
            	//we need to look inside the simplefield array to do the right kind of save
            	foreach($value as $fieldName => $fieldValue){
            		 if(isset($allFieldNames[$fieldName])){
            		 	/*get some details about the fields we're dealing with*/
               	 		$field = $allFieldNames[$fieldName];
          
				/*save them using the appropriate method (notice that the save process
				is different depending on whether it is public or private*/
               			$field->saveToModel($foafData, $value);
            		 }            	 
            	}
            } else {
                echo("unrecognised fields:".$key."\n");	
            }//end if
        }//end foreach 	
    }
	
    //saves other stuff
    public function saveOtherAction() {
	if (!check_key('post')) {
		error_log("POST hijack attempt ");
		exit();
	}
     	$this->view->isSuccess = 0;
        require_once 'FoafData.php';
        
        $publicRdf = @$_POST['public'];
	$privateRdf = @$_POST['private'];
	$publicRdf = str_replace('\n','',$publicRdf);
	$publicRdf = str_replace('+','',$publicRdf);
	$privateRdf = str_replace('\n','',$privateRdf);
	$privateRdf = str_replace('+','',$privateRdf);
	   	
        if ($publicRdf && $privateRdf) {
         
            $publicFoafData = FoafData::getFromSession(true);	
            $privateFoafData = FoafData::getFromSession(false);	
           
            //TODO MISCHA speed up downloading here
            if($publicFoafData){
            	error_log('doing public stuff');
            	$newPublicModel = new MemModel();
            	
            	//shove the data into a file
            	$filename_1 = BUILDER_TEMP_DIR.md5(microtime() * microtime());
                $filehandle_1 = fopen($filename_1,'w+');
                fwrite($filehandle_1,$publicRdf);
                
                $newPublicModel->load($filename_1);
                $publicFoafData->replacePrimaryTopic($publicFoafData->getUri());
                $publicFoafData->setModel($newPublicModel);
          		
                unlink($filename_1);
                $publicFoafData->putInSession();
                
            }
            if($privateFoafData){
            	error_log('doing private stuff');
            	$newPrivateModel = new MemModel();
            	
            	$privateFoafData->setModel(new MemModel());
            	
            	$filename_2 = BUILDER_TEMP_DIR.md5(microtime() * microtime());
            	$filehandle_2 = fopen($filename_2,'w+');
            	fwrite($filehandle_2,$privateRdf);
            	
            	$newPrivateModel->load($filename_2);
            	$privateFoafData->replacePrimaryTopic($privateFoafData->getUri());
                $privateFoafData->setModel($newPrivateModel);
                 
               	unlink($filename_2);
            	$privateFoafData->putInSession();
            }
        }
    	
    }
    
    
    public function clearFoafAction() {
        if(@Zend_Session::destroy()) {
            echo(1);
        } else {
            echo(0);
        }
    }
}
	
