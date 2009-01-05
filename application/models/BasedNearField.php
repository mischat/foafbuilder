<?php
require_once 'Field.php';
require_once 'helpers/Utils.php';
/*FIXME: perhaps fields shouldn't do the whole sparql query thing in the constructor.*/

/*class to represent one item e.g. foafName or bioBirthday... not the same as one triple*/
class BasedNearField extends Field {
	
    /*predicateUri is only appropriate for simple ones (one triple only)*/
    public function BasedNearField($foafDataPublic,$foafDataPrivate,$fullInstantiation = true) {
 	$this->name = 'basedNear';
        $this->label = "I'm based near...";
        
        $this->data['public'] = array();
    	$this->data['public']['basedNearFields'] = array();
	$this->data['public']['basedNearFields']['basedNear'] = array();
	$this->data['public']['basedNearFields']['displayLabel'] = $this->label;
        $this->data['public']['basedNearFields']['name'] = $this->name;
          
        $this->data['private'] = array();
        $this->data['private']['basedNearFields'] = array();
	$this->data['private']['basedNearFields']['basedNear'] = array();
	$this->data['private']['basedNearFields']['displayLabel'] = $this->label;
        $this->data['private']['basedNearFields']['name'] = $this->name;
         
        /*don't sparql query the model etc if a full instantiation is not required*/
        if (!$fullInstantiation) {
		return;
        }
        
        /*Do full load*/
    	if($foafDataPublic){
		$this->doFullLoad($foafDataPublic);
	} 
	if($foafDataPrivate){
		$this->doFullLoad($foafDataPrivate);
	}
    }
    
    public function doFullLoad(&$foafData){
    	
    	/*load data from the model*/
        $queryString = $this->getQueryString($foafData->getPrimaryTopic());
        $results = $foafData->getModel()->SparqlQuery($queryString);		

        $privacy = 'private';
        if($foafData->isPublic){
        	$privacy = 'public';
        } 
        
        if($results && !empty($results)){
	    	/*mangle the results so that they can be easily rendered*/
	        foreach ($results as $row) {
	            $this->addBasedNearElements($row,$privacy);
	        }	    
       	}
    }

	private function removeAllExistingBasedNearTriples($foafData){
		
		$primary_topic_resource = new Resource($foafData->getPrimaryTopic());
		$predicate_resource = new Resource('http://xmlns.com/foaf/0.1/based_near');
		
	        //find existing triples
		$foundModel = $foafData->getModel()->find($primary_topic_resource,$predicate_resource,NULL);
			
		//remove existing triples
		foreach($foundModel->triples as $triple){
			$this->removeTripleRecursively($triple,$foafData);
		}
	}
	
	//TODO: move all copies of this function to utils
  /*removes a triple and all hanging triples and the ones that hang off them
     * but doesn't go any further. XXX perhaps it should?*/
    //XXX: should be able to use rap's remove with NULLs for pred/obj
    public function removeTripleRecursively($triple, &$foafData){
    	
	if (($triple instanceof BlankNode)) {
		$foundHangingStuff = $foafData->getModel()->find($triple->obj,NULL,NULL);
		
		if($foundHangingStuff && $foundHangingStuff->triples){
			foreach($foundHangingStuff->triples as $subTriple){
				if(property_exists($subTriple,'obj') && $subTriple->obj && property_exists($subTriple->obj,'uri')){
					
					$foundSubStuff = $foafData->getModel()->find($subTriple->obj,NULL,NULL);
						
					if($foundSubStuff && $foundSubStuff->triples){
						foreach($foundHangingStuff->triples as $subSubTriple){
							$foafData->getModel()->remove($subSubTriple);
						}
					}
					$foafData->getModel()->remove($subTriple);
				}
			}
		}
	}
    	$foafData->getModel()->remove($triple);	
    }
    
    /*saves the values created by the editor in value... as encoded in json.  Returns an array of bnodeids and random strings to be replaced by the view.*/
    public function saveToModel(&$foafData, $value) {
    	
    	//TODO: the privacy implementation means we no longer preserve based near triples that we don't care about
    	//it would be nice to preserve this at some point.  see old git checkouts for an example where this was done pre-privacy.
		$this->removeAllExistingBasedNearTriples($foafData);
		
        if(!isset($value->basedNear) || !$value->basedNear){
        	return;
    	}
		
		foreach($value->basedNear as $basedNearName => $basedNearContents){
			
			//if it is new or a bnode, save a bnode.  If not, save the uri.
			if(substr($basedNearName,0,5)=='bNode' || strlen($basedNearName) == 50){
				//$subject = new BlankNode($foafData->getModel());	
                		$subject = Utils::GenerateUniqueBnode($foafData->getModel());
			} else {
				$subject = new Resource($basedNearName);
			}
			
			if($basedNearContents && $basedNearContents->latitude != NULL && $basedNearContents->longitude != NULL){
				//create statements to define this bNode
				$basedNearStatement = new Statement(new Resource($foafData->getPrimaryTopic()),new Resource('http://xmlns.com/foaf/0.1/based_near'),$subject);	
			
				$newStatementLat = new Statement($subject, new Resource('http://www.w3.org/2003/01/geo/wgs84_pos#lat'), new Literal($basedNearContents->latitude));
				$newStatementLong = new Statement($subject, new Resource('http://www.w3.org/2003/01/geo/wgs84_pos#long'), new Literal($basedNearContents->longitude));
				$newStatementLatLong = new Statement($subject, new Resource('http://www.w3.org/2003/01/geo/wgs84_pos#lat_long'), new Literal($basedNearContents->latitude.",".$basedNearContents->longitude));
				
				$foafData->getModel()->add($basedNearStatement);
				$foafData->getModel()->add($newStatementLat);
				$foafData->getModel()->add($newStatementLong);
				$foafData->getModel()->add($newStatementLatLong);
			} 			
		}
    }
    

    private function isLatLongValid($coord) {
        //FIXME: something should go here to make sure the string makes sense.
        if (!$coord) {
            return false;
        } else {
            return true;
        }
    }

    private function isCoordValid($coord) {
    //FIXME: something should go here to make sure the string makes sense.
    if (!$coord) {
            return false;
        } else {
            return true;
        }
    }
  
    private function objectToArray($value) {
        $ret = array();
        foreach($value as $key => $value) {
            $ret[$key] = $value;
        }
        return $ret;
    }
    
   	/*takes a row from a sparql resultset and puts any based_near information into data*/
    private function addBasedNearElements($row,$privacy){
    	$newArray = array();
        
    	if (isset($row['?geoLatLong']) && $this->isLatLongValid($row['?geoLatLong'])) {
        	$latLongArray = split(",",$row['?geoLatLong']->label);
            $newArray['latitude']= $latLongArray[1];
            $newArray['longitude']= $latLongArray[0];    	
        }
        if (isset($row['?geoLat']) && $this->isCoordValid($row['?geoLat']) &&
        	isset($row['?geoLong']) && $this->isCoordValid($row['?geoLong'])) {
        	$newArray['latitude'] = $row['?geoLat']->label;
            $newArray['longitude'] = $row['?geoLong']->label;
        }
        if(isset($newArray['latitude']) && isset($newArray['longitude']) && isset($row['?location']) && $row['?location']->uri){
        	$this->data[$privacy]['basedNearFields']['basedNear'][$row['?location']->uri] = $newArray;
        }
    }
    
    
    
    private function getQueryString($primaryTopic){
    	
    	//TODO: add vacationhome and other locations as appropriate as well as more based_near detail and contact_nearest_airport
    	$queryString = "PREFIX contact: <http://www.w3.org/2000/10/swap/pim/contact#>
               	PREFIX foaf: <http://xmlns.com/foaf/0.1/>
                PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
                PREFIX bio: <http://purl.org/vocab/bio/0.1/>
                PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
                PREFIX wn: <http://xmlns.com/wordnet/1.6/>
                PREFIX air: <http://dig.csail.mit.edu/TAMI/2007/amord/air#>
             	PREFIX airalt: <http://www.megginson.com/exp/ns/airports#>
                SELECT 
                	?geoLat 
                	?geoLong 
                	?geoLatLong 
                	?location 
                
                WHERE{
	                	?z foaf:primaryTopic <".$primaryTopic.">
	                	?z foaf:primaryTopic ?primaryTopic
	              
	                OPTIONAL{
	                	?primaryTopic foaf:based_near ?location .
	                	?location geo:lat_long ?geoLatLong .
	                } .
	                OPTIONAL{
	                	?primaryTopic foaf:based_near ?location .
	                	?location geo:lat ?geoLat .
	                	?location geo:long ?geoLong .     
	                }
        		
                }";
    	return $queryString;
    }

}
