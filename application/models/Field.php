<?php
/*class to represent one item e.g. foafName or bioBirthday... not the same as one triple*/
class Field {
	
	private $name;
	private $queryBit;
	private $type;//bnode or literal.  TODO: Should be an enumerator really.
	private $predicateUri;
	
	/*predicateUri is only appropriate for simple ones (one triple only)*/
	public function Field($name, $queryBit, $type, $predicateUri = NULL){
		$this->name = $name;
		$this->queryBit = $queryBit;
		$this->type = $type;
		
		if($predicateUri){
			$this->predicateUri = $predicateUri;		
		}
	}
	
	public function getName(){
		return $this->name;
	}
	public function setName($name){
		$this->name = $name;
	}
	
	public function getQueryBit(){
		return $this->queryBit;
	}
	public function setQueryBit($queryBit){
		$this->queryBit = $queryBit;
	}
	
	public function getType(){
		return $this->type;
	}
	public function setType($type){
		$this->type = $type;
	}
	
	public function getPredicateUri(){
		return $this->predicateUri;
	}
	public function setPredicateUri($predicateUri){
		$this->predicateUri = $predicateUri;
	}
}
?>