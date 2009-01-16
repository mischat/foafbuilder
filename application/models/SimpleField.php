<?php
require_once 'Field.php';
require_once 'helpers/double_metaphone.php';

/*class to represent one item e.g. foafName or bioBirthday... not the same as one triple*/
class SimpleField extends Field{
	
	private $predicateUri;
	
	/*predicateUri is only appropriate for simple ones (one triple only)*/
	//TODO: should label be here
	public function SimpleField($name, $label, $predicateUri, $foafDataPublic, $foafDataPrivate, $type, $fullInstantiation = true){
		
		//XXX: a bit inefficient
		$this->data = array();
		$this->data['private']['fields'] = array();
		$this->data['private']['fields'][$name] = array();
		$this->data['private']['fields'][$name]['displayLabel'] = $label;
		$this->data['private']['fields'][$name]['values'] = array();
		$this->data['private']['fields'][$name]['name'] = $name;
		$this->data['public']['fields'] = array();
		$this->data['public']['fields'][$name] = array();
		$this->data['public']['fields'][$name]['displayLabel'] = $label;
		$this->data['public']['fields'][$name]['values'] = array();
		$this->data['public']['fields'][$name]['name'] = $name;
		
        $this->name = $name;
		$this->label = $label;
		$this->type = $type;
		$this->predicateUri = $predicateUri;	
		
		/*only query the model if a full instantiation is required*/
		if($fullInstantiation){
			if($foafDataPublic){
				$this->doFullLoad($foafDataPublic);
			} 
			if($foafDataPrivate){
				$this->doFullLoad($foafDataPrivate);
			}
		}
		
	}
	
	/*does a full load of whichever model is in the foafData object that is passed in*/
	private function doFullLoad($foafData){
		
		if(!$foafData || !$foafData->getPrimaryTopic() ){
			return;
		}
		
		$queryString = "SELECT ?".$this->name." WHERE {<".$foafData->getPrimaryTopic()."> <".$this->predicateUri."> ?".$this->name." }";
		$results = $foafData->getModel()->SparqlQuery($queryString);		

		if ($this->predicateUri == 'http://xmlns.com/foaf/0.1/givenname') {
			$foundModel = $foafData->getModel()->find(new Resource($foafData->getPrimaryTopic()),new Resource($this->predicateUri), NULL);
			//remove existing triples
			foreach($foundModel->triples as $triple){
				$phones = $phones = double_metaphone($triple->obj->label);
				foreach ($phones as $phone) {
					if ($phone != "") {
						$phone_statement = new Statement(new Resource ($foafData->getPrimaryTopic()),new Resource('http://qdos.com/schema#metaphone'), new Literal($phone));
						$foafData->getModel()->addWithOutDuplicates($phone_statement);
					}
				}
			}
		}
		if ($this->predicateUri == 'http://xmlns.com/foaf/0.1/family_name') {
			$foundModel = $foafData->getModel()->find(new Resource($foafData->getPrimaryTopic()),new Resource($this->predicateUri), NULL);
			//remove existing triples
			foreach($foundModel->triples as $triple){
				$phones = $phones = double_metaphone($triple->obj->label);
				foreach ($phones as $phone) {
					if ($phone != "") {
						$phone_statement = new Statement(new Resource ($foafData->getPrimaryTopic()),new Resource('http://qdos.com/schema#metaphone'), new Literal($phone));
						$foafData->getModel()->addWithOutDuplicates($phone_statement);
					}
				}
			}
		}
		if ($this->predicateUri == 'http://xmlns.com/foaf/0.1/surname') {
			$foundModel = $foafData->getModel()->find(new Resource($foafData->getPrimaryTopic()),new Resource($this->predicateUri), NULL);
			//remove existing triples
			foreach($foundModel->triples as $triple){
				$phones = $phones = double_metaphone($triple->obj->label);
				foreach ($phones as $phone) {
					if ($phone != "") {
						$phone_statement = new Statement(new Resource ($foafData->getPrimaryTopic()),new Resource('http://qdos.com/schema#metaphone'), new Literal($phone));
						$foafData->getModel()->addWithOutDuplicates($phone_statement);
					}
				}
			}
		}
		if ($this->predicateUri == 'http://xmlns.com/foaf/0.1/name') {
			$foundModel = $foafData->getModel()->find(new Resource($foafData->getPrimaryTopic()),new Resource($this->predicateUri), NULL);
			//remove existing triples
			foreach($foundModel->triples as $triple){
				$tempname = $triple->obj->label;
				$tempname = preg_replace('/-/',' ',$tempname);
				$names = split(' ',$tempname);
				foreach ($names as $name) {
					$phones = $phones = double_metaphone($name);
					foreach ($phones as $phone) {
						if ($phone != "") {
							$phone_statement = new Statement(new Resource ($foafData->getPrimaryTopic()),new Resource('http://qdos.com/schema#metaphone'), new Literal($phone));
							$foafData->getModel()->addWithOutDuplicates($phone_statement);
						}
					}
				}
			}
		}
		/*make sure that we populate the public or the private bit*/
		$privacy;
		if($foafData->isPublic){
			$privacy = 'public';
		} else {
			$privacy = 'private';
		}
		
		/*mangle the results so that they can be easily rendered*/
		if(!isset($results[0])){
			return;
		}
		foreach($results as $row){
			foreach($row as $key => $value){	
				$key = str_replace('?','',$key);
								
				if(property_exists($value,'label')){
			       	array_push($this->data[$privacy]['fields'][$this->name]['values'],$value->label);
			    } else if(property_exists($value,'uri')){
			    	array_push($this->data[$privacy]['fields'][$this->name]['values'],$value->uri);
			    }
			     
			}
	    }
	}

	/*saves the appropriate triples in the model at the appropriate index and replace them with $value*/
	public function saveToModel(&$foafData, &$value){

		require_once 'SimpleField.php';
		require_once 'FieldNames.php';
		
		
		$model = $foafData->getModel();		
		$predicate_resource = new Resource($this->predicateUri);
		$primary_topic_resource = new Resource($foafData->getPrimaryTopic());
		
		//find existing triples
		$foundModel = $model->find($primary_topic_resource,$predicate_resource,NULL);
		
		//remove existing triples
		foreach($foundModel->triples as $triple){
			$model->remove($triple);
		}

		$foundmetaphones = $model->find($primary_topic_resource,new Resource('http://qdos.com/schema#metaphone'),NULL);
		//remove existing triples
		foreach($foundmetaphones->triples as $metaphonetriple){
			$model->remove($metaphonetriple);
		}
		
		//add new triples
		$valueArray = get_object_vars($value);
		foreach($valueArray[$this->name]->values as $thisValue){
			$value_res_or_literal = null;
			if($this->type == 'literal' && $thisValue != ""){
				$value_res_or_literal = new Literal($thisValue);
			} else if($this->type == 'resource' && $thisValue != 'http://' && $thisValue != 'mailto:'){
				$value_res_or_literal = new Resource($thisValue);
			} 		
			if ($value_res_or_literal != null) {
				$new_statement = new Statement($primary_topic_resource,$predicate_resource,$value_res_or_literal);	
				if ($this->predicateUri == 'http://xmlns.com/foaf/0.1/givenname') {
					$phones = $phones = double_metaphone($thisValue);
					foreach ($phones as $phone) {
						if ($phone != "") {
							$phone_statement = new Statement($primary_topic_resource,new Resource('http://qdos.com/schema#metaphone'), new Literal($phone));
							$model->addWithOutDuplicates($phone_statement);
						}
					}
					
				}
				if ($this->predicateUri == 'http://xmlns.com/foaf/0.1/family_name') {
					$phones = $phones = double_metaphone($thisValue);
					foreach ($phones as $phone) {
						if ($phone != "") {
							$phone_statement = new Statement($primary_topic_resource,new Resource('http://qdos.com/schema#metaphone'), new Literal($phone));
							$model->addWithOutDuplicates($phone_statement);
						}
					}
				}
				if ($this->predicateUri == 'http://xmlns.com/foaf/0.1/surname') {
					$phones = $phones = double_metaphone($thisValue);
					foreach ($phones as $phone) {
						if ($phone != "") {
							$phone_statement = new Statement($primary_topic_resource,new Resource('http://qdos.com/schema#metaphone'), new Literal($phone));
							$model->addWithOutDuplicates($phone_statement);
						}
					}
				}
				if ($this->predicateUri == 'http://xmlns.com/foaf/0.1/name') {
					$names = split(' ',$thisValue);
					foreach ($names as $name) {
						$phones = $phones = double_metaphone($name);
						foreach ($phones as $phone) {
							if ($phone != "") {
								$phone_statement = new Statement($primary_topic_resource,new Resource('http://qdos.com/schema#metaphone'), new Literal($phone));
								$model->addWithOutDuplicates($phone_statement);
							}
						}
					}
				}
				$model->addWithOutDuplicates($new_statement);
			} else {
				error_log("an empty triple was generated in a simplefield".$this->predicateUri);
			}
		}
	}
	

	public function getPredicateUri(){
		return $this->predicateUri;
	}
	public function setPredicateUri($predicateUri){
		$this->predicateUri = $predicateUri;
	}
}
?>
