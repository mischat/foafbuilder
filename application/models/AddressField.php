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
            	
	    	/*mangle the results so that they can be easily rendered*/
	    	foreach ($results as $row) {
	 				         	
	    	    $this->addAddressElements($row,'office',$foafData->isPublic);
	            $this->addAddressElements($row,'home',$foafData->isPublic);
	        }	
            
        }
    }

	
    /*saves the values created by the editor in value... as encoded in json.  Returns an array of bnodeids and random strings to be replaced by the view.*/
    public function saveToModel(&$foafData, $value) {
    	/* XXX really, removing the entire location and readding the entire location is not on.
    	 * It would be better to preserve triples where possible, although moving things between models is hard.*/
    	
    	//remove all the address fields
		$this->removeAllExistingAddressTriples($foafData);
		
		//save the new addresses
        $this->saveAddressFieldsToModel($foafData,$value->home,'home');
        $this->saveAddressFieldsToModel($foafData,$value->office,'office');
    }
    
	public function saveAddressFieldsToModel(&$foafData, $address, $type){
		
		//save all of them
		foreach($address as $bNodeName => $value){
			//XXX RAP doesn't seem to be very good at generating unique bnodes, so do some jiggery pokery
			$homeBnode = Utils::GenerateUniqueBnode($foafData->getModel());
				
			// create a home/office triple here and add it to the model.  also set the bnode to be created.
			$homeStatement = new Statement(new Resource($foafData->getPrimaryTopic()),new Resource('http://www.w3.org/2000/10/swap/pim/contact#'.$type),$homeBnode);	
			$homeLocationStatement = new Statement($homeBnode,new Resource('http://www.w3.org/1999/02/22-rdf-syntax-ns#type'),new Resource("http://www.w3.org/2000/10/swap/pim/contact#ContactLocation"));
	                	
			$foafData->getModel()->addWithoutDuplicates($homeStatement);
			$foafData->getModel()->addWithoutDuplicates($homeLocationStatement);				
						

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
    
    /*
    private function cleanOfficeAddressTriples(&$foafData,&$doNotCleanArray){	
    	
		//clean out all home/office addresses that we haven't edited
		$allOffices = $foafData->getModel()->find(new Resource($foafData->getPrimaryTopic()), new Resource('http://www.w3.org/2000/10/swap/pim/contact#office'), NULL);
		
		foreach($allOffices->triples as $triple){
			if(!$doNotCleanArray[$triple->obj->uri]){
				echo("Removing office address triple".$triple->obj->uri." isPublic ".$foafData->isPublic);
				$this->removeExistingAddressTriples($foafData,$triple->obj->uri,'office');
				$foafData->getModel()->remove($triple);
			}
		}
    }
    private function cleanHomeAddressTriples(&$foafData,&$doNotCleanArray){
		
		//clean out all home/office addresses that we haven't edited
    	$allHomes = $foafData->getModel()->find(new Resource($foafData->getPrimaryTopic()), new Resource('http://www.w3.org/2000/10/swap/pim/contact#home'), NULL);
		
    	foreach($allHomes->triples as $triple){
			if(!$doNotCleanArray[$triple->obj->uri]){
				echo("Removing home address triple".$triple->obj->uri." isPublic ".$foafData->isPublic);
				$this->removeExistingAddressTriples($foafData,$triple->obj->uri,'home');
				$foafData->getModel()->remove($triple);
			}
		}
    }*/
	
    private function addNewAddressTriples(&$foafData,$homeBnode,$value,$type){
    	
    	$addressBnode = Utils::GenerateUniqueBnode($foafData->getModel());
   // 	$addressStatement = new Statement($homeBnode, new Resource('http://www.w3.org/2000/10/swap/pim/contact#address'),$addressBnode);
//		$homeLocationStatement = new Statement($homeBnode,new Resource('http://www.w3.org/1999/02/22-rdf-syntax-ns#type'),new Resource("http://www.w3.org/2000/10/swap/pim/contact#ContactLocation"));
			
//		$foafData->getModel()->add($addressStatement);
//		$foafData->getModel()->add($homeLocationStatement);
			
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
    }
    
    private function addHomeAddressTriples($value,&$foafData,$addressBnode){
			
    		echo("\n"."Adding home triples"."\n");	
    	
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
    	error_log("\n"."Adding office triples"."\n");	
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
    
    private function removeAllExistingAddressTriples(&$foafData){
    	
    	//FIXME: we should really try to preserve as many triples as we can but moving them between models is hard
    	/*removes any existing home triples and sub triples which have an address associated with them*/
    	$query = "PREFIX foaf: <http://xmlns.com/foaf/0.1/>
    			  PREFIX contact: <http://www.w3.org/2000/10/swap/pim/contact#>
    		      PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
    		      SELECT ?office ?home WHERE {
    			  	?z foaf:primaryTopic <".$foafData->getPrimaryTopic()."> .
	                ?z foaf:primaryTopic ?primaryTopic .
    			  		?primaryTopic contact:office ?office .
	                	?office rdf:type contact:ContactLocation .
	                	?office contact:address ?address .
	    		  	UNION .
	                	?primaryTopic contact:home ?home .
	                	?home rdf:type contact:ContactLocation .
	                	?home contact:address ?address .
    			   }";
		
    	$primaryTopicRes = new Resource($foafData->getPrimaryTopic());
    	$contactHomeRes = new Resource('http://www.w3.org/2000/10/swap/pim/contact#home');
    	$contactOfficeRes = new Resource('http://www.w3.org/2000/10/swap/pim/contact#office');
    	
    	/*delete all the homes*/
    	$foundHomes = $foafData->getModel()->find($primaryTopicRes, $contactHomeRes, NULL);
		
    	if($foundHomes && property_exists($foundHomes,'triples')  
			&& $foundHomes->triples && !empty($foundHomes->triples)){
					
		foreach($foundHomes->triples as $homeTriple){	
			if(!property_exists($homeTriple->obj,'uri') || !$homeTriple->obj->uri){
				continue;		
			}
			$this->deleteUnderThisBnode($homeTriple->obj->uri,$foafData,'home');
		}
		$foafData->getModel()->remove(new Statement($primaryTopicRes, $contactHomeRes, new Resource($homeTriple->obj->uri)));
	}
		
	/*delete all the offices*/
    	$foundOffices = $foafData->getModel()->find($primaryTopicRes, $contactOfficeRes, NULL);
    	
    	if($foundOffices && property_exists($foundOffices,'triples')  
		&& $foundOffices->triples && !empty($foundOffices->triples)){
					
		foreach($foundOffices->triples as $officeTriple){	
			if(!property_exists($officeTriple->obj,'uri') || !$officeTriple->obj->uri){
				continue;		
			}
			$this->deleteUnderThisBnode($officeTriple->obj->uri,$foafData,'office');
		}
		$foafData->getModel()->remove(new Statement($primaryTopicRes, $contactOfficeRes, new Resource($officeTriple->obj->uri)));
	}
    	
   }
   	
   	//delete all triples that are 'hanging off' this bnode as well as the triple connecting it to the primarytopic
   	private function deleteUnderThisBnode($bNodeName,&$foafData,$prefix) {
   		$foundStuff = $foafData->getModel()->find(new BlankNode($bNodeName),NULL,NULL);
		
   		error_log("Deleting type: ".$prefix." bNodeName:".$bNodeName."\n");
   		
		if(!$foundStuff || !property_exists($foundStuff,'triples')  
			|| !$foundStuff->triples || empty($foundStuff->triples)){
			echo("return"."\n");
			return;
		}
		
		//remove all triples and subtriples.  Could have done this in fewer lines using the remove(x,null,null) functionality in RAP.
		foreach($foundStuff->triples as $foundTriple){			
			
			$foundMoreStuff = $foafData->getModel()->find($foundTriple->obj,NULL,NULL);
			if(!$foundMoreStuff || !property_exists($foundMoreStuff,'triples')  
				|| !$foundMoreStuff->triples || empty($foundMoreStuff->triples)){
				continue;
			}
			foreach($foundMoreStuff->triples as $foundMoreTriple){
				$foafData->getModel()->remove($foundMoreTriple);
			}
			$foafData->getModel()->remove($foundTriple);
		}	
   		
		$mainStatement = new Statement(new Resource($foafData->getPrimaryTopic()),
									 	new Resource('http://www.w3.org/2000/10/swap/pim/contact#'.$prefix),
									 	new BlankNode($bNodeName));
		$foafData->getModel()->remove($mainStatement);

		$mainStatement = new Statement(new BlankNode($bNodeName),
									 new Resource('http://www.w3.org/1999/02/22-rdf-syntax-ns#'),
									 new Resource('http://www.w3.org/2000/10/swap/pim/contact#'.$prefix));
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
    		 $this->data[$privacy]['addressFields'][$prefix.''][$row['?'.$prefix.'']->uri] = $newArray;    	
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
