<?php
require_once 'Field.php';
require_once 'helpers/Utils.php';
/*FIXME: perhaps fields shouldn't do the whole sparql query thing in the constructor.*/

/*class to represent one item e.g. foafName or bioBirthday... not the same as one triple*/
class PhoneField extends Field {
	
    /*predicateUri is only appropriate for simple ones (one triple only)*/
    public function PhoneField($foafData, $fullInstantiation = true) {
    	 
    	$this->data['foafPhoneFields'] = array();
        $this->data['foafPhoneFields']['values'] = array();       
        $this->name = 'foafPhone';
        $this->label = 'Phones';         
        $this->data['foafPhoneFields']['displayLabel'] = $this->label;
        $this->data['foafPhoneFields']['name'] =  $this->name;
        
        if ($foafData->getPrimaryTopic() && $fullInstantiation) {
            $queryString = 
                "PREFIX foaf: <http://xmlns.com/foaf/0.1/>
                PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
                PREFIX bio: <http://purl.org/vocab/bio/0.1/>
                PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
                SELECT ?foafPhone
                WHERE{
                ?z foaf:primaryTopic <".$foafData->getPrimaryTopic().">
                ?z foaf:primaryTopic ?primaryTopic
                ?primaryTopic foaf:phone ?foafPhone .
                 
                };";

            $results = $foafData->getModel()->SparqlQuery($queryString);		
	

               //Check if results are not empty
               if (!(empty($results))) {
                /*mangle the results so that they can be easily rendered*/
                	foreach ($results as $row) {	
                    	if (isset($row['?foafPhone']) && property_exists($row['?foafPhone'],'label') && $this->isPhoneNumberValid($row['?foafPhone']->label)) {
                        	array_push($this->data['foafPhoneFields']['values'],$this->onLoadManglePhoneNumber($row['?foafPhone']->label));
                    	}
                		if (isset($row['?foafPhone']) && property_exists($row['?foafPhone'],'uri') && $this->isPhoneNumberValid($row['?foafPhone']->uri)) {
                        	array_push($this->data['foafPhoneFields']['values'],$this->onLoadManglePhoneNumber($row['?foafPhone']->uri));
                    	}
                	}	
               } 
        }
    }
	
    /*saves the values created by the editor in value... as encoded in json. */
    public function saveToModel(&$foafData, $value) {

			require_once 'FieldNames.php';
			
			$predicate_resource = new Resource('http://xmlns.com/foaf/0.1/phone');
			$primary_topic_resource = new Resource($foafData->getPrimaryTopic());
			
			//find existing triples
			$foundModel = $foafData->getModel()->find($primary_topic_resource,$predicate_resource,NULL);
			
			//remove existing triples
			foreach($foundModel->triples as $triple){
				$foafData->getModel()->remove($triple);
			}
			
			//add new triples
			$valueArray = $value->values;
			foreach($valueArray as $thisValue){
				$mangledValue = $this->onSaveManglePhoneNumber($thisValue);
				
				$literalValue = new Literal($mangledValue);
		
				$new_statement = new Statement($primary_topic_resource,$predicate_resource,$literalValue);	
				$foafData->getModel()->add($new_statement);
			}

    }
    /*mangles the phone number for display purposes*/
	private function onLoadManglePhoneNumber($value){
		//TODO: more mangling will probably at some point be required here
		$ret = str_replace('tel:','',$value);
		return $ret;
	}
	
    /*mangles the phone number for saving purposes*/
	private function onSaveManglePhoneNumber($value){
		//TODO: more mangling will at some point probably be required here
		$ret = $value;
		
		if(substr($value,0,4) != 'tel:'){
			$ret = 'tel:'.$value;
		}
		
		return $ret;
	}
	
    private function isPhoneNumberValid($value) {
        if($value){
        	return true;
        } else {
        	return false;
        }
    }

}

