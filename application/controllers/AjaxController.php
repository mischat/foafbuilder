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
                    ?name 
                    ?homepage 
                    ?nick
                    ?location
                FROM NAMED <".$this->view->uri.">
                WHERE { 
                    ?z foaf:primaryTopic ?x.
                    OPTIONAL{
                        ?x foaf:name ?name . 
                    } .
                    OPTIONAL{
                        ?x foaf:homepage ?homepage . 
                    } .
                    OPTIONAL{
                        ?x foaf:nick ?nick . 
                    } .
                    OPTIONAL{
                        ?x foaf:based_near ?location . 
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
        }       
    }
	
	public function saveFoafAction()
	{
			$this->view->isSuccess = 0;
			
			require_once 'FoafData.php';
			$changes_model = @$_POST['model'];
			if($changes_model){
				$foafData = new FoafData();	
				if($foafData){
		
					$foafData = FoafData::getFromSession();
				
					$new_model = $this->applyChangesToModel($foafData,$changes_model);							
					
					$foafData->setModel($new_model);
					$foafData->putInSession();
					$this->view->isSuccess = 1;
				}
			}
	}
	
	public function applyChangesToModel($foafData,$changes_model)
	{
		
		//json representing stuff that has been changed on the page (a bit like sparql results)
		$json = new Services_JSON();
		$almost_model = $json->decode(stripslashes($changes_model));
		$new_model = $foafData->getModel();
					
		/*FIXME: this needs to be more sophisticated and do a similar thing to the query above rather
		* than not taking account of the primary topic.  There ought to be a more simple way to do this.*/
		$i=0;
		foreach($foafData->getModel()->triples as $triple){
			foreach($almost_model as $almost_row){
				foreach($almost_row as $key => $value){
					if($triple->pred->uri == "http://xmlns.com/foaf/0.1/".$key){
						if(isset($value->uri) && isset($triple->obj->uri)){
							//TODO: need to not just set all triples with this pred to this value.
							$new_model->triples[$i]->obj->uri = $value->uri;	
						}
						if(isset($value->label) && isset($triple->obj->label)){
							//TODO: need to not just set all triples with this pred to this value.
							$new_model->triples[$i]->obj->label = $value->label;
						}
					}
				}
			}
			$i++;
		}	
		return $new_model;			
	}
}
	
/* vi:set expandtab sts=4 sw=4: */
