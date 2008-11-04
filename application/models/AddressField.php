<?php
require_once 'Field.php';
require_once 'helpers/Utils.php';
/*FIXME: perhaps fields shouldn't do the whole sparql query thing in the constructor.*/

/*class to represent one item e.g. foafName or bioBirthday... not the same as one triple*/
class AddressField extends Field {
	
    /*predicateUri is only appropriate for simple ones (one triple only)*/
    public function AddressField($foafData) {
        /*TODO MISCHA dump test to check if empty */
    	//TODO: add foaf:nearestAirport stuff
        if ($foafData->getPrimaryTopic()) {
        	
            $queryString = $this->getQueryString($foafData->getPrimaryTopic());
            $results = $foafData->getModel()->SparqlQuery($queryString);		

            //var_dump($results);
            
          	$this->data['addressFields'] = array();
			$this->data['addressFields']['office'] = array();
			$this->data['addressFields']['home'] = array();
				
            if($results && !empty($results)){
            	
	            /*mangle the results so that they can be easily rendered*/
	            foreach ($results as $row) {
	       
	            	$this->addAddressElements($row,'office');
	            	$this->addAddressElements($row,'home');
	            }	
            
        	}

            $this->data['addressFields']['displayLabel'] = 'Addresses';
            $this->data['addressFields']['name'] = 'address';
            $this->name = 'address';
            $this->label = 'Addresses';
    	}
    }

	
    /*saves the values created by the editor in value... as encoded in json.  Returns an array of bnodeids and random strings to be replaced by the view.*/
    public function saveToModel(&$foafData, $value) {
        $this->saveAddressFieldsToModel($foafData,$value->home,'home');
        $this->saveAddressFieldsToModel($foafData,$value->office,'office');
        	//TODO: do airport and address ones
    }
    
	public function saveAddressFieldsToModel(&$foafData, $address, $type){
    	/*array to keep track of bnode ids versus random strings generated by the UI*/
		$randomStringToBnodeArray = $foafData->getRandomStringToBnodeArray();
		$doNotCleanArray = array();
		
		foreach($address as $bNodeName => $value){

			$this->removeExistingAddressTriples($foafData,$bNodeName,$type);

	 		/*check whether we've already created this bnode or not*/
			if(strlen($bNodeName) == 50){		
					echo("IN IF");
		    	if(isset($randomStringToBnodeArray[$bNodeName])){	
		    		
					$homeBnode = new BlankNode($randomStringToBnodeArray[$bNodeName]);
					
				} else {
						
					//XXX RAP doesn't seem to be very good at generating unique bnodes, so do some jiggery pokery
					$homeBnode = Utils::GenerateUniqueBnode($foafData->getModel());
				
					// create a home/office triple here and add it to the model.  also set the bnode to be created.
					$homeStatement = new Statement(new Resource($foafData->getPrimaryTopic()),new Resource('http://www.w3.org/2000/10/swap/pim/contact#'.$type),$homeBnode);	
					$homeLocationStatement = new Statement($homeBnode,new Resource('http://www.w3.org/1999/02/22-rdf-syntax-ns#type'),new Resource("http://www.w3.org/2000/10/swap/pim/contact#ContactLocation"));
				
					$foafData->getModel()->add($homeStatement);
					$foafData->getModel()->add($homeLocationStatement);
								
					/*so that we can keep track of what's going on*/
					$randomStringToBnodeArray[$bNodeName] = $homeBnode->uri;					
				}
			} else {				
					$homeBnode = new BlankNode($bNodeName);
			}

			/*add new triples*/
			$this->addNewAddressTriples($foafData,$homeBnode,$value,$doNotCleanArray,$type);			
		}	
		if($type == 'home'){
			$this->cleanHomeAddressTriples($foafData,$doNotCleanArray,$type);
		} else {			
			$this->cleanOfficeAddressTriples($foafData,$doNotCleanArray,$type);
		}
		/*so that we can keep track*/
		$foafData->setRandomStringToBnodeArray($randomStringToBnodeArray);
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
    if (!$$coord) {
            return false;
        } else {
            return true;
        }
    }
    
    private function cleanOfficeAddressTriples(&$foafData,&$doNotCleanArray){	
    	
		/*clean out all home/office addresses that we haven't edited*/
		$allOffices = $foafData->getModel()->find(new Resource($foafData->getPrimaryTopic()), new Resource('http://www.w3.org/2000/10/swap/pim/contact#office'), NULL);
		
		foreach($allOffices->triples as $triple){
			if(!$doNotCleanArray[$triple->obj->uri]){
				//echo("Removing office address triples");
				$this->removeExistingAddressTriples($foafData,$triple->obj->uri,'office');
				$foafData->getModel()->remove($triple);
			}
		}
    }
    private function cleanHomeAddressTriples(&$foafData,&$doNotCleanArray){
		
		/*clean out all home/office addresses that we haven't edited*/
    	$allHomes = $foafData->getModel()->find(new Resource($foafData->getPrimaryTopic()), new Resource('http://www.w3.org/2000/10/swap/pim/contact#home'), NULL);
		
    	foreach($allHomes->triples as $triple){
			if(!$doNotCleanArray[$triple->obj->uri]){
				echo("Removing home address triples");
				$this->removeExistingAddressTriples($foafData,$triple->obj->uri,'home');
				$foafData->getModel()->remove($triple);
			}
		}
    }
	
    private function addNewAddressTriples(&$foafData,$homeBnode,$value,&$doNotCleanArray,$type){
    	
    	$addressBnode = Utils::GenerateUniqueBnode($foafData->getModel());
    	$addressStatement = new Statement($homeBnode, new Resource('http://www.w3.org/2000/10/swap/pim/contact#address'),$addressBnode);
		$homeLocationStatement = new Statement($homeBnode,new Resource('http://www.w3.org/1999/02/22-rdf-syntax-ns#type'),new Resource("http://www.w3.org/2000/10/swap/pim/contact#ContactLocation"));
			
		
		$foafData->getModel()->add($addressStatement);
		$foafData->getModel()->add($homeLocationStatement);
			
		//XXX continue adding stuff here		
		if($type=='office'){
			$this->addOfficeAddressTriples($value,$foafData,$addressBnode);
		} else {
			$this->addHomeAddressTriples($value,$foafData,$addressBnode);
		}
			
		if(property_exists($value,'latitude') && $value->latitude && property_exists($value,'latitude') && $value->latitude){
			$longStatement = new Statement($homeBnode,new Resource('http://www.w3.org/2003/01/geo/wgs84_pos#long'),new Literal($value->longitude));
			$foafData->getModel()->add($longStatement);

			$latStatement = new Statement($homeBnode,new Resource('http://www.w3.org/2003/01/geo/wgs84_pos#lat'),new Literal($value->latitude));
			$foafData->getModel()->add($latStatement);
				
			$latLongStatement = new Statement($homeBnode,new Resource('http://www.w3.org/2003/01/geo/wgs84_pos#lat_long'),new Literal($value->latitude.",".$value->longitude));
			$foafData->getModel()->add($latLongStatement);
		}
		//we don't want to remove this one
		$doNotCleanArray[$homeBnode->uri] = $homeBnode->uri;
    }
    
    private function addHomeAddressTriples($value,&$foafData,$addressBnode){

    		if(property_exists($value,'homeCity') && $value->homeCity){
				$cityStatement = new Statement($addressBnode,new Resource('http://www.w3.org/2000/10/swap/pim/contact#city'),new Literal($value->homeCity));
				$foafData->getModel()->add($cityStatement);
			}
			if(property_exists($value,'homeCountry') && $value->homeCountry){
				$countryStatement = new Statement($addressBnode,new Resource('http://www.w3.org/2000/10/swap/pim/contact#country'),new Literal($value->homeCountry));
				$foafData->getModel()->add($countryStatement);
			}
			if(property_exists($value,'homeStreet') && $value->homeStreet){
				$streetStatement = new Statement($addressBnode,new Resource('http://www.w3.org/2000/10/swap/pim/contact#street'),new Literal($value->homeStreet));
				$foafData->getModel()->add($streetStatement);
			}
			if(property_exists($value,'homeStreet2') && $value->homeStreet2){
				$street2Statement = new Statement($addressBnode,new Resource('http://www.w3.org/2000/10/swap/pim/contact#street2'),new Literal($value->homeStreet2));
				$foafData->getModel()->add($street2Statement);
			}
			if(property_exists($value,'homeStreet3') && $value->homeStreet3){
				$street3Statement = new Statement($addressBnode,new Resource('http://www.w3.org/2000/10/swap/pim/contact#street3'),new Literal($value->homeStreet3));
				$foafData->getModel()->add($street3Statement);
			}
			if(property_exists($value,'homePostalCode') && $value->homePostalCode){
				$postalCodeStatement = new Statement($addressBnode,new Resource('http://www.w3.org/2000/10/swap/pim/contact#postalCode'),new Literal($value->homePostalCode));
				$foafData->getModel()->add($postalCodeStatement);
			}
			if(property_exists($value,'homeStateOrProvince') && $value->homeStateOrProvince){
				$stateOrProvinceStatement = new Statement($addressBnode,new Resource('http://www.w3.org/2000/10/swap/pim/contact#stateOrProvince'),new Literal($value->homeStateOrProvince));
				$foafData->getModel()->add($stateOrProvinceStatement);
		    }
    }
    
    private function addOfficeAddressTriples($value,&$foafData,$addressBnode){

    		if(property_exists($value,'officeCity') && $value->officeCity){
				$cityStatement = new Statement($addressBnode,new Resource('http://www.w3.org/2000/10/swap/pim/contact#city'),new Literal($value->officeCity));
				$foafData->getModel()->add($cityStatement);
			}
			if(property_exists($value,'officeCountry') && $value->officeCountry){
				$countryStatement = new Statement($addressBnode,new Resource('http://www.w3.org/2000/10/swap/pim/contact#country'),new Literal($value->officeCountry));
				$foafData->getModel()->add($countryStatement);
			}
			if(property_exists($value,'officeStreet') && $value->officeStreet){
				$streetStatement = new Statement($addressBnode,new Resource('http://www.w3.org/2000/10/swap/pim/contact#street'),new Literal($value->officeStreet));
				$foafData->getModel()->add($streetStatement);
			}
			if(property_exists($value,'officeStreet2') && $value->officeStreet2){
				$street2Statement = new Statement($addressBnode,new Resource('http://www.w3.org/2000/10/swap/pim/contact#street2'),new Literal($value->officeStreet2));
				$foafData->getModel()->add($street2Statement);
			}
			if(property_exists($value,'officeStreet3') && $value->officeStreet3){
				$street3Statement = new Statement($addressBnode,new Resource('http://www.w3.org/2000/10/swap/pim/contact#street3'),new Literal($value->officeStreet3));
				$foafData->getModel()->add($street3Statement);
			}
			if(property_exists($value,'officePostalCode') && $value->officePostalCode){
				$postalCodeStatement = new Statement($addressBnode,new Resource('http://www.w3.org/2000/10/swap/pim/contact#postalCode'),new Literal($value->officePostalCode));
				$foafData->getModel()->add($postalCodeStatement);
			}
			if(property_exists($value,'officeStateOrProvince') && $value->officeStateOrProvince){
				$stateOrProvinceStatement = new Statement($addressBnode,new Resource('http://www.w3.org/2000/10/swap/pim/contact#stateOrProvince'),new Literal($value->officeStateOrProvince));
				$foafData->getModel()->add($stateOrProvinceStatement);
		    }
    }
    
    /*if the flag is 0, preserve existing triples, if not, delete them all (for the case where the address has been removed*/
    private function removeExistingAddressTriples(&$foafData,$bNodeName){
    		//XXX FIXME : it is to do with bnodes replacing other ones that have been removed
			/*find the triples associated with this address*/		
			$foundStuffToRemove = array();
			$addressStuffToRemove = $foafData->getModel()->find(new BlankNode($bNodeName), new Resource('http://www.w3.org/2000/10/swap/pim/contact#address'),NULL);		
			
			foreach($addressStuffToRemove->triples as $addressTriple){					
				$addressBnode = $addressTriple->obj;
				//we used to try to only delete stuff that we were going to replace in the address
				/*
				array_push($foundStuffToRemove,$foafData->getModel()->find($addressBnode, new Resource('http://www.w3.org/2000/10/swap/pim/contact#city'), NULL));
				array_push($foundStuffToRemove,$foafData->getModel()->find($addressBnode, new Resource('http://www.w3.org/2000/10/swap/pim/contact#country'), NULL));
				array_push($foundStuffToRemove,$foafData->getModel()->find($addressBnode, new Resource('http://www.w3.org/2000/10/swap/pim/contact#street'), NULL));
				array_push($foundStuffToRemove,$foafData->getModel()->find($addressBnode, new Resource('http://www.w3.org/2000/10/swap/pim/contact#street2'), NULL));
				array_push($foundStuffToRemove,$foafData->getModel()->find($addressBnode, new Resource('http://www.w3.org/2000/10/swap/pim/contact#street3'), NULL));
				array_push($foundStuffToRemove,$foafData->getModel()->find($addressBnode, new Resource('http://www.w3.org/2000/10/swap/pim/contact#stateOrProvince'), NULL));
				array_push($foundStuffToRemove,$foafData->getModel()->find($addressBnode, new Resource('http://www.w3.org/2000/10/swap/pim/contact#postalCode'), NULL));*/
					
				//just remove everything under this address and the address itself XXX perhaps we should try to preserve things here if we can?
				array_push($foundStuffToRemove,$foafData->getModel()->find($addressBnode, NULL, NULL));
				$foafData->getModel()->remove($addressTriple);
			}			
			
			// try to only delete stuff that we were going to replace
			array_push($foundStuffToRemove,$foafData->getModel()->find(new BlankNode($bNodeName), new Resource('http://www.w3.org/2000/10/swap/pim/contact#address'),NULL));		
			array_push($foundStuffToRemove,$foafData->getModel()->find(new BlankNode($bNodeName), new Resource('http://www.w3.org/2003/01/geo/wgs84_pos#lat_long'), NULL));	
			array_push($foundStuffToRemove,$foafData->getModel()->find(new BlankNode($bNodeName), new Resource('http://www.w3.org/2003/01/geo/wgs84_pos#lat'), NULL));	
			array_push($foundStuffToRemove,$foafData->getModel()->find(new BlankNode($bNodeName), new Resource('http://www.w3.org/2003/01/geo/wgs84_pos#long'), NULL));	
		
			/*remove triples associated with this address*/
			foreach($foundStuffToRemove as $found_model){
				if(isset($found_model->triples[0])){
					foreach($found_model->triples as $triple){
						$foafData->getModel()->remove($triple);
					}
				}
			}	
    }
    private function objectToArray($value) {
        $ret = array();
        foreach($value as $key => $value) {
            $ret[$key] = $value;
        }
        return $ret;
    }
    
   	/*takes a row from a sparql resultset and puts any home/office (determined by prefix) address information into data*/
    private function addAddressElements($row,$prefix){
		
    	if(!isset($row['?'.$prefix]) || !$row['?'.$prefix]){
    		return;
    	}
    	
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
    	if (isset($row['?'.$prefix.'StateOrProvince']) && $row['?'.$prefix.'StateOrProvince'] && $row['?'.$prefix.'StateOrProvince']->label) {
            $newArray[$prefix.'StateOrProvince'] = $row['?'.$prefix.'StateOrProvince']->label;

    	}    
    	
    	if(!empty($newArray)){
    		 $this->data['addressFields'][$prefix.''][$row['?'.$prefix.'']->uri] = $newArray;    	
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
	                	?primaryTopic contact:home ?home .
	                	?home rdf:type contact:ContactLocation .
	                	?home contact:address ?address .
	                	
	                	OPTIONAL{
	                		
	                		OPTIONAL{
	                			?address geo:lat_long ?homeGeoLatLong .
	                		}
	                		OPTIONAL{
	                			?home geo:lat ?homeGeoLat.
	                		}
	                		OPTIONAL{
	                			?home geo:long ?homeGeoLong .   
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
	                	?office contact:address ?address .
	                	
	                	OPTIONAL{
	                		
	                		OPTIONAL{
	                			?address geo:lat_long ?officeGeoLatLong .
	                		}
	                		OPTIONAL{
	                			?office geo:lat ?officeGeoLat.
	                		}
	                		OPTIONAL{
	                			?office geo:long ?officeGeoLong .   
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
