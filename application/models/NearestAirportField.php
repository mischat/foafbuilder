<?php
require_once 'Field.php';
require_once 'helpers/Utils.php';
/*FIXME: perhaps fields shouldn't do the whole sparql query thing in the constructor.*/

/*class to represent one item e.g. foafName or bioBirthday... not the same as one triple*/
class NearestAirportField extends Field {
	
    /*predicateUri is only appropriate for simple ones (one triple only)*/
    public function NearestAirportField($foafDataPublic,$foafDataPrivate,$fullInstantiation = true) {
    	
    	$this->name = 'nearestAirport';
        $this->label = 'My Nearest Airport';
        
    	$this->data['public']['nearestAirportFields'] = array();
    	$this->data['public']['nearestAirportFields']['displayLabel'] = $this->label;
        $this->data['public']['nearestAirportFields']['name'] = $this->name;
        $this->data['public']['nearestAirportFields']['nearestAirport'] = array();
     	
        $this->data['private']['nearestAirportFields'] = array();
    	$this->data['private']['nearestAirportFields']['displayLabel'] = $this->label;
        $this->data['private']['nearestAirportFields']['name'] = $this->name;
        $this->data['private']['nearestAirportFields']['nearestAirport'] = array();
       
        if (!$fullInstantiation) {
			return;
        }

   		if($foafDataPublic){
			$this->doFullLoad($foafDataPublic,'public');
		} 
		if($foafDataPrivate){
			$this->doFullLoad($foafDataPrivate,'private');
		}
       
    }
    
    private function doFullLoad($foafData,$privacy){

    	if(!$foafData || !$privacy){
    		return;	
    	}
    	
    	$queryString = $this->getQueryString($foafData->getPrimaryTopic());
	    $results = $foafData->getModel()->SparqlQuery($queryString);		
		
        if($results && !empty($results)){
    
	        /*mangle the results so that they can be easily rendered*/
	        foreach ($results as $row) {
	        	$this->addNearestAirportElements($row,$privacy);
	        }	
        }
    }

    private function removeAirports(&$foafData,$existingAirports1,$exceptionBnode = false){
		
    	//loop through existing airports removing them
		foreach($existingAirports1->triples as $triple){
			
			/*don't remove the exception*/
			if($exceptionBnode && $triple->obj == $exceptionBnode){
				continue;	
			}
			
			//remove this triple
			$foafData->getModel()->remove($triple);
			$foundSubTriples = $foafData->getModel()->find($triple->obj,NULL,NULL);
					
			//remove all triples that are hanging off this one
			if(!$foundSubTriples->triples || empty($foundSubTriples->triples)){
				continue;
			}
			foreach($foundSubTriples->triples as $subTriple){
				$foafData->getModel()->remove($subTriple);
			}
		}
    }
	
    /*saves the values created by the editor in value... as encoded in json.  Returns an array of bnodeids and random strings to be replaced by the view.*/
    public function saveToModel(&$foafData, $value) {
	    
    	if(!property_exists($value,'nearestAirport') || !$value->nearestAirport){
    		return;
    	}
    	
    	//check for existing airports of either type
    	$existingAirports1 = $foafData->getModel()->find(new Resource($foafData->getPrimaryTopic()),new Resource('http://www.w3.org/2000/10/swap/pim/contact#nearestAirport'),NULL);
    	
    	 /*check if there are any airport codes in the response, if not delete all the airports in this model*/
    	if(!property_exists($value->nearestAirport,'iataCode') 
    		&& !property_exists($value->nearestAirport,'icaoCode')){
    		$this->removeAirports($foafData,$existingAirports1);
    	} 
    	
    	/*remove all but the first airport, since there should only be one*/
    	$airportBnode = false;
    	if($existingAirports1 && !empty($existingAirports1->triples)){
    		$airportBnode = $existingAirports1->triples[0]->obj;
			$this->removeAirports($foafData,$existingAirports1,$existingAirports1->triples[0]->obj);
		} 
				
    	/*if there is no airport already there then add one*/
    	if(!$airportBnode){
    		$airportBnode = Utils::GenerateUniqueBnode($foafData->getModel());
    		$foafData->getModel()->add(new Statement(new Resource($foafData->getPrimaryTopic()),new Resource('http://www.w3.org/2000/10/swap/pim/contact#nearestAirport'),$airportBnode));
    		$foafData->getModel()->add(new Statement($airportBnode,new Resource('http://www.w3.org/1999/02/22-rdf-syntax-ns#type'),new Resource('http://xmlns.com/wordnet/1.6/Airport')));
    	}
    	
    	/*remove the existing icao and iata codes*/
    	$removeTriples1 = $foafData->getModel()->find($airportBnode,new Resource('http://dig.csail.mit.edu/TAMI/2007/amord/air#iata'),NULL);
    	$removeTriples2 = $foafData->getModel()->find($airportBnode,new Resource('http://dig.csail.mit.edu/TAMI/2007/amord/air#icao'),NULL);
    	$removeTriples3 = $foafData->getModel()->find($airportBnode,new Resource('http://www.megginson.com/exp/ns/airports#iata'),NULL);
    	$removeTriples4 = $foafData->getModel()->find($airportBnode,new Resource('http://www.megginson.com/exp/ns/airports#icao'),NULL);
    	
    	//XXX perhaps all triples should be removed, since if we change the airport, other stuff e.g. name would be changed too? */
    	$this->removeFoundTriples($removeTriples1,$foafData);
    	$this->removeFoundTriples($removeTriples2,$foafData);
    	$this->removeFoundTriples($removeTriples3,$foafData);
    	$this->removeFoundTriples($removeTriples4,$foafData);
    	
    	/*add the iata and icao codes that have been passed in*/
    	if(property_exists($value->nearestAirport,'iataCode') && $value->nearestAirport->iataCode){  	
    		$foafData->getModel()->add(new Statement($airportBnode,new Resource('http://dig.csail.mit.edu/TAMI/2007/amord/air#iata'),new Literal($value->nearestAirport->iataCode)));
    		$foafData->getModel()->add(new Statement($airportBnode,new Resource('http://www.megginson.com/exp/ns/airports#iata'),new Literal($value->nearestAirport->iataCode)));
    	}
    	if(property_exists($value->nearestAirport,'icaoCode') && $value->nearestAirport->icaoCode){    		 	
    		$foafData->getModel()->add(new Statement($airportBnode,new Resource('http://dig.csail.mit.edu/TAMI/2007/amord/air#icao'),new Literal($value->nearestAirport->icaoCode)));
    		$foafData->getModel()->add(new Statement($airportBnode,new Resource('http://www.megginson.com/exp/ns/airports#icao'),new Literal($value->nearestAirport->icaoCode)));
    	}
    }
    
    private function removeFoundTriples($findResults,&$foafData){
    	if($findResults && !empty($findResults->triples)){
    		foreach($findResults->triples as $triple){
    			$foafData->getModel()->remove($triple);
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

    private function isCoordValid($date) {
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

    /*add nearest airport elements*/
    private function addNearestAirportElements($row,$privacy){
    	$newArray = array();

        if (isset($row['?icaoCodeAlt']) && $row['?icaoCodeAlt'] && $row['?icaoCodeAlt']->label) {
    		$newArray['icaoCode'] = $row['?icaoCodeAlt']->label;
    	}    
    	if (isset($row['?iataCodeAlt']) && $row['?iataCodeAlt'] && $row['?iataCodeAlt']->label) {
            $newArray['iataCode'] = $row['?iataCodeAlt']->label;
    	}
    	if (isset($row['?icaoCode']) && $row['?icaoCode'] && $row['?icaoCode']->label) {
    		$newArray['icaoCode'] = $row['?icaoCode']->label;
    	}    
    	if (isset($row['?iataCode']) && $row['?iataCode'] && $row['?iataCode']->label) {
            $newArray['iataCode'] = $row['?iataCode']->label;
    	}
    	if(!empty($newArray)){
    		 $this->data[$privacy]['nearestAirportFields']['nearestAirport'] = $newArray;
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
                	?icaoCode
                	?iataCode
                	?icaoCodeAlt
                	?iataCodeAlt
                
                WHERE{
	                	?z foaf:primaryTopic <".$primaryTopic.">
	                	?z foaf:primaryTopic ?primaryTopic
	                OPTIONAL{
	                	?primaryTopic contact:nearestAirport ?airport .
	                	?airport rdf:type wn:Airport
	                	OPTIONAL{
	                		?airport air:icao ?icaoCode
	                	}
	                	OPTIONAL{
	                		?airport air:iata ?iataCode
	                	}
	                	OPTIONAL{
	                		?airport airalt:icao ?icaoCodeAlt
	                	}
	                	OPTIONAL{
	                		?airport airalt:iata ?iataCodeAlt
	                	}
    				}
                }";
    	return $queryString;
    }

}
