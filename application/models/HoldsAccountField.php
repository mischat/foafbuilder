<?php
require_once 'Field.php';
require_once 'helpers/Utils.php';

/*class to represent one item e.g. foafName or bioBirthday... not the same as one triple*/
class HoldsAccountField extends Field {
	
	/*predicateUri is only appropriate for simple ones (one triple only)*/
	public function HoldsAccountField($foafDataPublic,$foafDataPrivate,$fullInstantiation) {
		
		$this->name = 'foafHoldsAccount';
		$this->label = 'Accounts';
		
		$this->data['public'] = array();
		$this->data['public']['foafHoldsAccountFields'] = array();
		$this->data['public']['foafHoldsAccountFields']['displayLabel'] = $this->label;
		$this->data['public']['foafHoldsAccountFields']['name'] = $this->name;
		
		$this->data['private'] = array();
		$this->data['private']['foafHoldsAccountFields'] = array();
		$this->data['private']['foafHoldsAccountFields']['displayLabel'] = $this->label;
		$this->data['private']['foafHoldsAccountFields']['name'] = $this->name;
		
		if(!$fullInstantiation){
			return;
		}
		
	  	/*Do full load*/
    	if($foafDataPublic){
			$this->doFullLoad($foafDataPublic);
		} 
		if($foafDataPrivate){
			$this->doFullLoad($foafDataPrivate);
		}
			
	}
	
	private function doFullLoad($foafData){
		
		if(!$foafData || !$foafData->getPrimaryTopic()){
			return;
		}
		
		/*so we output the right stuff*/
		$privacy = 'private';
		if($foafData->isPublic){
			$privacy = 'public';
		}
		
		$queryString = 
			"PREFIX foaf: <http://xmlns.com/foaf/0.1/>
			 PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
			 PREFIX bio: <http://purl.org/vocab/bio/0.1/>
			 PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
			 SELECT  ?a ?foafAccountProfilePage ?foafAccountServiceHomepage ?foafAccountName
			 WHERE{
					<".$foafData->getPrimaryTopic()."> foaf:holdsAccount ?a . 
					?a rdf:type foaf:OnlineAccount 
					OPTIONAL{
						?a foaf:accountProfilePage ?foafAccountProfilePage .
					} .
					OPTIONAL{
						?a foaf:accountServiceHomepage ?foafAccountServiceHomepage .
					} .
					OPTIONAL{
						?a foaf:accountName ?foafAccountName .
					}
				};";
		 
		$results = $foafData->getModel()->SparqlQuery($queryString);				
			
		if(!$results || empty($results)){
			return;
		}
		/*mangle the results so that they can be easily rendered*/
		foreach($results as $row){	
			/*key them on the account*/
			if(!isset($this->data['foafHoldsAccountFields'][$row['?a']->uri])){
				$this->data[$privacy]['foafHoldsAccountFields'][$row['?a']->uri] = array();
			}
			
			/*create an array for each of the properties we care about*/
			if(isset($row['?foafAccountProfilePage']) && $row['?foafAccountProfilePage']){
				 $this->data[$privacy]['foafHoldsAccountFields'][$row['?a']->uri]['foafAccountProfilePage'] = $row['?foafAccountProfilePage']->uri;
			}
			if(isset($row['?foafAccountServiceHomepage']) && $row['?foafAccountServiceHomepage']){
				 $this->data[$privacy]['foafHoldsAccountFields'][$row['?a']->uri]['foafAccountServiceHomepage'] = $row['?foafAccountServiceHomepage']->uri;
			}
			if(isset($row['?foafAccountName']) && $row['?foafAccountName']){
				 $this->data[$privacy]['foafHoldsAccountFields'][$row['?a']->uri]['foafAccountName'] = $row['?foafAccountName']->label;
			}
		}
	}

	public function getPredicateUri() {
		return $this->predicateUri;
	}
	public function setPredicateUri($predicateUri) {
		$this->predicateUri = $predicateUri;
	}
	/*saves the values created by the editor in value... as encoded in json.*/
	public function saveToModel(&$foafData, $value) {
		
		//XXX the fairly complex business of keeping track of all the bNodes etc has gone out of the window since the privacy implementation
		//check previous checkouts to see how it used to be done.  
		//It would be nice to get it working again so we don't lose people's valuable triples. 		
		
		$this->removeAllAccounts($foafData);
		
		
		foreach($value as $holdsAccountName => $holdsAccountContents){
			
			$holdsAccountBnode;
			if(substr($holdsAccountName,0,5)=='bNode' || strlen($holdsAccountName) == 50){
				$holdsAccountBnode = new BlankNode($foafData->getModel());		
			} else {
				$holdsAccountBnode = new Resource($holdsAccountName);				
			}

			//create an account triple here and add it to the model.
			$accountStatement = new Statement(new Resource($foafData->getPrimaryTopic()),new Resource('http://xmlns.com/foaf/0.1/holdsAccount'),$holdsAccountBnode);
			$bNodeStatement = new Statement($holdsAccountBnode,new Resource('http://www.w3.org/1999/02/22-rdf-syntax-ns#type'),new Resource('http://xmlns.com/foaf/0.1/OnlineAccount'));			
			$foafData->getModel()->add($accountStatement); 
			$foafData->getModel()->add($bNodeStatement); 
			
			if(property_exists($holdsAccountContents,'foafAccountProfilePage') && $holdsAccountContents->foafAccountProfilePage){
				echo('saving pp');
				$newStatement = new Statement($holdsAccountBnode, new Resource('http://xmlns.com/foaf/0.1/accountProfilePage'), new Resource($holdsAccountContents->foafAccountProfilePage));
				$foafData->getModel()->add($newStatement);
			}	
			if(property_exists($holdsAccountContents,'foafAccountServiceHomepage') && $holdsAccountContents->foafAccountServiceHomepage){
				echo('saving A_service_homepage');
				$newStatement = new Statement($holdsAccountBnode, new Resource('http://xmlns.com/foaf/0.1/accountServiceHomepage'), new Resource($holdsAccountContents->foafAccountServiceHomepage));
				$foafData->getModel()->add($newStatement);
			}		
			if(property_exists($holdsAccountContents,'foafAccountName') && $holdsAccountContents->foafAccountName){
				echo('saving name');
				$newStatement = new Statement($holdsAccountBnode, new Resource('http://xmlns.com/foaf/0.1/accountName'), new Literal($holdsAccountContents->foafAccountName));
				$foafData->getModel()->add($newStatement);
			}
		}
	}
	
	private function removeAllAccounts($foafData){
		//find them all
		$foundAccounts = $foafData->getModel()->find(NULL, new Resource('http://xmlns.com/foaf/0.1/holdsAccount'), NULL);
		
		if(!$foundAccounts ||  !property_exists($foundAccounts,'triples') || !$foundAccounts->triples){
			return;	
		}
		
		//loop through removing any hanging triples
		foreach($foundAccounts->triples as $triple){
			$this->removeTripleRecursively($triple,$foafData);
		}
	}
	
	//TODO: move all copies of this function to utils
  /*removes a triple and all hanging triples and the ones that hang off them
     * but doesn't go any further. XXX perhaps it should?*/
    //XXX: should be able to use rap's remove with NULLs for pred/obj
    public function removeTripleRecursively($triple, &$foafData){
    	
    	$foundHangingStuff = $foafData->getModel()->find($triple->obj,NULL,NULL);
    	
    	if($foundHangingStuff && $foundHangingStuff->triples){
    		foreach($foundHangingStuff->triples as $subTriple){
    			if(property_exists($subTriple,'obj') && $subTriple->obj && property_exists($subTriple->obj,'uri')){
	    			
    				$foundSubStuff = $foafData->getModel()->find($subTriple->obj,NULL,NULL);
					
	    			if($foundSubStuff && $foundSubStuff->triples){
	    				foreach($foundHangingStuff->triples as $subSubTriple){
	    					$foafData->getModel()->remove($subSubTriple);
	    				}
	    			}
    				$foafData->getModel()->remove($subTriple);
    			}
    		}
    	}
    	$foafData->getModel()->remove($triple);	
    }
}
?>
