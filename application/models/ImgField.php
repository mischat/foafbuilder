<?php
require_once 'Field.php';
require_once 'helpers/Utils.php';
/*FIXME: perhaps fields shouldn't do the whole sparql query thing in the constructor.*/
//TODO: this is very very similar to depiction field, possibly we should share the code rather than repeating it

/*class to represent one item e.g. foafName or bioBirthday... not the same as one triple*/
class ImgField extends Field {
	
    /*predicateUri is only appropriate for simple ones (one triple only)*/
    public function ImgField($foafData,$fullInstantiation = true) {
    	
     	$this->name = 'foafImg';
        $this->label = 'Secondary Images';
        $this->data['foafImgFields'] = array();
        $this->data['foafImgFields']['displayLabel'] =  $this->label;
        $this->data['foafImgFields']['name'] = $this->name;
        $this->data['foafImgFields']['images'] = array();
            
        /*don't sparql query the model etc if a full instantiation is not required*/
        if (!$fullInstantiation || !$foafData || !$foafData->getPrimaryTopic()) {
			return;
        }
            $queryString = 
                "PREFIX dc: <http://purl.org/dc/elements/1.1/>
                PREFIX foaf: <http://xmlns.com/foaf/0.1/>
                PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
                PREFIX bio: <http://purl.org/vocab/bio/0.1/>
                PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
                SELECT ?foafImg ?dcTitle ?dcDescription
                WHERE{
	                <".$foafData->getPrimaryTopic()."> foaf:img ?foafImg .
		            OPTIONAL{
		            	?foafImg dc:title ?dcTitle .
		            } .
		            OPTIONAL{
		                ?foafImg dc:description ?dcDescription .
		            }	
                };";
	             //   ?z foaf:primaryTopic <".$foafData->getPrimaryTopic()."> .
	             //   ?z foaf:primaryTopic ?primaryTopic .

            $results = $foafData->getModel()->SparqlQuery($queryString);		
            
            /*mangle the results so that they can be easily rendered*/
            if($results && isset($results[0]) && isset($results[0]) && $results[0]){
	            foreach ($results as $row) {	
	                if (isset($row['?foafImg']) && $this->isImageUrlValid($row['?foafImg'])) {
	                	$thisImage = array();
	                	$thisImage['uri'] = $row['?foafImg']->uri;            
	                	
	                	if(isset($row['?dcTitle']) && $row['?dcTitle'] && $row['?dcTitle']->label){
	                		$thisImage['title'] = $row['?dcTitle']->label;
	                	} 
	                	if(isset($row['?dcDescription']) && $row['?dcDescription'] && $row['?dcDescription']->label){
	                		$thisImage['description'] = $row['?dcDescription']->label;
	                	}
	                	array_push($this->data['foafImgFields']['images'],$thisImage);
	                }
	            }
        	}
                   
        
    }
	
    /*saves the values created by the editor in value... as encoded in json.  Returns an array of bnodeids and random strings to be replaced by the view.*/
    public function saveToModel(&$foafData, $value) {
		
		if(!property_exists($value,'images')){
			return;
		}
		
		/*array to keep any images that we should not remove in*/
		$doNotCleanArray = array();
		
		if(isset($value->images[0])){
			foreach($value->images as $image){
				
				/*check if the image already exists in the model*/
				$foundModel = $foafData->getModel()->find(new Resource($foafData->getPrimaryTopic()), new Resource('http://xmlns.com/foaf/0.1/img'),new Resource($image->uri));
				
				if($foundModel && property_exists($foundModel, 'triples') && isset($foundModel->triples[0])){
					/*img triple already exists in model*/
					foreach($foundModel->triples as $triple){
	
						/*find titles and remove them*/
						$foundTitles = $foafData->getModel()->find($triple->obj,new Resource('http://purl.org/dc/elements/1.1/title'),NULL);
						if($foundTitles && property_exists($foundTitles, 'triples') && isset($foundTitles->triples[0])){
							foreach($foundTitles->triples as $title){
								$foafData->getModel()->remove($title);
							}
						}
	
						/*find descriptions and remove them*/
						$foundDescriptions = $foafData->getModel()->find($triple->obj,new Resource('http://purl.org/dc/elements/1.1/description'),NULL);
						if($foundDescriptions && property_exists($foundDescriptions, 'triples') && isset($foundDescriptions->triples[0])){
							foreach($foundDescriptions->triples as $description){
								$foafData->getModel()->remove($description);
							}
						}
					}
				} else {
					/*img triple doesn't already exist in model so we need to create another one and add it*/	
					$imgTriple = new Statement(new Resource($foafData->getPrimaryTopic()), new Resource('http://xmlns.com/foaf/0.1/img'),new Resource($image->uri));
					$foafData->getModel()->add($imgTriple);
				}
			
			
				//so that we don't clean out the images that we want to keep
				$doNotCleanArray[$image->uri] = $image->uri;
			}//endfor
		}//endif
		
		$this->cleanTriples($foafData,$doNotCleanArray);
    }
    
	
	private function cleanTriples(&$foafData,$doNotCleanArray){
		/*clean any triples that we haven't edited*/
		$allImages = $foafData->getModel()->find(new Resource($foafData->getPrimaryTopic()),new Resource('http://xmlns.com/foaf/0.1/img'),NULL);
			
		if(property_exists($allImages,'triples') && $allImages->triples && isset($allImages->triples[0])){
			foreach($allImages->triples as $toCleanTriple){
				
				/*check that each triple isn't in the array that we earmarked for keeping*/
				if(property_exists($toCleanTriple->obj,'uri') && !isset($doNotCleanArray[$toCleanTriple->obj->uri])){
				
					/*We need to delete all the triples (e.g. title, description) associated with this image*/
					$foundToRemove = $foafData->getModel()->find(new Resource($toCleanTriple->obj->uri),NULL,NULL);
					if(property_exists($foundToRemove,'triples') && $foundToRemove->triples && isset($foundToRemove->triples[0]) && $foundToRemove->triples[0]){
					
						foreach($foundToRemove->triples as $tripleToRemove){		
							$foafData->getModel()->remove($tripleToRemove);
						}
					
					}
					/*remove this triple*/
					$foafData->getModel()->remove(new Statement(new Resource($foafData->getPrimaryTopic()), new Resource('http://xmlns.com/foaf/0.1/img'), new Resource($toCleanTriple->obj->uri)));
				}
			}
		}
	}

    private function isImageUrlValid($url) {
        //FIXME: something should go here to make sure the string makes sense.
        if (!property_exists($url,'uri') || $url->uri == null || $url->uri == '') {
        //TODO MISCHA ... add in the image filter thing
        //if(preg_match('/^https?:\/\/(?:[a-z\-]+\.)+[a-z]{2,6}(?:\/[^\/#?]+)+\.(?:jpg|JPG|GIF|gif|PNG|png|JPEG|jpeg)$/',$value)){
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
}
/* vi:set expandtab sts=4 sw=4: */
