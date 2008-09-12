<?php

require_once 'Zend/Controller/Action.php';
require_once("helpers/JSON.php");

class AjaxController extends Zend_Controller_Action
{
    public function init() 
    {
        $this->view->baseUrl = $this->_request->getBaseUrl();
    }

    public function loadFoafAction() 
    {
        require_once 'FoafData.php';
        require_once 'FieldNames.php';
        require_once 'Field.php';
      
        $uri = @$_POST['uri'];
        if($uri && $uri != "") {
            $foafData = new FoafData($uri);	
        } else {
			$foafData = FoafData::getFromSession();
		}
			
        if($foafData) {
        	/*push some stuff to the view TODO: do we need to push this to the view here 
        	 * since javascript is doing most of the rendering? */
            $this->view->model = $foafData->getModel();	
            $this->view->uri = $foafData->getURI();	
            $this->view->graphset= $foafData->getGraphset();
			
            //var_dump($this->view->model);
            //echo("+++++++++++============++++++++++++++");
            /*build up a sparql query to get the values of all the fields we need*/
            //TODO: make this relative to the page, possibly more than one function or controller.
			$fieldNamesObject = new FieldNames();
            $queryString = $this->buildSparqlQuery($fieldNamesObject);           
            $results = $this->view->graphset->sparqlQuery($queryString.";");
            
            /*get rid of the ?s in the sparql results so they can be used with json*/
            $this->view->results = array();
            foreach($results as $row) {
                $keys = array_keys($row);
                $keys = str_replace('?','',$keys);
                array_push($this->view->results, array_combine($keys,$row));
            }
           
            $foafData->setPrimaryTopic($results[0]['?primaryTopic']->uri);      	
        } else {
            print "Error Instance of FoafData is null!\n";
	    $this->view->isSuccess = 0;
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
	/*builds a sparql query for a given fieldNamesObject*/
	private function buildSparqlQuery($fieldNamesObject){
		require_once 'FieldNames.php';
		$queryString = "
        	PREFIX foaf: <http://xmlns.com/foaf/0.1/>
        	PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
        	PREFIX bio: <http://purl.org/vocab/bio/0.1/>
            PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
                SELECT ?primaryTopic ";
            
       	/*Add ?foafName ?foafHomepage etc.*/
        $allFieldNamesArray = $fieldNamesObject->getAllFieldNames();
        foreach($allFieldNamesArray as $fieldName => $field){
        	$queryString .= "?".$fieldName." ";	
        }
         
        $queryString .= "
        	FROM NAMED <".$this->view->uri.">
            	WHERE 	
                { 
                    ?z foaf:primaryTopic ?x .
                    ?z foaf:primaryTopic ?primaryTopic .";
            
        foreach($allFieldNamesArray as $fieldName => $field){
        	$queryString .= " OPTIONAL { ".$field->getQueryBit()." . } .";	
        }

        return $queryString;
	}
	
	/*does the actual saving to the model*/
	public function applyChangesToModel(&$foafData,&$changes_model)
	{
		require_once 'Field.php';
		require_once 'FieldNames.php';
		/*
		 * TODO: it might be good to add functionality to save teh primary topic.
		 */
		//json representing stuff that is to be saved
		$json = new Services_JSON();
		$almost_model = $json->decode(stripslashes($changes_model));
		$model = $foafData->getModel();

		/*
		 * TODO Need to add language tags etc.
		 */
		$foafNameCount = 0;
		$foafHomepageCount = 0;
		$foafNickCount = 0;
		
		//get all the detail for each of the fields
		$fieldNames = new FieldNames();
		
		//TODO: extend over all fields, not just simple ones
		$simpleFieldNameArray = $fieldNames->getSimpleFieldNames();
		
		/*loop through all the rows in the sparql results style 'almost model'*/
		foreach($almost_model as $key => $predicate_array){
			
			if(isset($simpleFieldNameArray[substr($key,0,-10)]) && $key != 'foafPrimaryTopic'){
				
				/*get some details about the field we're dealing with*/
				$field = $simpleFieldNameArray[substr($key,0,-10)];
				$type = $field->getType();
				$predicate_uri = $field->getPredicateUri();	
				
				/*loop through writing out triples*/
				for($index = 0; $index < count($predicate_array) ;$index++){
					/* Create a new statement from the values to be saved FIXME: this is inefficient.*/
					$predicate_resource = new Resource($predicate_uri);
					if($type == 'literal'){
						$value_res_or_literal = new Literal($predicate_array[$index]);
					} else if($type == 'resource'){
						$value_res_or_literal = new Resource($predicate_array[$index]);
					} else {
						//TODO: bnodes?
						echo("Not a uri or a bnode!");
					}
					//TODO: need to get the primary topic in here somehow instead of doing .#me poss from session?
					$primary_topic_resource = new Resource($foafData->getPrimaryTopic());
					$new_statement = new Statement($primary_topic_resource,$predicate_resource,$value_res_or_literal);	
					
					/* Look for values with the appropriate predicate/object */
					$found_model = $model->find($primary_topic_resource,$predicate_resource, NULL);
					
					/* Remove a matching triple (if there) and add the new one whilst remembering that there can
					 * be more than one e.g. foafName and we only want to remove the one at the appropriate index.*/ 
					if(isset($found_model->triples[$index])){
						//TODO - worry about the ordering of sparql results
						$model->remove($found_model->triples[0]);
					}
					$model->add($new_statement);
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
	
