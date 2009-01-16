<?php
require_once 'Field.php';
require_once 'helpers/Utils.php';

class MboxShaField extends Field {

    public function MboxShaField($foafDataPublic,$foafDataPrivate,$fullInstantiation = true) {
     	
    	//XXX this is a bit inefficient
        $this->name = 'foafMboxSha';
        $this->label = 'Mbox Sha1sums';
	
       	$this->data['public']['foafMboxShaFields'] = array();
        $this->data['public']['foafMboxShaFields']['values'] = array();
        $this->data['public']['foafMboxShaFields']['displayLabel'] = $this->label;
        $this->data['public']['foafMboxShaFields']['name'] = $this->name;
        
        $this->data['private']['foafMboxShaFields'] = array();
        $this->data['private']['foafMboxShaFields']['values'] = array();
        $this->data['private']['foafMboxShaFields']['displayLabel'] = $this->label;
        $this->data['private']['foafMboxShaFields']['name'] = $this->name;
    	
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
		SELECT ?foafMboxSha
		WHERE{
			<".$foafData->getPrimaryTopic()."> foaf:mbox_sha1sum ?foafMboxSha
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
		if (!isset($row['?foafMboxSha'])) {
			continue;	
		}
		$mBoxElem = $row['?foafMboxSha'];			
		/*add it to the data array which is returned to the application.  Try to load literals as well*/
		if (property_exists($mBoxElem,'label') && $this->isShaValid($mBoxElem->label)) {
			array_push( $this->data[$privacy]["foafMboxShaFields"]['values'],$mBoxElem->label);
		} 
	}
    }
	
    /*saves the values created by the editor in value... as encoded in json. */
    public function saveToModel(&$foafData, $value) {
		require_once 'FieldNames.php';
		
		$sha1Sum_resource = new Resource('http://xmlns.com/foaf/0.1/mbox_sha1sum');
		$primary_topic_resource = new Resource($foafData->getPrimaryTopic());

		//First to remove the sha1sums, dont remove if no corresponding mbox exists
		$foundModel = $foafData->getModel()->find($primary_topic_resource,$sha1Sum_resource,NULL);

		//remove existing triples
		foreach($foundModel->triples as $triple){
			$foafData->getModel()->remove($triple);
			error_log("I am removing a Sha1sum now .....".$triple->obj->label);
		}
		
		//add new triples
		$valueArray = $value->values;
		
		foreach($valueArray as $thisValue){
			if(!$thisValue || $thisValue == '' || trim($thisValue) == '' || !trim($thisValue)){
				continue;			
			}

			if ($this->isShaValid($thisValue)) {
				$literalValue = new Literal($thisValue);
				
				$mbox_Sha1Statement = new Statement($primary_topic_resource,$sha1Sum_resource,$literalValue);	
				
				$foafData->getModel()->addWithoutDuplicates($mbox_Sha1Statement);
			error_log("I am adding a Sha1sum now .....$thisValue");
			}
		}
    }
	
    /*Checks if Sha1 is valid*/
    private function isShaValid($value) {
	if (preg_match('/^[a-z0-9]{40}$/',$value)) {
		return true;
		error_log("yay ... valid sha1sum ....");
	} 
	return false;
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

}

