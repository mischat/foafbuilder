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
				echo("changes model there".$changes_model);
				
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

		//TODO, extend these to all of them.  possibly use an array.
		$foafNameCount = 0;
		$foafHomepageCount = 0;
				
		foreach($almost_model as $almost_row){
			foreach($almost_row as $key => $value){
				/*an example of a key: foafName_3 which would mean that we're dealing with the third foafName*/

				//TODO: need to do this for all predicates and to find a more sensible way of doing it. poss with an array.
				if($key == 'foafName'){
					$key = 'http://xmlns.com/foaf/0.1/name';
					$index = $foafNameCount;
					$foafNameCount++;
				} else if($key == 'foafHomepage') {
					$key = 'http://xmlns.com/foaf/0.1/homepage';
					$index = $foafHomepageCount;
					$foafHomepageCount++;
				}
				
				/* Create a new statement from the values to be saved FIXME: this is innefficient.*/
				if(isset($value->label)){
					$new_statement = new Statement(new Resource($foafData->getUri()),new Resource($key),new Literal($value->label));
				} else if(isset($value->uri)) {
					$new_statement = new Statement(new Resource($foafData->getUri()),new Resource($key),new Resource($value->uri));		
				} 
				
				if(isset($new_statement)){
					/* Look for values with the appropriate predicate/object */
					$found_model = $model->find(new Resource($almost_row->primaryTopic->uri),new Resource($key), NULL);
	
					/*
					 * Remove a matching triple (if there) and add the new one whilst remembering that there can
					 * be more than one e.g. foafName and we only want to remove the one at the appropriate index.
					 */ 
					if(isset($found_model->triples[$index])){
						$model->remove($found_model->triples[$index]);
					}
					$model->add($new_statement);
				}
			}
		}
	//	return $model;
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
