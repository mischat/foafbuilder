<?php
require_once 'Field.php';
require_once 'helpers/Utils.php';
/*FIXME: perhaps fields shouldn't do the whole sparql query thing in the constructor.*/

/*class to represent one item e.g. foafName or bioBirthday... not the same as one triple*/
class LocationField extends Field {
	
    /*predicateUri is only appropriate for simple ones (one triple only)*/
    public function LocationField($foafData) {
        /*TODO MISCHA dump test to check if empty */
    	//TODO: add foaf:nearestAirport stuff
        if ($foafData->getPrimaryTopic()) {
        	
            $queryString = $this->getQueryString($foafData->getPrimaryTopic());
            $results = $foafData->getModel()->SparqlQuery($queryString);		
	
            if(!$results){
           		return 0;
            }

            $this->data['locationFields'] = array();
			$this->data['locationFields']['basedNear'] = array();
			$this->data['locationFields']['office'] = array();
			$this->data['locationFields']['home'] = array();
			
            /*mangle the results so that they can be easily rendered*/
            foreach ($results as $row) {
            	$this->addBasedNearElements($row);
            	$this->addAddressElements($row,'office');
            	$this->addAddressElements($row,'home');
            }	
            
            //TODO: perhaps it is better to keep all the display stuff in the javascript?
            $this->data['locationFields']['displayLabel'] = 'Location';
            $this->data['locationFields']['name'] = 'location';
            $this->name = 'location';
            $this->label = 'Location';
        } else {
            return 0;
        }
    }

	
    /*saves the values created by the editor in value... as encoded in json.  Returns an array of bnodeids and random strings to be replaced by the view.*/
    public function saveToModel(&$foafData, $value) {
        echo ("save me");
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
    
   	/*takes a row from a sparql resultset and puts any based_near information into data*/
    private function addBasedNearElements($row){
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
            $this->data['locationFields']['basedNear'][$row['?location']->uri] = $newArray;
        }
    }
    
   	/*takes a row from a sparql resultset and puts any home/office (determined by prefix) address information into data*/
    private function addAddressElements($row,$prefix){
    	$newArray = array();
        
    	if (isset($row['?'.$prefix.'GeoLatLong']) && $this->isLatLongValid($row['?'.$prefix.'GeoLatLong'])) {
  
        	$latLongArray = split(",",$row['?'.$prefix.'GeoLatLong']->label);
            $newArray['latitude']= $latLongArray[1];
            $newArray['longitude']= $latLongArray[0];    	
        }
        if (isset($row['?'.$prefix.'GeoLat']) && $this->isCoordValid($row['?'.$prefix.'GeoLat']) &&
        	isset($row['?'.$prefix.'GeoLong']) && $this->isCoordValid($row['?'.$prefix.'GeoLong'])) {
            
        	$newArray['latitude'] = $row['?'.$prefix.'GeoLat']->label;
            $newArray['longitude'] = $row['?'.$prefix.'GeoLong']->label;
        }
    	if (isset($row['?'.$prefix.'City']) && $row['?'.$prefix.'City'] && $row['?'.$prefix.'City']->label) {
            $newArray[$prefix.'City'] = $row['?'.$prefix.'City']->label;
    	}
    	if (isset($row['?'.$prefix.'Country']) && $row['?'.$prefix.'Country'] && $row['?'.$prefix.'Country']->label) {
            $newArray[$prefix.'Country'] = $row['?'.$prefix.'Country']->label;
    	}
    	if (isset($row['?'.$prefix.'Street']) && $row['?'.$prefix.'Street'] && $row['?'.$prefix.'Street']->label) {
            $newArray[$prefix.'Street'] = $row['?'.$prefix.'Street']->label;
    	}
    	if (isset($row['?'.$prefix.'Street2']) && $row['?'.$prefix.'Street2'] && $row['?'.$prefix.'Street2']->label) {
            $newArray[$prefix.'Street2'] = $row['?'.$prefix.'Street2']->label;
    	}
    	if (isset($row['?'.$prefix.'Street3']) && $row['?'.$prefix.'Street3'] && $row['?'.$prefix.'Street3']->label) {
            $newArray[$prefix.'Street3'] = $row['?'.$prefix.'Street3']->label;
    	}
    	if (isset($row['?'.$prefix.'PostalCode']) && $row['?'.$prefix.'PostalCode'] && $row['?'.$prefix.'PostalCode']->label) {
            $newArray[$prefix.'PostalCode'] = $row['?'.$prefix.'PostalCode']->label;
    	}
   	 	if (isset($row['?'.$prefix.'StateOrProvince']) && $row['?'.$prefix.'StateOrProvince'] && $row['?'.$prefix.'StateOrProvince']->label) {
            $newArray[$prefix.'StateOrProvince'] = $row['?'.$prefix.'StateOrProvince']->label;
    	}    
    	
    	if(!empty($newArray)){
    		 $this->data['locationFields'][$prefix.''][$row['?'.$prefix.'']->uri] = $newArray;
    	}
    }
    
    private function getQueryString($primaryTopic){
    	
    	//TODO: add vacationhome and other locations as appropriate as well as more based_near detail and contact_nearest_airport
    	$queryString = "PREFIX contact: <http://www.w3.org/2000/10/swap/pim/contact#>
               	PREFIX foaf: <http://xmlns.com/foaf/0.1/>
                PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
                PREFIX bio: <http://purl.org/vocab/bio/0.1/>
                PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
                SELECT 
                	?geoLat 
                	?geoLong 
                	?geoLatLong 
                	?location 
                	
                	?home
                	?homeGeoLat
                	?homeGeoLong 
                	?homeGeoLatLong 
                	?homeCity
                	?homeCountry
                	?homeStreet
                	?homeStreet2
                	?homeStreet3
                	?homePostalCode
                	?homeStateOrProvince
                	
                	?office
                	?officeGeoLat
                	?officeGeoLong 
                	?officeGeoLatLong 
                	?officeCity
                	?officeCountry
                	?officeStreet
                	?officeStreet2
                	?officeStreet3
                	?officePostalCode
                	?officeStateOrProvince
                
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
        			OPTIONAL{
	                	?primaryTopic contact:home ?home .
	                	?home rdf:type contact:ContactLocation .
	                	
	                	OPTIONAL{
	                		?home contact:address ?address .
	                		
	                		OPTIONAL{
	                			?address geo:lat_long ?homeGeoLatLong .
	                		}
	                		OPTIONAL{
	                			?address geo:lat ?homeGeoLat.
	                		}
	                		OPTIONAL{
	                			?address geo:long ?homeGeoLong .   
	                		}
	                		OPTIONAL{
	                			?address contact:city ?homeCity .
	                		}
	                		OPTIONAL{
	                			?address contact:country ?homeCountry .
	                		}
	                		OPTIONAL{
	                			?address contact:street ?homeStreet .
	                		}
	                		OPTIONAL{
	                			?address contact:street2 ?homeStreet2 .
	                		}
	                		OPTIONAL{
	                			?address contact:street3 ?homeStreet3 .
	                		}
	                		OPTIONAL{	
	                			?address contact:postalCode ?homePostalCode .
	                		}
	                		OPTIONAL{
	                			?address contact:stateOrProvince ?homeStateOrProvince .
	                		}
	                	}
	                	OPTIONAL{
	                	?primaryTopic contact:office ?office .
	                	?office rdf:type contact:ContactLocation .
	                	
	                	OPTIONAL{
	                		?office contact:address ?address .
	                		
	                		OPTIONAL{
	                			?address geo:lat_long ?officeGeoLatLong .
	                		}
	                		OPTIONAL{
	                			?address geo:lat ?officeGeoLat.
	                		}
	                		OPTIONAL{
	                			?address geo:long ?officeGeoLong .   
	                		}
	                		OPTIONAL{
	                			?address contact:city ?officeCity .
	                		}
	                		OPTIONAL{
	                			?address contact:country ?officeCountry .
	                		}
	                		OPTIONAL{
	                			?address contact:street ?officeStreet .
	                		}
	                		OPTIONAL{
	                			?address contact:street2 ?officeStreet2 .
	                		}
	                		OPTIONAL{
	                			?address contact:street3 ?officeStreet3 .
	                		}
	                		OPTIONAL{	
	                			?address contact:postalCode ?officePostalCode .
	                		}
	                		OPTIONAL{
	                			?address contact:stateOrProvince ?officeStateOrProvince .
	                		}
	                	}
	                }
                }";
    	return $queryString;
    }

}
/* vi:set expandtab sts=4 sw=4: */
