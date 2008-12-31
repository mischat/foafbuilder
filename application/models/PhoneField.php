<?php
require_once 'Field.php';
require_once 'helpers/Utils.php';

//XXX this is just like MboxField
class PhoneField extends Field {
	
    public function PhoneField($foafDataPublic,$foafDataPrivate,$fullInstantiation = true) {
     	
    	//XXX this is a bit inefficient
        $this->name = 'foafPhone';
        $this->label = 'Phone';
	
       	$this->data['public']['foafPhoneFields'] = array();
        $this->data['public']['foafPhoneFields']['values'] = array();
        $this->data['public']['foafPhoneFields']['displayLabel'] = $this->label;
        $this->data['public']['foafPhoneFields']['name'] = $this->name;
        
        $this->data['private']['foafPhoneFields'] = array();
        $this->data['private']['foafPhoneFields']['values'] = array();
        $this->data['private']['foafPhoneFields']['displayLabel'] = $this->label;
        $this->data['private']['foafPhoneFields']['name'] = $this->name;
    	
        /*don't sparql query the model etc if a full instantiation is not required*/
        if (!$fullInstantiation){
			return;
        }
		if($foafDataPublic){
			$this->doFullLoad($foafDataPublic);
		} 
		if($foafDataPrivate){
			$this->doFullLoad($foafDataPrivate);
		}
    }
    
    private function doFullLoad(&$foafData){
    	
	    $queryString = 
	                "PREFIX foaf: <http://xmlns.com/foaf/0.1/>
	                PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
	                PREFIX bio: <http://purl.org/vocab/bio/0.1/>
	                PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
	                SELECT ?foafPhone
	                WHERE{
	                <".$foafData->getPrimaryTopic()."> foaf:phone ?foafPhone .   
	                };";
	
	    $results = $foafData->getModel()->SparqlQuery($queryString);		
	
	    //Check if results are not empty
		if (empty($results)) {
			return;	
		}
		
		$privacy;		
		//decide whether we put it in the public or private bit
		if($foafData->isPublic){
			$privacy = 'public';		
		} else {
			$privacy = 'private';			
		}
		
		/*mangle the results so that they can be easily rendered*/
		foreach ($results as $row) {	
		        	    
			if (!isset($row['?foafPhone'])) {
				continue;	
			}
			
			$phoneElem = $row['?foafPhone'];			
			
			/*add it to the data array which is returned to the application.  Try to load literals as well*/
			if(property_exists($phoneElem,'label') && $this->isPhoneNoValid($phoneElem->label)){
				
				array_push($this->data[$privacy]["foafPhoneFields"]['values'],$this->onLoadManglePhoneNo($phoneElem->label));
		        	        	
			} else if(property_exists($phoneElem,'uri') && $this->isPhoneNoValid($phoneElem->uri)){

				array_push($this->data[$privacy]["foafPhoneFields"]['values'],$this->onLoadManglePhoneNo($phoneElem->uri));	
	
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
				if ($thisValue != "") {
					$mangledValue = $this->onSaveManglePhoneNo($thisValue);
					$resourceValue = new Resource($mangledValue);
					$phoneStatement = new Statement($primary_topic_resource,$predicate_resource,$resourceValue);	
					$foafData->getModel()->addWithoutDuplicates($phoneStatement);
				}
			}
    }
    /*mangles the email address for display purposes*/
	private function onLoadManglePhoneNo($value){
		//TODO: more mangling will probably at some point be required here
		$ret = str_replace('tel:','',$value);
		return $ret;
	}
	
    /*mangles the email address  for saving purposes*/
	private function onSaveManglePhoneNo($value){
		//TODO: more mangling will at some point probably be required here
		$ret = $value;
		
		if(substr($value,0,4) != 'tel:'){
			$ret = 'tel:'.$value;
		}
		
		return $ret;
	}
	
    private function isPhoneNoValid($value) {
        //TODO: add some proper validation here
    	if($value){
        	return true;
        } else {
        	return false;
        }
    }

}

