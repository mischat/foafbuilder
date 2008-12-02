<?php
//TODO: we shouldn't have to instantiate all of these in order to know which fields are on which 'page'
require_once "SimpleField.php";

class FieldNames {
	
	//an array containing all the fieldNames in this object
	private $allFieldNames = Array();
	private $foafData;
	private $privateFoafData;
	
	public function FieldNames($type,$foafData = false ,$privateFoafData = false ){
		
		if($foafData){
			$this->foafData = $foafData;
		}
		if($privateFoafData){
			$this->privateFoafData = $privateFoafData;
		}
		
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
			case "locations":
				$this->instantiateLocationsFields();
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
				//FIXME: commented others out for development purposes
				$this->instantiateLocationsFields(false);
				$this->instantiateTheBasicsFields(false);
				$this->instantiateContactDetailsFields(false);
				$this->instantiatePicturesFields(false);
				$this->instantiateAccountsFields(false);
				$this->instantiateFriendsFields(false);
				//$this->instantiateBlogsFields(false);
				//$this->instantiateInterestsFields(false);
				//$this->instantiateOtherFields(false);
				break;	
		}
	}
	
	/*instantiates arrays of fields for all the items on the basics page*/
	private function instantiateTheBasicsFields($fullInstantiation = true){
		//var_dump($this->privateFoafData);
		//FIXME: homepage and birthday temporarily commented out for dev purposes
		$this->allFieldNames['foafBirthday'] = new BirthdayField($this->foafData,$this->privateFoafData,$fullInstantiation);
		$this->allFieldNames['foafTitle'] = 
			new SimpleField('foafTitle', 'Title', "http://xmlns.com/foaf/0.1/title",$this->foafData, $this->privateFoafData, "literal",$fullInstantiation);
		$this->allFieldNames['foafGivenName'] = 
			new SimpleField('foafGivenName', 'Given Name', "http://xmlns.com/foaf/0.1/givenname",$this->foafData, $this->privateFoafData,"literal",$fullInstantiation);
		$this->allFieldNames['foafFamilyName'] = 
			new SimpleField('foafFamilyName', 'Family Name', 'http://xmlns.com/foaf/0.1/family_name',$this->foafData, $this->privateFoafData,'literal',$fullInstantiation);
		$this->allFieldNames['foafName'] = 
			new SimpleField('foafName', 'Real Name', 'http://xmlns.com/foaf/0.1/name',$this->foafData, $this->privateFoafData,'literal',$fullInstantiation);

		$this->allFieldNames['foafNick'] = 
			new SimpleField('foafNick', 'Nickname', 'http://xmlns.com/foaf/0.1/nick',$this->foafData, $this->privateFoafData,'literal',$fullInstantiation);
	}
	
	private function instantiateContactDetailsFields($fullInstantiation = true){
		$this->allFieldNames['foafPhone'] = new PhoneField($this->foafData,$this->privateFoafData,$fullInstantiation);
		$this->allFieldNames['foafMbox'] = new MboxField($this->foafData,$this->privateFoafData,$fullInstantiation);
		$this->allFieldNames['address'] = new AddressField($this->foafData,$this->privateFoafData,$fullInstantiation);
	}
	
	private function instantiatePicturesFields($fullInstantiation = true){
		$this->allFieldNames['foafDepiction'] = new DepictionField($this->foafData,$this->privateFoafData,$fullInstantiation);
		$this->allFieldNames['foafImg'] = new ImgField($this->foafData,$this->privateFoafData,$fullInstantiation);
	}
	
	private function instantiateAccountsFields($fullInstantiation = true){
		$this->allFieldNames['foafHoldsAccount'] = new HoldsAccountField($this->foafData,$this->privateFoafData,$fullInstantiation);
		$this->allFieldNames['foafHomepage'] = new HomepageField($this->foafData,$this->privateFoafData,$fullInstantiation);
		$this->allFieldNames['foafWeblog'] = new BlogField($this->foafData,$this->privateFoafData,$fullInstantiation);
	}
	
	private function instantiateFriendsFields($fullInstantiation = true){
		$this->allFieldNames['foafKnows'] = new KnowsField($this->foafData,$fullInstantiation);
	}
	
	private function instantiateBlogsFields($fullInstantiation = true){
		$this->allFieldNames['foafWeblog'] = 
			new SimpleField('foafWeblog', 'Blogs', "http://xmlns.com/foaf/0.1/weblog",$this->foafData,"resource",$fullInstantiation);
	}
	
	private function instantiateInterestsFields($fullInstantiation = true){
		$this->allFieldNames['foafInterest'] = new SimpleField('foafInterest', 'Interests', "http://xmlns.com/foaf/0.1/interest",$this->foafData,"resource", $fullInstantiation);
	}
	
	private function instantiateLocationsFields($fullInstantiation = true){
		$this->allFieldNames['nearestAirport'] = new NearestAirportField($this->foafData,$this->privateFoafData, $fullInstantiation);
		$this->allFieldNames['basedNear'] = new BasedNearField($this->foafData,$this->privateFoafData, $fullInstantiation);	
	}
	
	private function instantiateOtherFields($fullInstantiation = true){
		//TODO: what goes on here?
	}
	
	public function getAllFieldNames(){
		return $this->allFieldNames;
	}
	
}
?>
