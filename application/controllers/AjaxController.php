<?php

require_once 'Zend/Controller/Action.php';
require_once("helpers/JSON.php");

class AjaxController extends Zend_Controller_Action
{
    public function init() {
        $this->view->baseUrl = $this->_request->getBaseUrl();
    }

    public function loadFoafAction() {
        require_once 'FoafData.php';
        $uri = @$_POST['uri'];
        if($uri) {
            $foafData = new FoafData($uri);	
        } else {
		//NOTE: keep this change after mischa's alterations.
		$foafData = FoafData::getFoafDataFromSession();
		var_dump($foafData);
	}
			
        /*Do we return stuff in json or HTML or at all FIXME?*/	
        if($foafData) {
            $this->view->model = $foafData->getModel();	
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
			$model = @$_POST['model'];
			if($model){
				$foafData = new FoafData();	
				if($foafData){
					$json = new Services_JSON();
					$foafData->setModel($json->decode($model));
					$this->view->isSuccess = 1;
				}
			}
	}
    }
	
/* vi:set expandtab sts=4 sw=4: */
