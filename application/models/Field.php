<?php

abstract class Field{
	protected $name;
	protected $queryBit;
	protected $type;//resource or literal.  TODO: Should be an enumerator really.
	protected $label;
	protected $keepNulls;//whether we keep the field even if empty (i.e. render an empty box);
	
	public function Field(){	
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
	
	public function getLabel(){
		return $this->label;
	}
	public function setLabel($label){
		$this->label = $label;
	}
	
	public function setKeepNulls($keepNulls){
		$this->keepNulls = $keepNulls;
	}
	public function getKeepNulls(){
		return $this->keepNulls;
	}
	
	public function saveToModel(&$foafData, $value, $index){
	
	}
	
	//some sort of save thing ought to go here
}

?>