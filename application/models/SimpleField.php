<?php
require_once 'Field.php';

/*class to represent one item e.g. foafName or bioBirthday... not the same as one triple*/
class SimpleField extends Field{
	
	private $predicateUri;
	
	/*predicateUri is only appropriate for simple ones (one triple only)*/
	//TODO: should label be here
	public function SimpleField($name, $label, $predicateUri, $foafData, $type){
		
		$queryString = "SELECT ?".$name." WHERE {<".$foafData->getPrimaryTopic()."> <".$predicateUri."> ?".$name." }";
		$results = $foafData->getModel()->SparqlQuery($queryString);		
		
		$this->data = array();
		$this->data['fields'] = array();
		
		/*mangle the results so that they can be easily rendered*/
		
		$this->data['fields'][$name] = array();
		$this->data['fields'][$name]['values'] = array();
		
		if(isset($results[0])){
			foreach($results as $row){
				foreach($row as $key => $value){	
					$key = str_replace('?','',$key);
					
					if(property_exists($value,'label')){
				       	array_push($this->data['fields'][$name]['values'],$value->label);
				    } else if(property_exists($value,'uri')){
				       	array_push($this->data['fields'][$name]['values'],$value->uri);
				    }
				     
				}
	    	}
		}
       	$this->data['fields'][$name]['displayLabel'] = $label;
		$this->data['fields'][$name]['name'] = $name;
		
        $this->name = $name;
		$this->label = $label;
		$this->type = $type;
		$this->predicateUri = $predicateUri;		
	}

	/*saves the appropriate triples in the model at the appropriate index and replace them with $value*/
	public function saveToModel(&$foafData, &$value){

		require_once 'SimpleField.php';
		require_once 'FieldNames.php';
		
		$predicate_resource = new Resource($this->predicateUri);
		$primary_topic_resource = new Resource($foafData->getPrimaryTopic());
		
		//find existing triples
		$foundModel = $foafData->getModel()->find($primary_topic_resource,$predicate_resource,NULL);
		
		//remove existing triples
		foreach($foundModel->triples as $triple){
			$foafData->getModel()->remove($triple);
		}
		
		//add new triples
		$valueArray = get_object_vars($value);
		foreach($valueArray[$this->name]->values as $thisValue){
			if($this->type == 'literal'){
				$value_res_or_literal = new Literal($thisValue);
			} else if($this->type == 'resource'){
				$value_res_or_literal = new Resource($thisValue);
			} 		
			$new_statement = new Statement($primary_topic_resource,$predicate_resource,$value_res_or_literal);	
			$foafData->getModel()->add($new_statement);
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