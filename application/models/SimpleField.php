<?php
require_once 'Field.php';

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
		
		//add new triples
		$valueArray = get_object_vars($value);
		foreach($valueArray[$this->name]->values as $thisValue){
			if($this->type == 'literal'){
				$value_res_or_literal = new Literal($thisValue);
			} else if($this->type == 'resource'){
				$value_res_or_literal = new Resource($thisValue);
			} 		
			$new_statement = new Statement($primary_topic_resource,$predicate_resource,$value_res_or_literal);	
			$model->add($new_statement);
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