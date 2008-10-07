<?php
//TODO: we shouldn't have to instantiate all of these in order to know which fields are on which 'page'
require_once "SimpleField.php";
require_once "GeoLatLongField.php";
require_once "GeoLatField.php";
require_once "GeoLongField.php";

class FieldNames {
	
	//an array containing all the fieldNames in this object
	private $allFieldNames = Array();
	private $foafData;
	
	public function FieldNames($type,$foafData){
		$this->foafData = $foafData;
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
			case "pictures":
				$this->instantiatePicturesFields();
				break;
			case "accounts":
				$this->instantiateAccountsFields();
				break;
			case "friends":
				$this->instantiateFriendsFields();
				break;
			case "blogs":
				$this->instantiateBlogsFields();
				break;
			case "interests":
				$this->instantiateInterestsFields();
				break;
			case "other":
				$this->instantiateOtherFields();
				break;
			case "all":
				$this->instantiateTheBasicsFields();
				$this->instantiateContactDetailsFields();
				$this->instantiatePicturesFields();
				$this->instantiateAccountsFields();
				$this->instantiateFriendsFields();
				$this->instantiateBlogsFields();
				$this->instantiateInterestsFields();
				$this->instantiateOtherFields();
				break;	
		}
	}
	
	/*instantiates arrays of fields for all the items on the basics page*/
	private function instantiateTheBasicsFields(){
		$this->allFieldNames['birthday'] = new BirthdayField($this->foafData);
		$this->allFieldNames['foafTitle'] = 
			new SimpleField('foafTitle', 'Title', "http://xmlns.com/foaf/0.1/title",$this->foafData,"literal");
		$this->allFieldNames['foafGivenName'] = 
			new SimpleField('foafGivenName', 'Given Name', "http://xmlns.com/foaf/0.1/givenname",$this->foafData,"literal");
		$this->allFieldNames['foafFamilyName'] = 
			new SimpleField('foafFamilyName', 'Family Name', 'http://xmlns.com/foaf/0.1/family_name',$this->foafData,'literal');
		$this->allFieldNames['foafName'] = 
			new SimpleField('foafName', 'Real Name', 'http://xmlns.com/foaf/0.1/name',$this->foafData,'literal');
		$this->allFieldNames['foafHomepage'] = 
			new SimpleField('foafHomepage', 'Homepage', 'http://xmlns.com/foaf/0.1/homepage',$this->foafData,'resource');
		$this->allFieldNames['foafNick'] = 
			new SimpleField('foafNick', 'Nickname', 'http://xmlns.com/foaf/0.1/nick',$this->foafData,'literal');
		
		//$this->allFieldNames['geoLatLong'] =  new GeoLatLongField();
		//$this->allFieldNames['geoLat'] =  new GeoLatField();
		//$this->allFieldNames['geoLong'] =  new GeoLongField();	
	}
	
	private function instantiateContactDetailsFields(){
		/*$this->allFieldNames['foafMbox'] = 
			new SimpleField('foafMbox', 'Email', '?x foaf:mbox ?foafMbox', 'literal',false,"http://xmlns.com/foaf/0.1/mbox");
		$this->allFieldNames['foafPhone'] = 
			new SimpleField('foafPhone', 'Phone', '?x foaf:phone ?foafPhone', 'literal',false,'http://xmlns.com/foaf/0.1/phone');
		$this->allFieldNames['foafMbox_sha1sum'] = 
			new SimpleField('foafMbox_sha1sum', 'Email (sha1sum)', '?x foaf:mbox_sha1sum ?foafMbox_sha1sum', 'literal',false,'http://xmlns.com/foaf/0.1/mbox_sha1sum');
		*/
		$this->allFieldNames['location'] = new LocationField($this->foafData);
	}
	
	private function instantiatePicturesFields(){
		/*
		$this->allFieldNames['foafDepiction'] = 
			new SimpleField('foafDepiction','Image', '?x foaf:depiction ?foafDepiction', 'resource',false,"http://xmlns.com/foaf/0.1/depiction");
		*/
	}
	
	private function instantiateAccountsFields(){
		$this->allFieldNames['foafHoldsAccount'] = new HoldsAccountField($this->foafData);
	}
	
	private function instantiateFriendsFields(){
		
	}
	
	private function instantiateBlogsFields(){
		
	}
	
	private function instantiateInterestsFields(){
		
	}
	
	private function instantiateOtherFields(){
		
	}
	
	public function getAllFieldNames(){
		return $this->allFieldNames;
	}
	
}
?>
