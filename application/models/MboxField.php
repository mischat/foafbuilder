<?php
require_once 'Field.php';
require_once 'helpers/Utils.php';
/*FIXME: perhaps fields shouldn't do the whole sparql query thing in the constructor.*/

/*class to represent one item e.g. foafName or bioBirthday... not the same as one triple*/
class MboxField extends Field {
	
    /*predicateUri is only appropriate for simple ones (one triple only)*/
    public function MboxField($foafData,$fullInstantiation = true) {
     	
    	$this->data['foafMboxFields'] = array();
        $this->data['foafMboxFields']['values'] = array();
        $this->name = 'foafMbox';
        $this->label = 'Email';
        $this->data['foafMboxFields']['displayLabel'] = $this->label;
        $this->data['foafMboxFields']['name'] = $this->name;
    	
        /*don't sparql query the model etc if a full instantiation is not required*/
        if (!$fullInstantiation || !$foafData || !$foafData->getPrimaryTopic()) {
			return;
        }
        
        $queryString = 
                "PREFIX foaf: <http://xmlns.com/foaf/0.1/>
                PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
                PREFIX bio: <http://purl.org/vocab/bio/0.1/>
                PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
                SELECT ?foafMbox
                WHERE{
                ?z foaf:primaryTopic <".$foafData->getPrimaryTopic().">
                ?z foaf:primaryTopic ?primaryTopic
                ?primaryTopic foaf:mbox ?foafMbox .
                 
                };";

        $results = $foafData->getModel()->SparqlQuery($queryString);		

        //Check if results are not empty
	    if (!(empty($results))) {
	    /*mangle the results so that they can be easily rendered*/
	        foreach ($results as $row) {	
	                if (isset($row['?foafMbox'])) {
	                	if(property_exists($row['?foafMbox'],'label') && $this->isEmailAddressValid($row['?foafMbox']->label)){
	                   		array_push($this->data['foafMboxFields']['values'],$this->onLoadMangleEmailAddress($row['?foafMbox']->label));
	                	} else if(property_exists($row['?foafMbox'],'uri') && $this->isEmailAddressValid($row['?foafMbox']->uri)){
	                		array_push($this->data['foafMboxFields']['values'],$this->onLoadMangleEmailAddress($row['?foafMbox']->uri));	
	                	}
	                }
	             }
	         
        }
    }
	
    /*saves the values created by the editor in value... as encoded in json. */
    public function saveToModel(&$foafData, $value) {

			require_once 'FieldNames.php';
			
			$predicate_resource = new Resource('http://xmlns.com/foaf/0.1/mbox');
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
				$mangledValue = $this->onSaveMangleEmailAddress($thisValue);
				
				$literalValue = new Literal($mangledValue);
		
				$new_statement = new Statement($primary_topic_resource,$predicate_resource,$literalValue);	
				$foafData->getModel()->add($new_statement);
			}

    }
    /*mangles the email address for display purposes*/
	private function onLoadMangleEmailAddress($value){
		//TODO: more mangling will probably at some point be required here
		$ret = str_replace('mailto:','',$value);
		return $ret;
	}
	
    /*mangles the email address  for saving purposes*/
	private function onSaveMangleEmailAddress($value){
		//TODO: more mangling will at some point probably be required here
		$ret = $value;
		
		if(substr($value,0,4) != 'mailto:'){
			$ret = 'mailto:'.$value;
		}
		
		return $ret;
	}
	
    private function isEmailAddressValid($value) {
        //TODO: add some proper validation here
    	if($value){
        	return true;
        } else {
        	return false;
        }
    }

}

