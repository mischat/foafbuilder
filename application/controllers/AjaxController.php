<?php
require_once 'Zend/Controller/Action.php';
require_once("helpers/JSON.php");
require_once("helpers/sparql.php");
require_once("helpers/settings.php");
require_once("helpers/Utils.php");

class AjaxController extends Zend_Controller_Action {
    public function init() {
        $this->view->baseUrl = $this->_request->getBaseUrl();
    }

    private $queryString;
    private $fieldNamesObject; 
    private $foafData;
    private $privateFoafData;

	public function loadFoafFromUri($uri) {
	 error_log("BBBBBBBBBBBBBBB");
	 error_log("BBBBBBBBBBBBBBB");
	 error_log("BBBBBBBBBBBBBBB");
	 error_log("BBBBBBBBBBBBBBB");
	 error_log("BBBBBBBBBBBBBBB");
	 error_log("BBBBBBBBBBBBBBB$uri");
	 error_log("BBBBBBBBBBBBBBB$uri");
	 error_log("BBBBBBBBBBBBBBB$uri");
	 error_log("BBBBBBBBBBBBBBB$uri");
	 error_log("BBBBBBBBBBBBBBB$uri");
	      $tempmodel = new NamedGraphMem($uri);
	   

	     /*load the rdf from the passed in uri into the model*/
	     $loadValue = $tempmodel->load($uri);		
		if ($loadValue==1) {
			return;		
		}

            $result = $tempmodel->find(NULL, NULL, NULL);

	   	
	    if ($result) { 
		    //loop through removing any hanging triples
		    foreach($result->triples as $triple){
			error_log("Is something happening here");
			error_log(var_dump($triple));
			$this->foafData->getModel()->addWithoutDuplicates($triple);
		    }
	    	    return 1;
		    flush($result);
		    flush($tempmodel);	
	    } else {
	        flush($result);
	        flush($tempmodel);	
		return 0;
	    }

	}

	
	public function loadExtractorAction(){
	
	$this->loadFoaf();
		
	//some details
        $uri = @$_GET['uri'];
        $flickr = @$_GET['flickr'];
        //$delicious = @$_GET['delicious'];
        $lastfm = @$_GET['lastfmUser'];
        $lj = @$_GET['lj'];
        
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
			$uriLoadOk = $this->loadFoafFromUri($uri);

			$this->foafData->replacePrimaryTopic($uri);
			$this->foafData->replaceGeneratorAgent();

			if($uriLoadOk != 0){
				$this->view->results['uriFound'] = true;
			}
		}
	}
	//grab the appropriate things if we haven't already
        if($lj && !$this->foafData->ljFound){
        	
		$ljUri = 'http://'.$lj.'.livejournal.com/data/foaf';
		//echo($ljUri);
		$this->foafData->mangleBnodes();
		$lj = $this->foafData->getModel()->load($ljUri);
		// LJ are lame and don't set foaf:primaryTopic
		$this->foafData->replaceKnowsSubject();
		
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
        		$flickr = $this->foafData->getModel()->load($flickrUri);
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
        	$lastfm = $this->foafData->getModel()->load($lastfmUri);
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
    	$this->loadAnyPage('theBasics');
    }
    public function loadContactDetailsAction() {
    	$this->loadAnyPage('contactDetails');
    }
    public function loadPicturesAction() {
    	$this->loadAnyPage('pictures');
    }
	public function loadLocationsAction() {
    	$this->loadAnyPage('locations');
    }
	public function loadBlogsAction() {
    	$this->loadAnyPage('blogs');
    }
	public function loadAccountsAction() {
    	$this->loadAnyPage('accounts');
    }
	public function loadInterestsAction() {
    	$this->loadAnyPage('interests');
    }
    public function loadFriendsAction() {

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
    	/*build up a sparql query to get the values of all the fields we need*/
    	if($this->loadFoaf()) {   
	if(!$this->foafData){
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
    	/*build up a sparql query to get the values of all the fields we need*/
        $this->loadFoaf();
        
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

    private function loadFoaf() {

    	require_once 'FoafData.php';
        require_once 'FieldNames.php';
        require_once 'Field.php';  
        
        /* This returns a null if nothing in session! */
        $this->foafData = FoafData::getFromSession(true);
		$this->privateFoafData = FoafData::getFromSession(false);
		
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
     echo('alpha');   
    	/*loop through all the rows in the sparql results style 'almost model'*/
        foreach($privateOrPublicModel as $key => $value) {
            
	echo('what?');		/*get rid of 'fields at the end of the name'*/
            if(isset($allFieldNames[substr($key,0,-6)])) {
            
	echo('whatid going on?');		/*get rid of 'fields at the end of the name'*/
                /*get some details about the fields we're dealing with*/
                $field = $allFieldNames[substr($key,0,-6)];
                
             echo('hmmmm'.$key);   /*save them using the appropriate method*/
                $field->saveToModel($foafData, $value);
echo('beta');
            } else if($key == 'fields'){
            	//we need to look inside the simplefield array to do the right kind of save
 		echo('midway through the for loop');
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
	echo('end');
    }
	
    //saves other stuff
    public function saveOtherAction() {
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
           
            //XXX FIXME the directory here shouldn't be hardcoded
            if($publicFoafData){
            	echo('doing public stuff');
            	$newPublicModel = new MemModel();
            	
            	//shove the data into a file
            	$filename_1 = BUILDER_TEMP_DIR.md5(microtime() * microtime());
                $filehandle_1 = fopen($filename_1,'w+');
                fwrite($filehandle_1,$publicRdf);
                
                $newPublicModel->load($filename_1);
                $publicFoafData->replacePrimaryTopic($publicFoafData->getUri());
                $publicFoafData->replaceGeneratorAgent();
                $publicFoafData->setModel($newPublicModel);
          		
                unlink($filename_1);
                $publicFoafData->putInSession();
                
            }
            if($privateFoafData){
            	echo('doing private stuff');
            	$newPrivateModel = new MemModel();
            	
            	$privateFoafData->setModel(new MemModel());
            	
            	$filename_2 = BUILDER_TEMP_DIR.md5(microtime() * microtime());
            	$filehandle_2 = fopen($filename_2,'w+');
            	fwrite($filehandle_2,$privateRdf);
            	
            	$newPrivateModel->load($filename_2);
            	$privateFoafData->replacePrimaryTopic($privateFoafData->getUri());
            	$privateFoafData->replaceGeneratorAgent();
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
	
