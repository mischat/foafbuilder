<?php
/*TODO: maybe the OO should go deeper than this and maybe this should be called something different e.g. querypieces.
Perhaps all fieldNames should have there own class, which inherits from a fieldNames interface?  The interface could contain
methods like getQueryBit, getName, saveToModel etc.*/
require_once "SimpleField.php";
require_once "BioBirthdayField.php";
require_once "GeoLatLongField.php";
require_once "GeoLatField.php";
require_once "GeoLongField.php";

class FieldNames {
	
	//an array containing all the fieldNames in this object
	private $allFieldNames = Array();

	public function FieldNames($type){
		//TODO: possibly we don't want two different arrays here
		require_once 'Field.php';
		/*
		 * TODO: extend this to define constants for whichever page is being viewed
		 * e.g. The basics, Contact details etc.  Could pass in a page parameter or something.
		 * ?x in the query is the uri of the person we're concerned with.
		 */
		switch($type){
			/*
			 * TODO: not sure about the 'all option here' and indeed whether or not this option should be used
			 * when saving and in main.phtml.
			 */
			case "theBasics":
				$this->instantiateTheBasicsFields();
				break;
			case "contactDetails":
				$this->instantiateContactDetailsFields();
				break;
			case "all":
				$this->instantiateTheBasicsFields();
				$this->instantiateContactDetailsFields();
				break;	
		}
	}
	
	/*instantiates arrays of fields for all the items on the basics page*/
	private function instantiateTheBasicsFields(){
		$this->allFieldNames['foafTitle'] = 
			new SimpleField('foafTitle', '?x foaf:name ?foafName', 'literal',"http://xmlns.com/foaf/0.1/title");
		$this->allFieldNames['foafName'] = 
			new SimpleField('foafName', '?x foaf:name ?foafName', 'literal','http://xmlns.com/foaf/0.1/name');
		$this->allFieldNames['foafHomepage'] = 
			new SimpleField('foafHomepage', '?x foaf:homepage ?foafHomepage','resource','http://xmlns.com/foaf/0.1/homepage');
		$this->allFieldNames['foafNick'] = 
			new SimpleField('foafNick', '?x foaf:nick ?foafNick','literal','http://xmlns.com/foaf/0.1/nick');
		$this->allFieldNames['foafBirthday'] = 
			new SimpleField('foafBirthday','?x foaf:birthday ?foafBirthday','literal','http://xmlns.com/foaf/0.1/birthday');
		$this->allFieldNames['foafDateOfBirth'] = 
			new SimpleField('foafDateOfBirth','?x foaf:dateOfBirth ?foafDateOfBirth','literal','http://xmlns.com/foaf/0.1/dateOfBirth');
					
		$this->allFieldNames['bioBirthday'] = new BioBirthdayField();
		$this->allFieldNames['geoLatLong'] =  new GeoLatLongField();
		$this->allFieldNames['geoLat'] =  new GeoLatField();
		$this->allFieldNames['geoLong'] =  new GeoLongField();	
	}
	
	private function instantiateContactDetailsFields(){
		$this->allFieldNames['foafMbox'] = 
			new SimpleField('foafMbox', '?x foaf:mbox ?foafMbox', 'literal',"http://xmlns.com/foaf/0.1/mbox");
		$this->allFieldNames['foafPhone'] = 
			new SimpleField('foafPhone', '?x foaf:phone ?foafPhone', 'literal','http://xmlns.com/foaf/0.1/phone');
	}
	
	public function getAllFieldNames(){
		return $this->allFieldNames;
	}
	
}
?>