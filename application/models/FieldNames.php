<?php
/*TODO: maybe the OO should go deeper than this and maybe this should be called something different e.g. querypieces.
Perhaps all fieldNames should have there own class, which inherits from a fieldNames interface?  The interface could contain
methods like getQueryBit, getName, saveToModel etc.*/

class FieldNames {
	
	//an array containing all the fieldNames in this object
	private $simpleFieldNameArray = Array();
	//TODO: how will this array actually work?
	private $complicatedFieldNameArray = Array();
	
	public function FieldNames(){
		/*
		 * TODO: extend this to define constants for whichever page is being viewed
		 * e.g. The basics, Contact details etc.  Could pass in a page parameter or something.
		 */
		$this->simpleFieldNameArray['foafName'] = '?x foaf:name ?foafName';
		$this->simpleFieldNameArray['foafHomepage'] = '?x foaf:homepage ?foafHomepage';
		$this->simpleFieldNameArray['foafNick'] = '?x foaf:nick ?foafNick';
		$this->simpleFieldNameArray['foafBirthday'] = '?x foaf:birthday ?foafBirthday';
		$this->simpleFieldNameArray['foafDateOfBirth'] = '?x foaf:dateOfBirth ?foafDateOfBirth';
		$this->simpleFieldNameArray['foafHomepage'] = '?x foaf:homepage ?foafHomepage';
		
		/*a slightly different one, since this is obtained through more than one triple in the sparql query*/
		$this->complicatedFieldNameArray['bioBirthday'] = '?x bio:event ?e .
        											 ?e rdf:type bio:Birth .
        											 ?e bio:date ?bioBirthday .';
	}
	
	//TODO: for multiple pages pass in page parameter here.
	public function getSimpleFieldNames(){
		return $this->simpleFieldNameArray;
	}
	
	public function getComplicatedFieldNames(){
		return $this->complicatedFieldNameArray;
	}
	
	public function getAllFieldNames(){
		return array_merge($this->complicatedFieldNameArray,$this->simpleFieldNameArray);
	}
}
?>