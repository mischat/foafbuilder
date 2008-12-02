<?php
require_once 'Zend/Controller/Action.php';
require_once("helpers/JSON.php");
require_once("helpers/sparql.php");
require_once("helpers/settings.php");
require_once("helpers/Utils.php");

class AccountsController extends Zend_Controller_Action {
	
    public function init() {
        $this->view->baseUrl = $this->_request->getBaseUrl();
    }
	
    
    /*converts a userame to a URI*/
	//converts usernames into uris
	public function usernameToUriAction(){
		
		//build a query from the names of the post variables to get the appropriate patterns
		$query = "PREFIX gs: <http://qdos.com/schema#> 
                        PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#> 
                        SELECT ?serv ?patt WHERE 
                        { ?serv rdfs:subPropertyOf gs:serviceProperty ; gs:canonicalUriPattern ?patt .
                        FILTER(";
        //we only want to get canonical patterns of things that are passed in
		foreach($_POST as $key => $value){
			$query.='?serv = <http://qdos.com/schema#'.$key.'> || ';			
		}
		
		
		$query = substr($query,0,-3).')}';
		
		
		echo($query);
		
		//execute the query to get the patterns
		$res = sparql_query(QDOS_EP,$query);

                if(!$res || empty($res)){
                        return;
                }

		//use this to contain the patterns for all of the usernames passed in
                $uris = array();
          
                foreach($res as $row){
                        if(!isset($row['?serv']) || !$row['?serv']){
                                continue;
                        } 
                        if(!isset($row['?patt']) || !$row['?patt']){
                                continue;
                        }
                        //create the various uris
			foreach($_POST as $key => $value){
                        	if(sparql_strip($row['?serv']) == 'http://qdos.com/schema#'.$key){
					$uris[$key] = str_replace('@USER@',$value,sparql_strip($row['?patt']));
				}
			}
		}
                $this->view->results = $uris;
	}
	
	/*get all of the various types of account*/
	public function getAllAccountTypesAction(){
		
		/*query to get account homepages and names*/
		$query = "PREFIX gs: <http://qdos.com/schema#> 
    					PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#> 
        				SELECT ?page ?name WHERE {	
        					?serv rdfs:subPropertyOf gs:serviceProperty
        				    ?serv gs:serviceHomepage ?page .
        					?serv gs:serviceName ?name .
						} LIMIT 200";
		$res = sparql_query(QDOS_EP,$query);
		
		
		/*loop through the results and create ones that are easier to work with in javascript*/
		$retArray = array();
		foreach($res as $row){
			
			$thisArray = array();
			
			if(!isset($row['?page']) || !isset($row['?name'])){
				continue;
			}	
			
			$thisArray['name'] = sparql_strip($row['?name']);
			$thisArray['page'] = sparql_strip($row['?page']);
			array_push($retArray,$thisArray);
		}
		
		/*add the results to the view*/
		$this->view->results = $retArray;
	}
}
	
