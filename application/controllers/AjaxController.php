<?php
require_once 'Zend/Controller/Action.php';
require_once("helpers/JSON.php");

class AjaxController extends Zend_Controller_Action {
    public function init() {
        $this->view->baseUrl = $this->_request->getBaseUrl();
    }

    private $queryString;
    private $fieldNamesObject; 
    private $foafData;
    private $privateFoafData;
	
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
    
  
    public function loadAccountsAction() {
    	/*build up a sparql query to get the values of all the fields we need*/
    	if ($this->loadFoaf()) {   
            $this->fieldNamesObject = new FieldNames('accounts',$this->foafData);  	
            $this->view->results = array();
            foreach ($this->fieldNamesObject->getAllFieldNames() as $field) {
            	
            	//need to cope with multiple fields of the same type
            	$this->view->results = array_merge_recursive($this->view->results,$field->getData());
          
            }
        } 
    }
    
    public function loadBlogsAction() {
        /*build up a sparql query to get the values of all the fields we need*/
        if($this->loadFoaf()) {   
            $this->fieldNamesObject = new FieldNames('blogs',$this->foafData);  	
            $this->view->results = array();
           	foreach ($this->fieldNamesObject->getAllFieldNames() as $field) {
            	
            	//need to cope with multiple fields of the same type
            	$this->view->results = array_merge_recursive($this->view->results,$field->getData());
          
            }
        } 
    }
    
    public function loadInterestsAction() {
        /*build up a sparql query to get the values of all the fields we need*/
        if($this->loadFoaf()) {   
            $this->fieldNamesObject = new FieldNames('interests',$this->foafData);  	
            $this->view->results = array();
           foreach ($this->fieldNamesObject->getAllFieldNames() as $field) {
            	
            	//need to cope with multiple fields of the same type
            	$this->view->results = array_merge_recursive($this->view->results,$field->getData());
          
            }
        } 
    }
    
    public function loadOtherAction() {
    	/*build up a sparql query to get the values of all the fields we need*/
    	if($this->loadFoaf()) {   
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
		//echo("foafData primary topic: ".$this->foafData->getPrimaryTopic());
		//echo("privateFoafData primary topic: ".$this->privateFoafData->getPrimaryTopic());
		//var_dump($this->privateFoafData);
		//var_dump(privateFoafData);
		
        if (!$this->foafData) {
            //print "First time !\n";
            $uri = @$_POST['uri'];
            $this->foafData = new FoafData($uri);	  
        }
    	if (!$this->privateFoafData) {
            //print "First time !\n";
            $uri = @$_POST['uri'];
            $this->privateFoafData = new FoafData($uri,false);	
        }
        
 //       	var_dump($this->privateFoafData); 
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
            //echo($privateFoafData->getPrimaryTopic()."Hmmm");
            //echo($publicFoafData->getPrimaryTopic()."Hmmm"); 
            
            if($publicFoafData) {
          //  	echo("Saving public");
                $this->applyChangesToModel($publicFoafData,$changes_model);	
                $this->view->isSuccess = 1;
            }
            if($privateFoafData) {
       //     	echo("Saving private");
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
       
       // var_dump($almost_model);
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
            /*get rid of 'fields at the end of the name'*/
            if(isset($allFieldNames[substr($key,0,-6)])) {
                /*get some details about the fields we're dealing with*/
                $field = $allFieldNames[substr($key,0,-6)];
                /*save them using the appropriate method*/
                $field->saveToModel($foafData, $value);
                
            } else if($key == 'fields'){
            	//we need to look inside the simplefield array to do the right kind of save
            	//XXX: is the level of abstraction right here? 
            	foreach($value as $fieldName => $fieldValue){
            		 if(isset($allFieldNames[$fieldName])) {
            		 	/*get some details about the fields we're dealing with*/
               	 		$field = $allFieldNames[$fieldName];
          
						/*save them using the appropriate method (notice that the save process
						 is different depending on whether it is public or private*/
               			$field->saveToModel($foafData, $value);
               		
            		 }            	 
            	}
            }else{
                echo("unrecognised fields:".$key."\n");	
            }//end if
        }//end foreach 	
    }
	
    //TODO really dirty	MISCHA not sure why this isnt working properly !
    public function clearFoafAction() {
        if(@Zend_Session::destroy()) {
            echo("Session destroyed properly");
        } else {
            echo("Session not destroyed properly");
        }
    }
}
	
