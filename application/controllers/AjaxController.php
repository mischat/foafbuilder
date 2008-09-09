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
	
            $queryString = "
            PREFIX foaf: <http://xmlns.com/foaf/0.1/>
            PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
            PREFIX bio: <http://purl.org/vocab/bio/0.1/>
            PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
                SELECT 
                	?primaryTopic
                    
                	?foafName 
                    ?foafHomepage 
                    ?foafNick
                    ?foafLocation
                    
                    ?foafBirthday
                    ?foafDateOfBirth
                    ?bioBirthday
                    
                    ?geoLatLong
                    ?geoLatLong
                    ?geoLatitude
                    ?geoLongitude
                    
                FROM NAMED <".$this->view->uri.">
                WHERE 
                		
                	{ 
                    ?z foaf:primaryTopic ?x .
                    ?z foaf:primaryTopic ?primaryTopic .
                    
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
                    	?x foaf:birthday ?foafBirthday .
                    }
                    OPTIONAL{
                        ?x foaf:based_near ?l .
                        ?l geo:lat ?geoLatitude .
                        ?l geo:lat ?geoLongitude .
                    } .
                    OPTIONAL{
                    	?x foaf:based_near ?l .
                    	?l geo:lat_long ?geoLatLong
        			}
        			OPTIONAL{
        				?x bio:event ?e .
        				?e rdf:type bio:Birth .
        				bio:birth bio:date ?bioBirthday .
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

	public function applyChangesToModel(&$foafData,&$changes_model)
	{
		/*
		 * TODO: it might be good to add functionality to save teh primary topic.
		 */
		//json representing stuff that is to be saved
		$json = new Services_JSON();
		$almost_model = $json->decode(stripslashes($changes_model));
		$model = $foafData->getModel();

		/*
		 * TODO, extend these to all of them.  possibly use an array.  We shouldn't have to do everything manually
		 * over and over for each predicate.  Also need to add language tabs etc.
		 */
		$foafNameCount = 0;
		$foafHomepageCount = 0;
		$foafNickCount = 0;
		
		foreach($almost_model as $key => $predicate_array){
			$skip=0;
			if($key == 'foafNameValueArray'){
				$type = 'literal';
				$predicate_uri = 'http://xmlns.com/foaf/0.1/name';
			} else if($key == 'foafHomepageValueArray') {
				$type = 'resource';
				$predicate_uri = 'http://xmlns.com/foaf/0.1/homepage';
			} else if($key == 'foafNickValueArray') {
				$type = 'literal';
				$predicate_uri = 'http://xmlns.com/foaf/0.1/nick';
			} else if($key != 'foafPrimaryTopic'){
				echo("Unknown predicate ".$key."\n");
			} else {
				//we don't need to do these steps for the foaf primary topic
				$skip = 1;
			}
			if(!$skip){
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
				}
			}
		}
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
	
