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
		if(isset($results[0])){
			foreach($results as $row) {
	           	$keys = array_keys($row);
	        	$keys = str_replace('?','',$keys);
	        	array_push($this->data['fields'], array_combine($keys,$row));
	    	}
		}
        
        $this->data['displayLabel'] = $label;
        $this->data['name'] = $name;
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
		/*TODO: these literal/resource values shouldn't really be hardcoded*/
		if($this->type == 'literal'){
			$value_res_or_literal = new Literal($value);
		} else if($this->type == 'resource'){
			$value_res_or_literal = new Resource($value);
		} 
		
		//TODO: need to get the primary topic in here somehow instead of doing .#me poss from session?
		$primary_topic_resource = new Resource($foafData->getPrimaryTopic());
		$new_statement = new Statement($primary_topic_resource,$predicate_resource,$value_res_or_literal);	
					
		/* Look for values with the appropriate predicate/object */
		$found_model = $foafData->getModel()->find($primary_topic_resource,$predicate_resource, NULL);
					
		/* Remove a matching triple (if there) and add the new one whilst remembering that there can
		 * be more than one e.g. foafName and we only want to remove the one at the appropriate index.*/ 
		if(isset($found_model->triples[0])){
				$foafData->getModel()->remove($found_model->triples[0]);
		}
		$foafData->getModel()->add($new_statement);
	}
	

	public function getPredicateUri(){
		return $this->predicateUri;
	}
	public function setPredicateUri($predicateUri){
		$this->predicateUri = $predicateUri;
	}
}
?>