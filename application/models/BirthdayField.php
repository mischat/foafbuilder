<?php
require_once 'Field.php';
require_once 'helpers/Utils.php';
/*FIXME: perhaps fields shouldn't do the whole sparql query thing in the constructor.*/

/*class to represent one item e.g. foafName or bioBirthday... not the same as one triple*/
class BirthdayField extends Field{
	
	/*predicateUri is only appropriate for simple ones (one triple only)*/
	public function BirthdayField($foafData){

		$queryString = 
		"PREFIX foaf: <http://xmlns.com/foaf/0.1/>
         PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
         PREFIX bio: <http://purl.org/vocab/bio/0.1/>
         PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
         SELECT ?bioBirthday ?foafBirthday ?foafDateOfBirth
         	 WHERE{
         	 		?z foaf:primaryTopic <".$foafData->getPrimaryTopic().">
         	 		?z foaf:primaryTopic ?primaryTopic
				OPTIONAL{
					?primaryTopic foaf:birthday ?foafBirthday .
				} .
				OPTIONAL{
					?primaryTopic foaf:dateOfBirth ?foafDateOfBirth .
				}
				OPTIONAL{
					?primaryTopic bio:event ?e .
        			?e rdf:type bio:Birth .
        			?e bio:date ?bioBirthday .
				} 
			};";
         
		$results = $foafData->getModel()->SparqlQuery($queryString);		
		
		$this->data['birthdayFields'] = array();
		
		/*mangle the results so that they can be easily rendered*/
		foreach($results as $row){	
			if(isset($row['?foafDateOfBirth']) && $this->isLongDateValid($row['?foafDateOfBirth'])){
				$birthdayArray = split("-",$row['?foafDateOfBirth']->label);
				$this->data['birthdayFields']['day']= $birthdayArray[2];
				$this->data['birthdayFields']['month']= $birthdayArray[1];
				$this->data['birthdayFields']['year']= $birthdayArray[0];
			}
			if(isset($row['?foafBirthday']) && $this->isShortDateValid($row['?foafBirthday'])){
				$birthdayArray = split("-",$row['?foafBirthday']->label);
				$this->data['birthdayFields']['day']= $birthdayArray[1];
				$this->data['birthdayFields']['month']= $birthdayArray[0];
			}
			if(isset($row['?bioBirthday']) && $this->isLongDateValid($row['?bioBirthday'])){
				$birthdayArray = split("-",$row['?bioBirthday']->label);
				$this->data['birthdayFields']['day']= $birthdayArray[2];
				$this->data['birthdayFields']['month']= $birthdayArray[1];
				$this->data['birthdayFields']['year']= $birthdayArray[0];
			}
		}	
        
        //TODO: perhaps it is better to keep all the display stuff in the javascript?
        $this->data['birthdayFields']['displayLabel'] = 'Birthday';
        $this->data['birthdayFields']['name'] = 'birthday';
        $this->name = 'birthday';
		$this->label = 'Birthday';
	}

	public function getPredicateUri(){
		return $this->predicateUri;
	}
	public function setPredicateUri($predicateUri){
		$this->predicateUri = $predicateUri;
	}
	
	/*saves the values created by the editor in value... as encoded in json.  Returns an array of bnodeids and random strings to be replaced by the view.*/
	public function saveToModel(&$foafData, $value){

		/*find existing triples for foafBirthday and foafDateOfBirth*/
		$foundModel1 = $foafData->getModel(NULL,"http://xmlns.com/foaf/0.1/birthday",NULL);
		$foundModel2 = $foafData->getModel(NULL,"http://xmlns.com/foaf/0.1/dateOfBirth",NULL);
		
		/*remove any existing triples*/
		foreach($foundModel1->triples as $triple){
			$foafData->getModel->remove($triple);
		}
		foreach($foundModel2->triples as $triple){
			$foafData->getModel->remove($triple);
		}
		
		/*re-add them (if they exist)*/
		if($value['month'] && $value['month'] != '' && $value['day'] && $value['day'] != ''){
			/*add FoafBirthday element*/
			$newFoafBirthday = new Statement(new Resource($foafData->getPrimaryTopic()),"http://xmlns.com/foaf/0.1/birthday",$value['month']."-".$value['day']);
			$foafData->add($newFoafBirthday);
			
			if($value['year'] && $value['year'] != ''){
				/*add foafDateOfBirth element*/
				$newFoafDateOfBirth= new Statement(new Resource($foafData->getPrimaryTopic()),"http://xmlns.com/foaf/0.1/birthday",$value['month']."-".$value['day']."-".$value['year']);
				$foafData->add($newFoafBirthday);
				
				/*if bio style birthday exists already then edit it but if not, don't*/
				$foafData->getModel();
				if($this->bioBirthdayExists()){
					$foafData->getModel();
					$bioBirthdayExists = new Statement(NULL, new Resource(), new Resource());
				}
			}
		}
		
		
		//FIXME: add save functionality here
	}

	private function isLongDateValid($date){
		
		//FIXME: something should go here to make sure the string makes sense.
		
		if($date == null || $date == ''){
			return false;
		} else {
			return true;
		}
	}
	
	private function isShortDateValid($date){
		
		//FIXME: something should go here to make sure the string makes sense.
		if($date == null || $date == ''){
			return false;
		} else {
			return true;
		}
	}
	
	private function bioBirthdayExists(){
		
	}
}
?>