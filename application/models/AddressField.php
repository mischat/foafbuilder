<?php
require_once 'Field.php';
require_once 'helpers/Utils.php';
/*FIXME: perhaps fields shouldn't do the whole sparql query thing in the constructor.*/

/*class to represent one item e.g. foafName or bioBirthday... not the same as one triple*/
class AddressField extends Field {
	
    /*predicateUri is only appropriate for simple ones (one triple only)*/
    public function AddressField($publicFoafData, $privateFoafData, $fullInstantiation = true) {
        	
            //XXX this is slightly innefficient
            $this->name = 'address';
            $this->label = 'Addresses';
            
            $this->data['public']['addressFields'] = array();
 	    $this->data['public']['addressFields']['office'] = array();
	    $this->data['public']['addressFields']['home'] = array();
            $this->data['public']['addressFields']['displayLabel'] =  $this->label;
            $this->data['public']['addressFields']['name'] =  $this->name;
            
            $this->data['private']['addressFields'] = array();
	    $this->data['private']['addressFields']['office'] = array();
	    $this->data['private']['addressFields']['home'] = array();
            $this->data['private']['addressFields']['displayLabel'] =  $this->label;
            $this->data['private']['addressFields']['name'] =  $this->name;
            
            /*don't sparql query the model etc if a full instantiation is not required*/
            if (!$fullInstantiation) {
	 	return;
            }
            /*load the public and private foaf data*/
            if($publicFoafData){
        	$this->doFullLoad($publicFoafData);
            }
            if($privateFoafData){
        	$this->doFullLoad($privateFoafData);
            }
    }
    
    private function doFullLoad(&$foafData){
    	
    	$queryString = $this->getQueryString($foafData->getPrimaryTopic());
        $results = $foafData->getModel()->SparqlQuery($queryString);		

        if($results && !empty($results)){
		if ($results[0]['?office']) {
			$this->addAddressElements($results[0],'office',$foafData->isPublic);
		}
		if ($results[0]['?home']) {
			$this->addAddressElements($results[0],'home',$foafData->isPublic);
		}
        }
    }

	
    /*saves the values created by the editor in value... as encoded in json.  Returns an array of bnodeids and random strings to be replaced by the view.*/
    public function saveToModel(&$foafData, $value) {
    	/* XXX really, removing the entire location and readding the entire location is not on.
    	 * It would be better to preserve triples where possible, although moving things between models is hard.*/

    	//remove all the address fields
	$this->removeAllExistingAddressTriples($foafData);

	foreach ($value->office as $office) {
        	$this->saveAddressFieldsToModel($foafData,$value->office,'office');
	}
	foreach ($value->home as $home) {
        	$this->saveAddressFieldsToModel($foafData,$value->home,'home');
	}

    }
    
    public function saveAddressFieldsToModel(&$foafData, $address, $type){
	//save all of them
	foreach($address as $bNodeName => $value){
		$homeBnode = Utils::GenerateUniqueBnode($foafData->getModel());
		/*add new triples*/
		$this->addNewAddressTriples($foafData,$homeBnode,$value,$type);	
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

    private function addNewAddressTriples(&$foafData,$homeBnode,$value,$type){
	$addressBnode = Utils::GenerateUniqueBnode($foafData->getModel());

	$somethingWasAdded = false;

	//XXX continue adding stuff here		
	if($type=='office'){
		$somethingWasAdded = $this->addOfficeAddressTriples($value,$foafData,$addressBnode);
	} else {
		$somethingWasAdded = $this->addHomeAddressTriples($value,$foafData,$addressBnode);
	}
		
	if(!$somethingWasAdded){
		return;
	}
	$addressStatement = new Statement($homeBnode, new Resource('http://www.w3.org/2000/10/swap/pim/contact#address'),$addressBnode);
	$homeLocationStatement = new Statement($homeBnode,new Resource('http://www.w3.org/1999/02/22-rdf-syntax-ns#type'),new Resource("http://www.w3.org/2000/10/swap/pim/contact#ContactLocation"));
	$homeStatement = new Statement(new Resource($foafData->getPrimaryTopic()),new Resource('http://www.w3.org/2000/10/swap/pim/contact#'.$type),$homeBnode);

	$foafData->getModel()->addWithoutDuplicates($addressStatement);
	$foafData->getModel()->addWithoutDuplicates($homeLocationStatement);
	$foafData->getModel()->addWithoutDuplicates($homeStatement);

	if(property_exists($value,'longitude') && $value->longitude && property_exists($value,'latitude') && $value->latitude){
		$longStatement = new Statement($homeBnode,new Resource('http://www.w3.org/2003/01/geo/wgs84_pos#long'),new Literal($value->longitude));
		$foafData->getModel()->add($longStatement);
		$latStatement = new Statement($homeBnode,new Resource('http://www.w3.org/2003/01/geo/wgs84_pos#lat'),new Literal($value->latitude));
		$foafData->getModel()->add($latStatement);
		$latLongStatement = new Statement($homeBnode,new Resource('http://www.w3.org/2003/01/geo/wgs84_pos#lat_long'),new Literal($value->latitude.",".$value->longitude));
		$foafData->getModel()->add($latLongStatement);
	}
   }
    
    private function addHomeAddressTriples($value,&$foafData,$addressBnode){
    		$somethingWasAdded = false;
    		if(property_exists($value,'homeCity') && $value->homeCity){
				$cityStatement = new Statement($addressBnode,new Resource('http://www.w3.org/2000/10/swap/pim/contact#city'),new Literal($value->homeCity));
				$foafData->getModel()->add($cityStatement);
				$somethingWasAdded = true;
		}
		if(property_exists($value,'homeCountry') && $value->homeCountry){
			$countryStatement = new Statement($addressBnode,new Resource('http://www.w3.org/2000/10/swap/pim/contact#country'),new Literal($value->homeCountry));
			$foafData->getModel()->add($countryStatement);
			$somethingWasAdded = true;
		}
		if(property_exists($value,'homeStreet') && $value->homeStreet){
			$streetStatement = new Statement($addressBnode,new Resource('http://www.w3.org/2000/10/swap/pim/contact#street'),new Literal($value->homeStreet));
			$foafData->getModel()->add($streetStatement);
			$somethingWasAdded = true;
		}
		if(property_exists($value,'homeStreet2') && $value->homeStreet2){
			$street2Statement = new Statement($addressBnode,new Resource('http://www.w3.org/2000/10/swap/pim/contact#street2'),new Literal($value->homeStreet2));
			$foafData->getModel()->add($street2Statement);
			$somethingWasAdded = true;
		}
		if(property_exists($value,'homeStreet3') && $value->homeStreet3){
			$street3Statement = new Statement($addressBnode,new Resource('http://www.w3.org/2000/10/swap/pim/contact#street3'),new Literal($value->homeStreet3));
			$foafData->getModel()->add($street3Statement);
			$somethingWasAdded = true;
		}
		if(property_exists($value,'homePostalCode') && $value->homePostalCode){
			$postalCodeStatement = new Statement($addressBnode,new Resource('http://www.w3.org/2000/10/swap/pim/contact#postalCode'),new Literal($value->homePostalCode));
			$foafData->getModel()->add($postalCodeStatement);
			$somethingWasAdded = true;
		}
		if(property_exists($value,'homeStateOrProvince') && $value->homeStateOrProvince){
			$stateOrProvinceStatement = new Statement($addressBnode,new Resource('http://www.w3.org/2000/10/swap/pim/contact#stateOrProvince'),new Literal($value->homeStateOrProvince));
			$foafData->getModel()->add($stateOrProvinceStatement);
			$somethingWasAdded = true;
	    }
	    return $somethingWasAdded;
    }
    
    private function addOfficeAddressTriples($value,&$foafData,$addressBnode){
    		$somethingWasAdded = false;
    		if(property_exists($value,'officeCity') && $value->officeCity){
			$cityStatement = new Statement($addressBnode,new Resource('http://www.w3.org/2000/10/swap/pim/contact#city'),new Literal($value->officeCity));
			$foafData->getModel()->addWithoutDuplicates($cityStatement);
			$somethingWasAdded = true;
		}
		if(property_exists($value,'officeCountry') && $value->officeCountry){
			$countryStatement = new Statement($addressBnode,new Resource('http://www.w3.org/2000/10/swap/pim/contact#country'),new Literal($value->officeCountry));
			$foafData->getModel()->add($countryStatement);
			$somethingWasAdded = true;
		}
		if(property_exists($value,'officeStreet') && $value->officeStreet){
			$streetStatement = new Statement($addressBnode,new Resource('http://www.w3.org/2000/10/swap/pim/contact#street'),new Literal($value->officeStreet));
			$foafData->getModel()->add($streetStatement);
			$somethingWasAdded = true;
		}
		if(property_exists($value,'officeStreet2') && $value->officeStreet2){
			$street2Statement = new Statement($addressBnode,new Resource('http://www.w3.org/2000/10/swap/pim/contact#street2'),new Literal($value->officeStreet2));
			$foafData->getModel()->add($street2Statement);
			$somethingWasAdded = true;
		}
		if(property_exists($value,'officeStreet3') && $value->officeStreet3){
			$street3Statement = new Statement($addressBnode,new Resource('http://www.w3.org/2000/10/swap/pim/contact#street3'),new Literal($value->officeStreet3));
			$foafData->getModel()->add($street3Statement);
			$somethingWasAdded = true;
		}
		if(property_exists($value,'officePostalCode') && $value->officePostalCode){
			$postalCodeStatement = new Statement($addressBnode,new Resource('http://www.w3.org/2000/10/swap/pim/contact#postalCode'),new Literal($value->officePostalCode));
			$foafData->getModel()->add($postalCodeStatement);
			$somethingWasAdded = true;
		}
		if(property_exists($value,'officeStateOrProvince') && $value->officeStateOrProvince){
			$stateOrProvinceStatement = new Statement($addressBnode,new Resource('http://www.w3.org/2000/10/swap/pim/contact#stateOrProvince'),new Literal($value->officeStateOrProvince));
			$foafData->getModel()->add($stateOrProvinceStatement);
			$somethingWasAdded = true;
		}
	return $somethingWasAdded;
    }
    
    private function removeAllExistingAddressTriples(&$foafData){
    	
    	//FIXME: we should really try to preserve as many triples as we can but moving them between models is hard
    	$primaryTopicRes = new Resource($foafData->getPrimaryTopic());
    	$contactHomeRes = new Resource('http://www.w3.org/2000/10/swap/pim/contact#home');
    	$contactOfficeRes = new Resource('http://www.w3.org/2000/10/swap/pim/contact#office');
    	
    	/*delete all the homes*/
    	$foundHomes = $foafData->getModel()->find($primaryTopicRes, $contactHomeRes, NULL);
		
    	if($foundHomes && property_exists($foundHomes,'triples') && $foundHomes->triples && !empty($foundHomes->triples)){
		foreach ($foundHomes->triples as $homeTriple) {	
			if(!property_exists($homeTriple->obj,'uri') || !$homeTriple->obj->uri){
				continue;		
			}
			$this->deleteUnderThisBnode($homeTriple->obj->uri,$foafData,'home');
			$foafData->getModel()->remove($homeTriple);
		}
	}
		
	/*delete all the offices*/
    	$foundOffices = $foafData->getModel()->find($primaryTopicRes, $contactOfficeRes, NULL);
    	
    	if($foundOffices && property_exists($foundOffices,'triples') && $foundOffices->triples && !empty($foundOffices->triples)){
		foreach($foundOffices->triples as $officeTriple){	
			if(!property_exists($officeTriple->obj,'uri') || !$officeTriple->obj->uri){
				continue;		
			}
			$this->deleteUnderThisBnode($officeTriple->obj->uri,$foafData,'office');
			$foafData->getModel()->remove($officeTriple);
		}
	}
   }
   	
   	//delete all triples that are 'hanging off' this bnode as well as the triple connecting it to the primarytopic
   	private function deleteUnderThisBnode($bNodeName,&$foafData,$prefix){
   		$foundStuff = $foafData->getModel()->find(new BlankNode($bNodeName),NULL,NULL);
		
		if(!$foundStuff || !property_exists($foundStuff,'triples')  
			|| !$foundStuff->triples || empty($foundStuff->triples)){
			return;
		}
		
		foreach($foundStuff->triples as $foundTriple){			
			if(($foundTriple->obj instanceof BlankNode) || ($foundTriple->obj instanceof Resource)){
				$foundMoreStuff = $foafData->getModel()->find($foundTriple->obj,NULL,NULL);
				if ($foundMoreStuff) {
					foreach($foundMoreStuff->triples as $foundMoreTriple){
						if (property_exists($foundMoreTriple->obj,'uri')) {
							$foundMoreMore = $foafData->getModel()->find(new BlankNode($foundMoreTriple->obj->uri),NULL,NULL);
							if ($foundMoreMore) {
								foreach($foundMoreMore->triples as $lame) {
									$foafData->getModel()->remove($lame);
								}
							}
						}
						$foafData->getModel()->remove($foundMoreTriple);
					}
				}
			}
			$foafData->getModel()->remove($foundTriple);
		}	
   		
		$mainStatement = new Statement(new Resource($foafData->getPrimaryTopic()),
									 	new Resource('http://www.w3.org/2000/10/swap/pim/contact#'.$prefix),
									 	new BlankNode($bNodeName));
		$foafData->getModel()->remove($mainStatement);
   	}
   	
    private function objectToArray($value) {
        $ret = array();
        foreach($value as $key => $value) {
            $ret[$key] = $value;
        }
        return $ret;
    }
    
   	/*takes a row from a sparql resultset and puts any home/office (determined by prefix) address information into data*/
    private function addAddressElements($row,$prefix,$isPublic){
		
    	if(!isset($row['?'.$prefix]) || !$row['?'.$prefix]){
    		return;
    	}
    	
    	/*We need to know which model the results have come from so we know whether to display them as private or public*/
    	$privacy;
    	if($isPublic){
    		$privacy = 'public';
    	} else {
    		$privacy = 'private';
    	}
    	
	$flag = false;
    	$newArray = array();

    	if (isset($row['?'.$prefix.'GeoLatLong']) && $this->isLatLongValid($row['?'.$prefix.'GeoLatLong'])) {
            $latLongArray = split(",",$row['?'.$prefix.'GeoLatLong']->label);
            $newArray['latitude']= $latLongArray[1];
            $newArray['longitude']= $latLongArray[0];    	
	    $flag = true;
        }
        if (isset($row['?'.$prefix.'GeoLat']) && $this->isCoordValid($row['?'.$prefix.'GeoLat']) &&
            isset($row['?'.$prefix.'GeoLong']) && $this->isCoordValid($row['?'.$prefix.'GeoLong'])) {
            $newArray['latitude'] = $row['?'.$prefix.'GeoLat']->label;
            $newArray['longitude'] = $row['?'.$prefix.'GeoLong']->label;
	    $flag = true;
        }
    	if (isset($row['?'.$prefix.'City']) && $row['?'.$prefix.'City'] && $row['?'.$prefix.'City']->label) {
            $newArray[$prefix.'City'] = $row['?'.$prefix.'City']->label;
	    if ($newArray[$prefix.'City'] != "") {
		$flag = true;
	    }
    	}
    	if (isset($row['?'.$prefix.'Country']) && $row['?'.$prefix.'Country'] && $row['?'.$prefix.'Country']->label) {
            $newArray[$prefix.'Country'] = $row['?'.$prefix.'Country']->label;
	    if ($newArray[$prefix.'Country']) {
 	        $flag = true;
    	    }
	}
    	if (isset($row['?'.$prefix.'Street']) && $row['?'.$prefix.'Street'] && $row['?'.$prefix.'Street']->label) {
            $newArray[$prefix.'Street'] = $row['?'.$prefix.'Street']->label;
	    if ($newArray[$prefix.'Street']) {
    	       $flag = true;
	    }
    	}
    	if (isset($row['?'.$prefix.'Street2']) && $row['?'.$prefix.'Street2'] && $row['?'.$prefix.'Street2']->label) {
            $newArray[$prefix.'Street2'] = $row['?'.$prefix.'Street2']->label;
	    if ($newArray[$prefix.'Street2']) {
		$flag = true;
	    }
    	}
    	if (isset($row['?'.$prefix.'Street3']) && $row['?'.$prefix.'Street3'] && $row['?'.$prefix.'Street3']->label) {
            $newArray[$prefix.'Street3'] = $row['?'.$prefix.'Street3']->label;
	    if ($newArray[$prefix.'Street3']) {
	        $flag = true;
	    }
    	}
    	if (isset($row['?'.$prefix.'PostalCode']) && $row['?'.$prefix.'PostalCode'] && $row['?'.$prefix.'PostalCode']->label) {
            $newArray[$prefix.'PostalCode'] = $row['?'.$prefix.'PostalCode']->label;
	    if ($newArray[$prefix.'PostalCode']) {
  	       $flag = true;
	    }
    	}
   	if (isset($row['?'.$prefix.'StateOrProvince']) && $row['?'.$prefix.'StateOrProvince'] && $row['?'.$prefix.'StateOrProvince']->label) {
            $newArray[$prefix.'StateOrProvince'] = $row['?'.$prefix.'StateOrProvince']->label;
	    if ($newArray[$prefix.'StateOrProvince']) {
		$flag = true;
	    }
    	}    
    	if(($newArray)){
    		 $this->data[$privacy]['addressFields'][$prefix.''][$row['?'.$prefix.'']->uri] = $newArray;    	
    	} else {
    		 $this->data[$privacy]['addressFields'][$prefix.''][$row['?'.$prefix.'']->uri] =  array();
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
	                	?office contact:address ?officeaddress .
	                	
	                	OPTIONAL{
	                		
	                		OPTIONAL{
	                			?office geo:lat_long ?officeGeoLatLong .
	                		}
	                		OPTIONAL{
	                			?office geo:lat ?officeGeoLat.
	                		}
	                		OPTIONAL{
	                			?office geo:long ?officeGeoLong .   
	                		}
	                		OPTIONAL{
	                			?officeaddress contact:city ?officeCity .
	                		}
	                		OPTIONAL{
	                			?officeaddress contact:country ?officeCountry .
	                		}
	                		OPTIONAL{
	                			?officeaddress contact:street ?officeStreet .
	                		}
	                		OPTIONAL{
	                			?officeaddress contact:street2 ?officeStreet2 .
	                		}
	                		OPTIONAL{
	                			?officeaddress contact:street3 ?officeStreet3 .
	                		}
	                		OPTIONAL{	
	                			?officeaddress contact:postalCode ?officePostalCode .
	                		}
	                		OPTIONAL{
	                			?officeaddress contact:stateOrProvince ?officeStateOrProvince .
	                		}
	                	}
	                }
                }";
    	return $queryString;
    }

}
