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
      
        $uri = @$_POST['uri'];
        if($uri) {
            $foafData = new FoafData($uri);	
        } else {
			//NOTE: keep this change after mischa's alterations.
			$foafData = FoafData::getFromSession();
		}
			
        if($foafData) {
            $this->view->model = $foafData->getModel();	
           // var_dump($this->view->model);
            $this->view->uri = $foafData->getURI();	
            $this->view->graphset= $foafData->getGraphset();	
	
            $queryString = "PREFIX foaf: <http://xmlns.com/foaf/0.1/>
                SELECT 
                    ?foafName 
                    ?foafHomepage 
                    ?foafNick
                    ?foafLocation
                    ?primaryTopic
                FROM NAMED <".$this->view->uri.">
                WHERE { 
                    ?z foaf:primaryTopic ?x.
                    ?z foaf:primaryTopic ?primaryTopic.
                    OPTIONAL{
                        ?x foaf:name ?foafName . 
                    } .
                    OPTIONAL{
                        ?x foaf:homepage ?foafHomepage . 
                    } .
                    OPTIONAL{
                        ?x foaf:nick ?foafNick . 
                    } .
                    OPTIONAL{
                        ?x foaf:based_near ?foafLocation . 
                    } 
                };
            ";
            $results = $this->view->graphset->sparqlQuery($queryString);

            //get rid of the ?s in the sparql results so they can be used with json
            $this->view->results = array();
            foreach($results as $row) {
                $keys = array_keys($row);
                $keys = str_replace('?','',$keys);
                array_push($this->view->results, array_combine($keys,$row));
            }
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

        //TODO could this be a private function?
	public function applyChangesToModel(&$foafData,&$changes_model)
	{
		//json representing stuff that has been changed on the page (a bit like sparql results)
		$json = new Services_JSON();
		$almost_model = $json->decode(stripslashes($changes_model));
		$model = $foafData->getModel();

		//TODO, extend these to all of them.  possibly use an array.  WE shouldn't have to do everything
		//TODO: extend to include language tabs etc.
		//over and over again for each predicate.
		$foafNameCount = 0;
		$foafHomepageCount = 0;
		$foafNickCount = 0;
		
		//TODO: tidy this up!
		foreach($almost_model as $key => $predicate_array){
			
			if($key == 'foafNameValueArray'){
				$type = 'literal';
				$predicate_uri = 'http://xmlns.com/foaf/0.1/name';
			} else if($key == 'foafHomepageValueArray') {
				$type = 'resource';
				$predicate_uri = 'http://xmlns.com/foaf/0.1/homepage';
			} else if($key == 'foafNickValueArray') {
				$type = 'literal';
				$predicate_uri = 'http://xmlns.com/foaf/0.1/nick';
			} else {
				echo("Unknown predicate ".$key."\n");
			}
			
			/*TODO: start from here down.  Need to go round and set all statements as appropriate*/		
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
				$primary_topic_resource = new Resource($foafData->getUri()."#me");
				$new_statement = new Statement($primary_topic_resource,$predicate_resource,$value_res_or_literal);	
				
				/* Look for values with the appropriate predicate/object */
				$found_model = $model->find($primary_topic_resource,$predicate_resource, NULL);
				
				/* Remove a matching triple (if there) and add the new one whilst remembering that there can
				 * be more than one e.g. foafName and we only want to remove the one at the appropriate index.*/ 
				if(isset($found_model->triples[$index])){
					echo("removing triple: ".$predicate_array[$index]." $key "."\n");
					echo("index: ".$index."\n");
					$model->remove($found_model->triples[$index]);
				}
				$model->add($new_statement);
			}
		}
	}	
		
        
	public function clearFoafAction() {
		$this->view->isSuccess = 0;
		$foafData = FoafData::getFromSession();
                if ($foafData) {
                    $foafData->killSession();
		    $this->view->isSuccess = 1;
                }
        }
}
	
/* vi:set expandtab sts=4 sw=4: */
