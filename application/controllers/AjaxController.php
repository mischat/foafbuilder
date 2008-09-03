<?php

require_once 'Zend/Controller/Action.php';

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
			if($uri){
				$foafData = new FoafData($uri);	
			}
			
			/*Do we return stuff in json or HTML or at all FIXME?*/	
			if($foafData){
				$this->view->model = $foafData->getModel();	
						
				/*TODO: add more of these to get all the stuff that's necessary.*/ 
				$queryString = "
					PREFIX foaf: <http://xmlns.com/foaf/0.1/>
					SELECT 
						?name 
						?homepage 
						?nick
					WHERE {
						?z foaf:primaryTopic ?x.
						OPTIONAL{
							?x foaf:name ?name . 
						} .
						OPTIONAL{
							?x foaf:homepage ?homepage . 
						}
						OPTIONAL{
							?x foaf:nick ?nick . 
						}
					};
				";
				
				$results = $this->view->model->sparqlQuery($queryString);
				
				/*get rid of the ?s in the sparql results so they can be used with json*/
				$this->view->results = array();
				foreach($results as $row){
					$keys = array_keys($row);
					$keys = str_replace('?','',$keys);
					array_push($this->view->results, array_combine($keys,$row));
				}
			}
			
			
	}
	
	public function saveFoafAction()
	{
			require_once 'FoafData.php';
			$uri = @$_POST['uri'];
			if($uri){
				$foafData = new FoafData($uri);	
				
				if($foafData){
					 $this->view->model = $foafData->getModel();
				}
			}
	}
	
	
}