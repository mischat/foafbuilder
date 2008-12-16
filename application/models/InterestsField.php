<?php
require_once 'Field.php';
require_once 'helpers/Utils.php';

class InterestsField extends Field {

    public function InterestsField($foafDataPublic,$foafDataPrivate,$fullInstantiation = true) {
     	
    	//XXX this is a bit inefficient
        $this->name = 'foafInterests';
        $this->label = 'Interests';
	
       	$this->data['public']['foafInterestsFields'] = array();
        $this->data['public']['foafInterestsFields']['values'] = array();
        $this->data['public']['foafInterestsFields']['displayLabel'] = $this->label;
        $this->data['public']['foafInterestsFields']['name'] = $this->name;
        
        $this->data['private']['foafInterestsFields'] = array();
        $this->data['private']['foafInterestsFields']['values'] = array();
        $this->data['private']['foafInterestsFields']['displayLabel'] = $this->label;
        $this->data['private']['foafInterestsFields']['name'] = $this->name;
    	
        /*don't sparql query the model etc if a full instantiation is not required*/
        if (!$fullInstantiation) {
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
	                SELECT ?foafInterests
	                WHERE{
	                <".$foafData->getPrimaryTopic()."> foaf:interest ?foafInterests .
	                };";
	
	    $results = $foafData->getModel()->SparqlQuery($queryString);		
	
	    //Check if results are not empty
	    if (empty($results)) {
		return;	
	    }
		
	    $privacy;		
	    //decide whether we put it in the public or private bit
	    if ($foafData->isPublic) {
		$privacy = 'public';		
	    } else {
		$privacy = 'private';			
	    }
		
	    /*mangle the results so that they can be easily rendered*/
	    foreach ($results as $row) {	
		if (!isset($row['?foafMbox'])) {
			continue;	
		}
			
	    $mBoxElem = $row['?foafMbox'];			
			
			/*add it to the data array which is returned to the application.  Try to load literals as well*/
			if(property_exists($mBoxElem,'label') && $this->isEmailAddressValid($mBoxElem->label)){
				
				array_push( $this->data[$privacy]["foafMboxFields"]['values'],$this->onLoadMangleEmailAddress($mBoxElem->label));
		        	        	
			} else if(property_exists($mBoxElem,'uri') && $this->isEmailAddressValid($mBoxElem->uri)){

				array_push($this->data[$privacy]["foafMboxFields"]['values'],$this->onLoadMangleEmailAddress($mBoxElem->uri));	
	
			}
	     }
    }
	
    /*saves the values created by the editor in value... as encoded in json. */
    public function saveToModel(&$foafData, $value) {

			require_once 'FieldNames.php';
			
			$sha1Sum_resource = new Resource('http://xmlns.com/foaf/0.1/mbox_sha1sum');
			$predicate_resource = new Resource('http://xmlns.com/foaf/0.1/mbox');
			$primary_topic_resource = new Resource($foafData->getPrimaryTopic());
			
			//find existing triples
			$foundModel = $foafData->getModel()->find($primary_topic_resource,$predicate_resource,NULL);
			//echo($primary_topic_resource->uri);
			//var_dump($foundModel);
			//var_dump($foafData->getModel());
			//echo("------------------------------HERE------------------------------");
			//remove existing triples
			foreach($foundModel->triples as $triple){
				//echo('removing mbox triples');
				$foafData->getModel()->remove($triple);
			}
			
			//add new triples
			$valueArray = $value->values;
			
			foreach($valueArray as $thisValue){
				$mangledValue = $this->onSaveMangleEmailAddress($thisValue);
				
				$resourceValue = new Resource($mangledValue);
				$literalValue = new Literal(sha1($sha1Sum_resource->uri));
				
				$mboxStatement = new Statement($primary_topic_resource,$predicate_resource,$resourceValue);	
				$mbox_Sha1Statement = new Statement($primary_topic_resource,$sha1Sum_resource,$literalValue);	
				
				$foafData->getModel()->addWithoutDuplicates($mboxStatement);
				$foafData->getModel()->addWithoutDuplicates($mbox_Sha1Statement);
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
