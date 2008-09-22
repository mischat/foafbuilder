<?php

require_once 'Zend/Controller/Action.php';
require_once("helpers/JSON.php");

class AjaxController extends Zend_Controller_Action
{
    public function init() 
    {
        $this->view->baseUrl = $this->_request->getBaseUrl();
    }
    private $queryString;
	private $fieldNamesObject; 
	private $foafData;
	
    public function loadTheBasicsAction() 
    {
    	/*build up a sparql query to get the values of all the fields we need*/
    	$this->loadFoaf();   
		$this->fieldNamesObject = new FieldNames('theBasics',$this->foafData);  	
		$this->view->results = array();
		foreach($this->fieldNamesObject->getAllFieldNames() as $field){
			array_push($this->view->results,$field->getData());	
		}
    }
    
   public function loadContactDetailsAction() 
    {
    	/*build up a sparql query to get the values of all the fields we need*/
    	$this->loadFoaf();   
		$this->fieldNamesObject = new FieldNames('contactDetails',$this->foafData);
    	$this->buildSparqlQuery();
    	$this->putResultsIntoView();
    }
    
   public function loadPicturesAction() 
    {
    	/*build up a sparql query to get the values of all the fields we need*/
    	$this->loadFoaf();   
		$this->fieldNamesObject = new FieldNames('pictures',$this->foafData);
    	$this->buildSparqlQuery();
    	$this->putResultsIntoView();
    }
    
   public function loadAccountsAction() 
    {
    	/*build up a sparql query to get the values of all the fields we need*/
    	$this->loadFoaf();   
		$this->fieldNamesObject = new FieldNames('accounts',$this->foafData);
    	$this->buildSparqlQuery();
    	$this->putResultsIntoView();
    }
    
   public function loadFriendsAction() 
    {
    	/*build up a sparql query to get the values of all the fields we need*/
    	$this->loadFoaf();   
		$this->fieldNamesObject = new FieldNames('friends',$this->foafData);
    	$this->buildSparqlQuery();
    	$this->putResultsIntoView();
    }
    
   	public function loadBlogsAction() 
    {
    	/*build up a sparql query to get the values of all the fields we need*/
    	$this->loadFoaf();   
		$this->fieldNamesObject = new FieldNames('blogs',$this->foafData);
    	$this->buildSparqlQuery();
    	$this->putResultsIntoView();
    }
    
    public function loadInterestsAction() 
    {
    	/*build up a sparql query to get the values of all the fields we need*/
    	$this->loadFoaf();   
		$this->fieldNamesObject = new FieldNames('interests',$this->foafData);
    	$this->buildSparqlQuery();
    	$this->putResultsIntoView();
    }
    
    public function loadOtherAction() 
    {
    	/*build up a sparql query to get the values of all the fields we need*/
    	$this->loadFoaf();   
		$this->fieldNamesObject = new FieldNames('other',$this->foafData);
    	$this->buildSparqlQuery();
    	$this->putResultsIntoView();
    }
    
    
	/*gets the foaf (either from the uri or from the session) as well as adding stuff to the view*/
    private function loadFoaf(){
    	require_once 'FoafData.php';
        require_once 'FieldNames.php';
        require_once 'Field.php';
       
        $uri = @$_POST['uri'];
        if($uri && $uri != "") {
            $this->foafData = new FoafData($uri);	
        } else {
			$this->foafData = FoafData::getFromSession();
		}
			
        if($this->foafData) {
        	/*push some stuff to the view TODO: do we need to push this to the view here 
        	 * since javascript is doing most of the rendering? */
             $this->view->model =   $this->foafData->getModel();	
             $this->view->uri =   $this->foafData->getURI();	
             $this->view->graphset =   $this->foafData->getGraphset();    
        }
    }
    
    private function putResultsIntoView(){
		  
    	if($this->foafData){
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
	private function buildSparqlQuery(){
		require_once 'FieldNames.php';
		$this->queryString = "
        	PREFIX foaf: <http://xmlns.com/foaf/0.1/>
        	PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
        	PREFIX bio: <http://purl.org/vocab/bio/0.1/>
            PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
                SELECT ?primaryTopic ";
            
       	/*Add ?foafName ?foafHomepage etc.*/
        $allFieldNamesArray = $this->fieldNamesObject->getAllFieldNames();
        foreach($allFieldNamesArray as $fieldName => $field){
        	$this->queryString .= "?".$fieldName." ";	
        }
         
        $this->queryString .= "
        	FROM NAMED <".$this->view->uri.">
            	WHERE 	
                { 
                    ?z foaf:primaryTopic ?x .
                    ?z foaf:primaryTopic ?primaryTopic .";
            
        foreach($allFieldNamesArray as $fieldName => $field){
        	 $this->queryString .= " OPTIONAL { ".$field->getQueryBit()." . } .";	
        }
 
	}
	
	public function saveFoafAction()
	{
		$this->view->isSuccess = 0;
	
		require_once 'FoafData.php';
		$changes_model = @$_POST['model'];
		
		if($changes_model){
			$foafData = FoafData::getFromSession();	
			if($foafData){
				$this->applyChangesToModel($foafData,$changes_model);	
				$foafData->putInSession();
				$this->view->isSuccess = 1;
			} else {
				echo("there aint anything in the session");
			}
		}
	}
	
	/*does the actual saving to the model*/
	public function applyChangesToModel(&$foafData,&$changes_model)
	{
		require_once 'Field.php';
		require_once 'SimpleField.php';
		require_once 'FieldNames.php';

		//json representing stuff that is to be saved
		$json = new Services_JSON();
		$almost_model = $json->decode(stripslashes($changes_model));
		$model = $foafData->getModel();

		/*
		 * TODO Need to add language tags etc.
		 */
		
		//get all the detail for each of the fields
		$fieldNames = new FieldNames('all');
		
		/*TODO: extend over all fields, not just simple ones, so here need to create the appropriate type of field*/
		$simpleFieldNameArray = $fieldNames->getAllFieldNames();
		
		/*loop through all the rows in the sparql results style 'almost model'*/
		foreach($almost_model as $key => $predicate_array){
					
			if(isset($simpleFieldNameArray[substr($key,0,-10)]) && $key != 'foafPrimaryTopic'){
				
				/*get some details about the field we're dealing with*/
				$field = $simpleFieldNameArray[substr($key,0,-10)];
					//echo(substr($key,0,-10));
				/*loop through writing out triples*/
				for($index = 0; $index < count($predicate_array) ;$index++){
					echo("Saving: ".$field->getName()." at index: ".$index."\n");
					$field->saveToModel($foafData, $predicate_array[$index]);
				}//end for	
				
			} else {
				echo("unrecognised triple:".$key."\n");	
			}//end if
		}//end foreach
	}
	
	
        //TODO really dirty	
	public function clearFoafAction() {
                if(@Zend_Session::destroy()) {
                    echo("Session destroyed properly");
                } else {
                    echo("Session not destroyed properly");
                }
        }
}
	
