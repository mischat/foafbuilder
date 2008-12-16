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
	                PREFIX dc:  <http://purl.org/dc/elements/1.1/>
	                SELECT ?foafInterests ?foafInterestTitles
	                WHERE{
			   <".$foafData->getPrimaryTopic()."> foaf:interest ?foafInterests 
			   OPTIONAL {
				?foafInterests dc:title ?foafInterestTitles
			   }
			}";

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
	    	$interestsArray = array();
		if (!isset($row['?foafInterests'])) {
			continue;	
		}
	    	if ($row['?foafInterests']->uri) {
			$interestsArray['uri'] = $row['?foafInterests']->uri;
		}
		if (isset($row['?foafInterestTitles']) && $row['?foafInterestTitles']->label) {
			$interestsArray['title'] = $row['?foafInterestTitles']->label;
		}
		array_push($this->data[$privacy]["foafInterestsFields"]['values'],$interestsArray);
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

}
