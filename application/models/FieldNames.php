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
		//TODO: possibly we don't want two different arrays here
		require_once 'Field.php';
		/*
		 * TODO: extend this to define constants for whichever page is being viewed
		 * e.g. The basics, Contact details etc.  Could pass in a page parameter or something.
		 * ?x in the query is the uri of the person we're concerned with.
		 * NOTE: generates sub optimal queries (see e.g. geoLatitude/geoLongitude
		 */
		@$this->simpleFieldNameArray['foafTitle'] = new Field('foafTitle', '?x foaf:name ?foafName', 'literal');
		@$this->simpleFieldNameArray['foafName'] = new Field('foafName', '?x foaf:name ?foafName', 'literal');
		@$this->simpleFieldNameArray['foafHomepage'] = new Field('foafHomepage', '?x foaf:homepage ?foafHomepage','resource');
		@$this->simpleFieldNameArray['foafNick'] = new Field('foafNick', '?x foaf:nick ?foafNick','literal');
		@$this->simpleFieldNameArray['foafBirthday'] = new Field('foafBirthday','?x foaf:birthday ?foafBirthday','literal');
		@$this->simpleFieldNameArray['foafDateOfBirth'] = new Field('foafDateOfBirth','?x foaf:dateOfBirth ?foafDateOfBirth','literal');
		//$this->simpleFieldNameArray['foafPostCode'] = '?x foaf:homepage ?foafHomepage';
		
		/*a slightly different one, since this is obtained through more than one triple in the sparql query*/
		@$this->complicatedFieldNameArray['bioBirthday'] = new Field(
															'bioBirthday',
															'?x bio:event ?e .
        											 		 ?e rdf:type bio:Birth .
        											 		 ?e bio:date ?bioBirthday .',
															 'literal');
		@$this->complicatedFieldNameArray['geoLat'] =  new Field(
															'geoLat',
														  	'?x foaf:based_near ?l .
                        								  	?l geo:lat ?geoLat .
                        								  	?l geo:long ?geoLong
                        								  	?l geo:lat_long ?geoLatLong',
															'literal');
		@$this->complicatedFieldNameArray['geoLong'] = new Field('geoLong','','literal');//queryBit not required, since we get it with geoLat
		@$this->complicatedFieldNameArray['geoLatLong'] =  new Field('geoLatLong','','literal');
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