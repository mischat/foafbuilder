<?php
require_once 'Field.php';
require_once 'helpers/Utils.php';
require_once 'helpers/IFPTriangulation.class.php';
require_once 'helpers/settings.php';
require_once 'helpers/sparql.php';

/*FIXME: perhaps fields shouldn't do the whole sparql query thing in the constructor.*/

/*class to represent one item e.g. foafName or bioBirthday... not the same as one triple*/
class KnowsField extends Field {
	
    /*predicateUri is only appropriate for simple ones (one triple only)*/
    public function KnowsField($foafData) {
        /*TODO MISCHA dump test to check if empty */

        if($foafData->getPrimaryTopic()) {  	
        			
			/*get all known ifps of this person by making use of the foaf.qdos.com KB*/
        	$ifps = $this->getTriangulatedIfps($foafData);              
        	
        	$initialIfps = $this->getUserIfps($foafData);
        	/**/
        	$knowsUserIfps = $this->getKnowsUserIfps($initialIfps,$foafData->getPrimaryTopic());
        	
        	/*more or less done*/
        	$userKnowsIfps = $this->getUserKnowsIfps($foafData);   

        	/*needs refining*/
        	$mutualFriendsIfps = $this->getMutualFriendsIfps($userKnowsIfps, $knowsUserIfps);
        	
        	$knowsUserDetails = $this->getDetailsFromIfps($knowsUserIfps); 
        	$userKnowsDetails = $this->getDetailsFromIfps($userKnowsIfps);   
        	$mutualFriendsDetails = $this->getDetailsFromIfps($mutualFriendsIfps);
        	
        	$this->data['foafKnowsFields'] = array();
        	$this->data['foafKnowsFields']['mutualFriends'] = $mutualFriendsDetails;
        	$this->data['foafKnowsFields']['userKnows'] = $userKnowsDetails;
        	$this->data['foafKnowsFields']['knowsUser'] = $knowsUserDetails;
        	
            $this->data['foafKnowsFields']['displayLabel'] = 'Friends';
            $this->data['foafKnowsFields']['name'] = 'foafKnows';
            $this->name = 'foafKnows';
            $this->label = 'Friends';
        }
    }
    
    /*returns an array of arrays of ifps for each friend that the user knows who knows the user as well.  
	This function also cleans out these 'mutual friends' from the userknows and knowsuser arrays*/
    private function getMutualFriendsIfps(&$userKnowsIfps, &$knowsUserIfps){
    	if(!$userKnowsIfps || !$knowsUserIfps || empty($userKnowsIfps) || empty($knowsUserIfps)){
    		return array();
    	}
    	
    	/*build up intersecting mutual friends array*/
    	$mutualFriendsIfps = array();
    	$newUserKnowsIfps = $userKnowsIfps;
    	$newKnowsUserIfps = $knowsUserIfps;
    	
    	foreach($userKnowsIfps as $keyUK => $thisUserKnowsFriend){
    		
    		$thisMutualFriend = array();
    		
    		foreach($knowsUserIfps as $keyKU => $thisKnowsUserFriend){
    			$intersection_array = array_intersect($thisUserKnowsFriend, $thisKnowsUserFriend);
				
    			if(!empty($intersection_array)){
    	
    				//a mutal friend was found
    				$thisMutualFriend = array_merge($thisMutualFriend, $thisKnowsUserFriend);
    				$thisMutualFriend = array_merge($thisMutualFriend, $thisUserKnowsFriend);
					
    				//since these are mutual friends we need to remove them from this array.  Nulls will be cleaned later.
    				$newKnowsUserIfps[$keyKU] = null;
    			} 
    		}
    		if(!empty($thisMutualFriend)){
    			array_push($mutualFriendsIfps,array_unique($thisMutualFriend));
    			$newUserKnowsIfps[$keyUK] = null;
    		}
    	}

    	/*clean out nulls for user knows*/
    	$cleanedUserKnowsIfps = array();
    	foreach($newUserKnowsIfps as $elem){
    		if($elem){
    			array_push($cleanedUserKnowsIfps,$elem);
    		} 
    	}
    	$userKnowsIfps = $cleanedUserKnowsIfps;
    	
        /*clean out nulls for knows user*/
    	$cleanedKnowsUserIfps = array();
    	foreach($newKnowsUserIfps as $elem){
    		if($elem){
    			array_push($cleanedKnowsUserIfps,$elem);
    		}
    	}
    	$knowsUserIfps = $cleanedKnowsUserIfps;
    	
    	return $mutualFriendsIfps;
    }
    
    /*array of the user details*/
    private function getDetailsFromIfps($userKnowsIfps){
    	//TODO: need to ask this query over the inMemModel as well
    	//array to store all the details of people that the user knows
        	$userKnowsDetails = array();
        	
        	foreach($userKnowsIfps as $thisFriendsIfps){
				
        		$inquery = '';
	        	foreach($thisFriendsIfps as $ifp){
	                if(isset($ifp) && $ifp && substr($ifp,0,2)!="_:"){
	                    $inquery.=" ?ifp = ".$ifp."||";
	               }
	            }
	                
	        	/*build a query to get each friend's details*/
	        	$query = "PREFIX foaf: <http://xmlns.com/foaf/0.1/> PREFIX bio: <http://purl.org/vocab/bio/0.1/> PREFIX ya: <http://blogs.yandex.ru/schema/foaf/>";
	        	$query .= " SELECT DISTINCT ?img ?name WHERE {";
	        	$query .= " ?uri ?ifp_predicate ?ifp . 
	        				?uri foaf:name ?name .
	        				OPTIONAL{
	        					?uri foaf:depiction ?img
	        				} .
	        				OPTIONAL{
	        				   ?uri foaf:img ?img 
	        				} . 
	        				FILTER(?ifp_predicate = foaf:homepage || ?ifp_predicate = foaf:weblog || ?ifp_predicate = foaf:mbox  ||?ifp_predicate = foaf:mbox_sha1sum) .
	        				FILTER(".substr($inquery,0,-2).") } LIMIT 50";
	        	
	        	$thisFriendResults = sparql_query(FOAF_EP, $query);
      
        		$thisFriendDetails = array();
        		/*pick just one name, depiction/image etc for this person*/
        		foreach($thisFriendResults as $row){
        			if(isset($row['?name']) && $row['?name'] && $row['?name'] != 'NULL'){
        				$thisFriendDetails['name'] = sparql_strip($row['?name']);
        			}
        			if(isset($row['?img']) && $row['?img'] && $row['?img'] != 'NULL'){
        				$thisFriendDetails['img'] = sparql_strip($row['?img']);
        			}
        			//array_walk($thisFriendsIfps,'sparql_strip');
        			
        			//get rid of angle brackets, quotes etc so that they are easy to read
        			$strippedIfps = array();
        			foreach($thisFriendsIfps as $ifp){
        				array_push($strippedIfps,sparql_strip($ifp));
        			}
        			$thisFriendDetails['ifps'] = $strippedIfps;
        		}
        		if(!empty($thisFriendDetails)){
        			array_push($userKnowsDetails,$thisFriendDetails);
        		}
     
        		$userKnowsDetails;
        	}
        	
        	//sort alphabetically by name
        	usort($userKnowsDetails, array('KnowsField','nameSorter'));
        	
        	//var_dump($userKnowsArray);
        	return $userKnowsDetails;	
    }
    
    public function nameSorter($a,$b){
    	$ret = strcmp($a['name'],$b['name']);
    	if($ret == 0){
    		return 1;
    	}

    	return $ret;
    }
    
    /*get the ifps (or uris) of the people who this person knows from the loaded foaf files, keyed on the persons uri*/
    private function getUserKnowsIfps($foafData){
    	
    	$query = "PREFIX foaf: <http://xmlns.com/foaf/0.1/> PREFIX bio: <http://purl.org/vocab/bio/0.1/> PREFIX ya: <http://blogs.yandex.ru/schema/foaf/>
						SELECT DISTINCT ?homepage ?weblog ?mbox ?mbox_sha1sum ?uri
						WHERE {
						 	 <".$foafData->getPrimaryTopic()."> foaf:knows ?uri .
						 	 OPTIONAL{
						 	 	?uri foaf:homepage ?homepage
						 	 }
						 	 OPTIONAL{
						 	 	?uri foaf:weblog ?weblog
						 	 }
						 	 OPTIONAL{
						 	 	?uri foaf:mbox ?mbox
						 	 }
						 	 OPTIONAL{
						 	 	?uri foaf:mbox_sha1sum ?mbox_sha1sum
						 	 }
						}";//FIXME: need to look for uris here too
    	 //FILTER(?ifp_predicate = foaf:homepage || ?ifp_predicate = foaf:weblog || ?ifp_predicate = foaf:mbox  ||?ifp_predicate = foaf:mbox_sha1sum);
    	$potentialIfpArray = $foafData->getModel()->SparqlQuery($query);
    	$actualIfpArray = array();
    	
    	if(!empty($potentialIfpArray)){
    		foreach($potentialIfpArray as $row){
    			
				/* we need to key this on the uri of the person that the user knows */
    			if(!isset($actualIfpArray[$row['?uri']->uri])){
    				$actualIfpArray[$row['?uri']->uri] = array();
    			}
    			
    			/*add the ifp */
    			if(isset($row['?homepage']) && $row['?homepage']){
    				if(property_exists($row['?homepage'],'uri') && $row['?homepage']->uri){
    					array_push($actualIfpArray[$row['?uri']->uri],"<".$row['?homepage']->uri.">");
    				} else if(property_exists($row['?homepage'],'label') && $row['?homepage']->label){
    					array_push($actualIfpArray[$row['?uri']->uri],"<".$row['?homepage']->label.">");
    				}
    			}
    			if(isset($row['?weblog']) && $row['?weblog']){
    				if(property_exists($row['?weblog'],'uri') && $row['?weblog']->uri){
    					array_push($actualIfpArray[$row['?uri']->uri],"<".$row['?weblog']->uri.">");
    				} else if(property_exists($row['?weblog'],'label') && $row['?weblog']->label){
    					array_push($actualIfpArray[$row['?uri']->uri],"<".$row['?weblog']->label.">");	
    				}
    			}
    			if(isset($row['?mbox']) && $row['?mbox']){
    				if(property_exists($row['?mbox'],'uri') && $row['?mbox']->uri){
    					array_push($actualIfpArray[$row['?uri']->uri],"<".$row['?mbox']->uri.">");	
    				} else if(property_exists($row['?mbox'],'label') && $row['?mbox']->label){
    					array_push($actualIfpArray[$row['?uri']->uri],"<".$row['?mbox']->label.">");	
    				}	
    			}
    			if(isset($row['?mbox_sha1sum']) && $row['?mbox_sha1sum']){
    				if(property_exists($row['?mbox_sha1sum'],'uri') && $row['?mbox_sha1sum']->uri){
    					array_push($actualIfpArray[$row['?uri']->uri],'"'.$row['?mbox_sha1sum']->uri.'"');	
    				} else if(property_exists($row['?mbox_sha1sum'],'label') && $row['?mbox_sha1sum']->label){
    					array_push($actualIfpArray[$row['?uri']->uri],'"'.$row['?mbox_sha1sum']->label.'"');	
    				}	
    			}
    		}
    	}
    	
    	/*loop through the people they know and triangulate their IFPs*/
        $userKnowsIfps = array();
        foreach($actualIfpArray as $row){
        	$thisFriendsIfps = IFPTriangulation::doIterativeIFPTriangulation($row);
        	array_push($userKnowsIfps,$thisFriendsIfps);
        }
        /*unique the array in case some people were duplicated*/
        //TODO: unique this array
      
    	return $userKnowsIfps;
    }
    
    /*get ifps, keyed on the uri, of people who say they know the user*/
    private function getKnowsUserIfps($ifps,$primaryTopic){
    
	    $model = new MemModel('baseuri');
	    echo("start");
	    $model->load('http://foaf.qdos.com/reverse?path='.$primaryTopic);
	    echo('1');
    	
	    foreach($ifps as $ifp){
	   
    			$model->load('http://foaf.qdos.com/reverse?path='.urlencode(sparql_strip($ifp))."&ifp");
	    		//FIXME: behave well if there is a nasty error here and get rid of the break below for when the reverse bug is fixed
	    		break;
	    }
    		
    	$model->writeAsHTML();
    	
    	//need to actually return some knowsRDFs
    	return array();
    }
    
    
    private function getUserIfps($foafData){
    	
    /*get the ifps that the person has already entered in their foaf file*/
        $initialQuery = "PREFIX foaf: <http://xmlns.com/foaf/0.1/> PREFIX bio: <http://purl.org/vocab/bio/0.1/> PREFIX ya: <http://blogs.yandex.ru/schema/foaf/>
        			SELECT ?homepage ?weblog ?mbox ?mbox_sha1sum WHERE {
        				?person foaf:primaryTopic <".$foafData->getPrimaryTopic().">
        				?person foaf:primaryTopic ?primaryTopic
        				OPTIONAL{
        					?primaryTopic foaf:homepage ?homepage
        				} .
        				OPTIONAL{
        					?primaryTopic foaf:weblog ?weblog
        				} .
        				OPTIONAL{
        					?primaryTopic foaf:mbox ?mbox
        				} .
        				OPTIONAL{
        					?primaryTopic foaf:mbox_sha1sum ?mbox_sha1sum
        				}
        			};";
        
        $initialIfpResults = $foafData->getModel()->SparqlQuery($initialQuery);
        	
        /*loop through the results populating the ifp array - care about labels or uris*/
        $initialIfpArray = array();		
        if(!empty($initialIfpResults)){
	        foreach($initialIfpResults as $row){
	        	if(isset($row['?weblog']) && property_exists($row['?weblog'],'uri')){
	        		array_push($initialIfpArray,"<".$row['?weblog']->uri.">");
	        	}
	        	if(isset($row['?mbox']) && property_exists($row['?mbox'],'uri')){
	        		array_push($initialIfpArray,"<".$row['?mbox']->uri.">");
	        	}
	        	if(isset($row['?mbox_sha1sum']) && property_exists($row['?mbox_sha1sum'],'uri')){
	        		array_push($initialIfpArray,'"'.$row['?mbox_sha1sum']->uri.'"');
	        	}
	        	if(isset($row['?homepage']) && property_exists($row['?homepage'],'uri')){
	        		array_push($initialIfpArray,"<".$row['?homepage']->uri.">");
	        	}		
	        	if(isset($row['?mbox']) && property_exists($row['?weblog'],'label')){
	        		array_push($initialIfpArray,"<".$row['?mbox']->label.">");
	        	}
	        	if(isset($row['?mbox_sha1sum']) && property_exists($row['?mbox_sha1sum'],'label')){
	        		array_push($initialIfpArray,'"'.$row['?mbox_sha1sum']->label.'"');
	        	}
	        	if(isset($row['?homepage']) && property_exists($row['?homepage'],'label')){
	        		array_push($initialIfpArray,"<".$row['?homepage']->label.">");
	        	}
	        	if(isset($row['?weblog']) && property_exists($row['?weblog'],'label')){
	        		array_push($initialIfpArray,"<".$row['?weblog']->label.">");
	        	}
	        }
        }
        
        return $initialIfpArray;
    }
    
    /*queries the loaded foaf file and queries foaf.qdos.com then triangulates the two*/
    private function getTriangulatedIfps($foafData){
    	
    	$initialIfpArray = $this->getUserIfps($foafData);
        
       	/*triangulate the ifps to grow the array*/
        $ifps = IFPTriangulation::doIterativeIFPTriangulation($initialIfpArray);
        
		return $ifps;
    }
	
    /*saves the values created by the editor in value... as encoded in json. */
    public function saveToModel(&$foafData, $value) {

			require_once 'FieldNames.php';
			
			$predicate_resource = new Resource('http://xmlns.com/foaf/0.1/mbox');
			$primary_topic_resource = new Resource($foafData->getPrimaryTopic());
			
			//find existing triples
			$foundModel = $foafData->getModel()->find($primary_topic_resource,$predicate_resource,NULL);
			
			//remove existing triples
			foreach($foundModel->triples as $triple){
				$foafData->getModel()->remove($triple);
			}
			
			//add new triples
			$valueArray = $value->values;
			foreach($valueArray as $thisValue){
				$mangledValue = $this->onSaveMangleEmailAddress($thisValue);
				
				$literalValue = new Literal($mangledValue);
		
				$new_statement = new Statement($primary_topic_resource,$predicate_resource,$literalValue);	
				$foafData->getModel()->add($new_statement);
			}

    }

}

