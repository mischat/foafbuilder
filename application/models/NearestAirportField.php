<?php
require_once 'Field.php';
require_once 'helpers/Utils.php';
/*FIXME: perhaps fields shouldn't do the whole sparql query thing in the constructor.*/

/*class to represent one item e.g. foafName or bioBirthday... not the same as one triple*/
class NearestAirportField extends Field {
	
    /*predicateUri is only appropriate for simple ones (one triple only)*/
    public function NearestAirportField($foafData) {
        /*TODO MISCHA dump test to check if empty */
    	//TODO: add foaf:nearestAirport save stuff
        if ($foafData->getPrimaryTopic()) {
        	
            $queryString = $this->getQueryString($foafData->getPrimaryTopic());
            $results = $foafData->getModel()->SparqlQuery($queryString);		

          	$this->data['nearestAirportFields'] = array();
	        $this->data['nearestAirportFields']['nearestAirport'] = array();

            if($results && !empty($results)){
            	
	            /*mangle the results so that they can be easily rendered*/
	            foreach ($results as $row) {
	            	$this->addNearestAirportElements($row);

	            }	
            
        	}

            $this->data['nearestAirportFields']['displayLabel'] = 'My Nearest Airport';
            $this->data['nearestAirportFields']['name'] = 'nearestAirport';
            $this->name = 'nearestAirport';
            $this->label = 'My Nearest Airport';
    	}
    }

	
    /*saves the values created by the editor in value... as encoded in json.  Returns an array of bnodeids and random strings to be replaced by the view.*/
    public function saveToModel(&$foafData, $value) {
    	//TODO: implement this
    }
  
    private function isLatLongValid($date) {
        //FIXME: something should go here to make sure the string makes sense.
        if ($date == null || $date == '') {
            return false;
        } else {
            return true;
        }
    }

    private function isCoordValid($date) {
    //FIXME: something should go here to make sure the string makes sense.
    if ($date == null || $date == '') {
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
    private function addNearestAirportElements($row){
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
    		 $this->data['nearestAirportFields']['nearestAirport'] = $newArray;
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
