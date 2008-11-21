<?php
require_once 'Field.php';
require_once 'helpers/Utils.php';
/*FIXME: perhaps fields shouldn't do the whole sparql query thing in the constructor.*/
//TODO: this is very very similar to depiction field, possibly we should share the code rather than repeating it

/*class to represent one item e.g. foafName or bioBirthday... not the same as one triple*/
class DepictionField extends Field {
	
    /*predicateUri is only appropriate for simple ones (one triple only)*/
    public function DepictionField($foafDataPublic,$foafDataPrivate,$fullInstantiation = true) {
    	
     	$this->name = 'foafDepiction';
        $this->label = 'Main Images';
        $this->data['public']['foafDepictionFields'] = array();
        $this->data['public']['foafDepictionFields']['displayLabel'] =  $this->label;
        $this->data['public']['foafDepictionFields']['name'] = $this->name;
        $this->data['public']['foafDepictionFields']['images'] = array();
        
        $this->data['private']['foafDepictionFields'] = array();
        $this->data['private']['foafDepictionFields']['displayLabel'] =  $this->label;
        $this->data['private']['foafDepictionFields']['name'] = $this->name;
        $this->data['private']['foafDepictionFields']['images'] = array();
       	
        
        /*don't sparql query the model etc if a full instantiation is not required*/
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
    	
    	$queryString = 
        	"PREFIX dc: <http://purl.org/dc/elements/1.1/>
             PREFIX foaf: <http://xmlns.com/foaf/0.1/>
             PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
             PREFIX bio: <http://purl.org/vocab/bio/0.1/>
             PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
             SELECT ?foafDepiction ?dcTitle ?dcDescription
             	WHERE{
	            	<".$foafData->getPrimaryTopic()."> foaf:depiction ?foafDepiction .
		            OPTIONAL{
		            	?foafDepiction dc:title ?dcTitle .
		            } .
		            OPTIONAL{
		                ?foafDepiction dc:description ?dcDescription .
		            }	
                };";

            $results = $foafData->getModel()->SparqlQuery($queryString);		
            
            /*mangle the results so that they can be easily rendered*/
            if(!$results || !isset($results[0]) || !isset($results[0]) || !$results[0]){
            	return;
            }
	        
            foreach ($results as $row) {	
	        	if (isset($row['?foafDepiction']) && $this->isImageUrlValid($row['?foafDepiction'])) {
	            	$thisImage = array();
	                $thisImage['uri'] = $row['?foafDepiction']->uri;            
	                	
	                if(isset($row['?dcTitle']) && $row['?dcTitle'] && $row['?dcTitle']->label){
	                	$thisImage['title'] = $row['?dcTitle']->label;
	                } 
	                if(isset($row['?dcDescription']) && $row['?dcDescription'] && $row['?dcDescription']->label){
	                	$thisImage['description'] = $row['?dcDescription']->label;
	                }
	                array_push($this->data[$privacy]['foafDepictionFields']['images'],$thisImage);
	            }
	        }
    }
	
    /*saves the values created by the editor in value... as encoded in json.  Returns an array of bnodeids and random strings to be replaced by the view.*/
    public function saveToModel(&$foafData, $value) {
		
		if(!$value || !$foafData || !property_exists($value,'images')){
			return;
		}
		
		//XXX clean all the triples out of the model.  
    	//TODO: we should really be trying to preserve triples but moving them between models is hard
    	$this->cleanTriples($foafData);
		
		if(!isset($value->images[0])){
				return;
		}
			
		foreach($value->images as $image){
				
			/*check if the image already exists in the model*/
			$foundModel = $foafData->getModel()->find(new Resource($foafData->getPrimaryTopic()), new Resource('http://xmlns.com/foaf/0.1/depiction'),new Resource($image->uri));
				
			if($foundModel && property_exists($foundModel, 'triples') && isset($foundModel->triples[0])){
	
				/*depiction triple already exists in model*/
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
				/*depiction triple doesn't already exist in model so we need to create another one and add it*/	
				$depictionTriple = new Statement(new Resource($foafData->getPrimaryTopic()), new Resource('http://xmlns.com/foaf/0.1/depiction'),new Resource($image->uri));
				$foafData->getModel()->add($depictionTriple);
			}
		}
    }
    
	
	private function cleanTriples(&$foafData){
		/*clean any triples that we haven't edited*/
		$allImages = $foafData->getModel()->find(new Resource($foafData->getPrimaryTopic()),new Resource('http://xmlns.com/foaf/0.1/depiction'),NULL);
			
		if(property_exists($allImages,'triples') && $allImages->triples && isset($allImages->triples[0])){
			foreach($allImages->triples as $toCleanTriple){
				
				/*check that each triple isn't in the array that we earmarked for keeping*/
				if(property_exists($toCleanTriple->obj,'uri')){
				
					/*We need to delete all the triples (e.g. title, description) associated with this image*/
					$foundToRemove = $foafData->getModel()->find(new Resource($toCleanTriple->obj->uri),NULL,NULL);
					if(property_exists($foundToRemove,'triples') && $foundToRemove->triples && isset($foundToRemove->triples[0]) && $foundToRemove->triples[0]){
					
						foreach($foundToRemove->triples as $tripleToRemove){		
							$foafData->getModel()->remove($tripleToRemove);
						}
					
					}
					/*remove this triple*/
					$foafData->getModel()->remove(new Statement(new Resource($foafData->getPrimaryTopic()), new Resource('http://xmlns.com/foaf/0.1/depiction'), new Resource($toCleanTriple->obj->uri)));
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
