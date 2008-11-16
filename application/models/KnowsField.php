<?php
require_once 'Field.php';
require_once 'helpers/Utils.php';
require_once 'helpers/IFPTriangulation.class.php';
require_once 'helpers/URITriangulation.class.php';
require_once 'helpers/settings.php';
require_once 'helpers/sparql.php';

/*FIXME: perhaps fields shouldn't do the whole sparql query thing in the constructor.*/

/*class to represent one item e.g. foafName or bioBirthday... not the same as one triple*/
class KnowsField extends Field {
	
	/**/
	public function removeFriend($friend,&$foafData){
		
		$successfulRemove = 0;	

		if(property_exists($friend,'uri')){
			//TODO: remove via uri here
		} else {
			
			//?knowsResource ?ifp_predicate 
						 	//}";
			$query  = 	"PREFIX foaf: <http://xmlns.com/foaf/0.1/>
						 	SELECT DISTINCT ?knowsResource ?ifp_predicate WHERE {
						 		<".$foafData->getPrimaryTopic()."> foaf:knows ?knowsResource";
			
			
			foreach($friend->ifps as $ifp){
				$query .= " . OPTIONAL{ ?knowsResource ?ifp_predicate <".$ifp.">  }";
				$query .= " . OPTIONAL{ ?knowsResource ?ifp_predicate \"".$ifp."\" }";
				$query .= " . OPTIONAL{ ?knowsResource ?ifp_predicate \"".sha1($ifp)."\" }";
				$query .= " . OPTIONAL{ ?knowsResource ?ifp_predicate \"".sha1('mailto:'.$ifp)."\" }";
			}
			
			$query.="}";
			$results = $foafData->getModel()->SparqlQuery($query);

			foreach($results as $row){
				if($row['?ifp_predicate']){
					$triple = new Statement(new Resource($foafData->getPrimarytopic()),new Resource("http://xmlns.com/foaf/0.1/knows"),$row['?knowsResource']);
					$this->removeTripleRecursively($triple, $foafData);
					$successfulRemove = 1;	
				}
			}
		}

		return $successfulRemove;
	}


	/**/
        public function addFriend($friend,&$foafData){

                $successfulAdd = 0;

                if(property_exists($friend,'uri')){
                        //TODO: add just the  uri here
                } else {
			
			$bNode = Utils::GenerateUniqueBnode($foafData->getModel());
			echo($bNode);
                	$foafData->getModel()->add(new Statement(new Resource($foafData->getPrimaryTopic()),new Resource("http://xmlns.com/foaf/0.1/knows"), $bNode));
                	$foafData->getModel()->add(new Statement($bNode,new Resource("http://www.w3.org/1999/02/22-rdf-syntax-ns#type"),new Resource("http://www.w3.org/1999/02/22-rdf-syntax-ns#Person")));

			if($friend->ifp_type=="mbox"){
				if(substr($friend->ifps[0],0,7) != 'mailto:'){ 
					$friend->ifps[0] = "mailto:".$friend->ifps[0];
				}
				$foafData->getModel()->add(new Statement($bNode,new Resource("http://xmlns.com/foaf/0.1/mbox_sha1sum"),new Literal(sha1($friend->ifps[0]))));
				$successfulAdd = 1;
	
			} else if($friend->ifp_type=="mbox_sha1sum"){

				$foafData->getModel()->add(new Statement($bNode,new Resource("http://xmlns.com/foaf/0.1/mbox_sha1sum"),new Literal($friend->ifps[0])));
				$successfulAdd = 1;
	
			} else{
				$foafData->getModel()->add(new Statement($bNode,new Resource("http://xmlns.com/foaf/0.1/".$friend->ifp_type),new Resource($friend->ifps[0])));
				$successfulAdd = 1;
			}
                }

                return $successfulAdd;
        }


	
	
    /*predicateUri is only appropriate for simple ones (one triple only)*/
    public function KnowsField($foafData, $fullInstantiation = true) {
        
			/*TODO MISCHA dump test to check if empty */
			
        	$this->data['foafKnowsFields'] = array();
            $this->name = 'foafKnows';
            $this->label = 'Friends';
        	$this->data['foafKnowsFields']['displayLabel'] = $this->label;
            $this->data['foafKnowsFields']['name'] = $this->name;
            
           	if(!$fullInstantiation || !$foafData || !$foafData->getPrimaryTopic()){
				return;	
    		}
    		
			/*get all known ifps of this person by making use of the foaf.qdos.com KB*/
        	$ifps = $this->getTriangulatedIfps($foafData);              
        	
        	$knowsUserIfps = $this->getKnowsUserIfps($ifps,$foafData);
        	$userKnowsIfps = $this->getUserKnowsIfps($foafData);   

        	/*TODO: check that this works*/
        	$mutualFriendsIfps = $this->getMutualFriendsIfps($userKnowsIfps, $knowsUserIfps);
        	
        	$knowsUserDetails = $this->getDetailsFromIfps($knowsUserIfps); 
        	$userKnowsDetails = $this->getDetailsFromIfps($userKnowsIfps);   
        	$mutualFriendsDetails = $this->getDetailsFromIfps($mutualFriendsIfps);
        	
        	$this->data['foafKnowsFields']['mutualFriends'] = $mutualFriendsDetails;
        	$this->data['foafKnowsFields']['userKnows'] = $userKnowsDetails;
        	$this->data['foafKnowsFields']['knowsUser'] = $knowsUserDetails; 	
    }
    
    /*returns an array of arrays of ifps for each friend that the user knows who knows the user as well.  
	This function also cleans out these 'mutual friends' from the userknows and knowsuser arrays*/
    private function getMutualFriendsIfps(&$userKnowsIfps, &$knowsUserIfps){
    	if(!$userKnowsIfps || !$knowsUserIfps || empty($userKnowsIfps) || empty($knowsUserIfps)){
    		return array();
    	}
    	//to eventually return
    	$mutualFriends = array();
    	
    	//keys to clean afterwards
    	$userKnowsRemoveKeys = array();
    	$knowsUserRemoveKeys = array();
    	
    	/*user knows */
    	foreach($userKnowsIfps as $userKnowsURI => $userKnowsIFPs){
    		
    		foreach($knowsUserIfps as $knowsUserURI => $knowsUserIFPs){
    			
    			if($knowsUserURI == $userKnowsURI){
    				//if they have the same uri they must be the same person
    				$thisFriendsIFPs = array_unique(array_merge($userKnowsIFPs,$userKnowsIFPs));
    				
    				//add mutual friend
					array_push($mutualFriends,$thisFriendsIFPs);    	
					//do appropriate cleaning
					array_push($userKnowsRemoveKeys,$userKnowsURI);
					array_push($knowsUserRemoveKeys,$knowsUserURI);
					continue;			
    			} else {
    				//if they have a different uri they might still be the same person if at least one ifp matches
    				foreach($userKnowsIFPs as $userKnowsIFP){
    					foreach($knowsUserIFPs as $knowsUserIFP){
    						if($knowsUserIFP == $userKnowsIFP){
    							$thisFriendsIFPs = array_unique(array_merge($userKnowsIFPs,$userKnowsIFPs));
    							
    							//add  mutual friend
								array_push($mutualFriends,$thisFriendsIFPs); 
								//do appropriate cleaning
								array_push($userKnowsRemoveKeys,$userKnowsURI);
								array_push($knowsUserRemoveKeys,$knowsUserURI);   	
								break;
    						}
    					}
    				}
    			}

    		}
    		
    	}
    	
    	//clean out userknows/knows users that are now mutual friends
    	foreach($userKnowsRemoveKeys as $key){
    		unset($userKnowsIfps[$key]);
    	}
    	foreach($knowsUserRemoveKeys as $key){
    		unset($knowsUserIfps[$key]);
    	}
    	
    	return $mutualFriends;
    }
    
    
    
    /*array of the user details*/
    private function getDetailsFromIfps($userKnowsIfps){
    	//TODO: need to ask this query over the inMemModel as well
    		
    	 	//TODO: put this at the start of the details section so we can preserve the uri goodness here
      	 	 $userKnowsIfps = $this->uniqueIterativelyUsingIfps($userKnowsIfps);
        	//var_dump($userKnowsIfps);
    		//array to store all the details of people that the user knows
    		$userKnowsDetails = array();
        	
        	foreach($userKnowsIfps as $thisUri => $thisFriendsIfps){
				
        		$inquery = '';
	        	foreach($thisFriendsIfps as $ifp){
	                if(isset($ifp) && $ifp && substr($ifp,0,2)!="_:"){
	                    $inquery.=" ?ifp = ".$ifp."||";
	               }
	            }
	                
	        	/*build a query to get each friend's details*/
	        	$query = "PREFIX foaf: <http://xmlns.com/foaf/0.1/> PREFIX bio: <http://purl.org/vocab/bio/0.1/> PREFIX ya: <http://blogs.yandex.ru/schema/foaf/>";
	        	$query .= " SELECT DISTINCT ?img ?name ?nick WHERE {";
	        	$query .= " ?uri ?ifp_predicate ?ifp. 
	        				OPTIONAl{
	        					?uri foaf:name ?name .
	        				}
	        				OPTIONAL{
	        					?uri foaf:nick ?nick .
	        				}
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
        			/*only add stuff if there is a name or nick*/
        			if(isset($row['?name']) && $row['?name'] && $row['?name'] != 'NULL'){
        				$thisFriendDetails['name'] = sparql_strip($row['?name']);
        			} else if(isset($row['?nick']) && $row['?nick'] && $row['?nick'] != 'NULL'){
        				$thisFriendDetails['nick'] = sparql_strip($row['?nick']);
        			} else{
        				continue;
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
        			
        			if(!$this->isBnode($thisUri)){
        				//echo("this is not a bnode".$thisUri."\n");
        				$thisFriendDetails['uri'] = sparql_strip($thisUri);
        			}
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
    
    /*checks whether a uri is a bnode or not*/
    private function isBnode($thisUri){
    	
    	$thisUri = sparql_strip($thisUri);
    	if(substr($thisUri,0,2)=="_:"){
    		return true;
    	}
    	if(substr($thisUri,0,6)=="bnode:"){
    		return true;
    	}
    	if(substr($thisUri,0,5)=="bNode"){
    		return true;		
    	}
    	
    	return false;
    }
    
    /*get the ifps of the people who this person knows from the loaded foaf files, keyed on the persons uri*/
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
						}";
    	 //FILTER(?ifp_predicate = foaf:homepage || ?ifp_predicate = foaf:weblog || ?ifp_predicate = foaf:mbox  ||?ifp_predicate = foaf:mbox_sha1sum);
    	$potentialIfpArray = $foafData->getModel()->SparqlQuery($query);
    	$actualIfpArray = array();
    	
    	if(!empty($potentialIfpArray)){
    		foreach($potentialIfpArray as $row){
    			
				/* we need to key this on the uri of the person that the user knows*/
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
        foreach($actualIfpArray as $key => $row){
        	$thisFriendsIfps = IFPTriangulation::doIterativeIFPTriangulation($row);
        	
        	if(!isset($userKnowsIfps[$key])){
        		$userKnowsIfps[$key] = array();	
        	}
        	
        	$userKnowsIfps[$key] = array_merge($userKnowsIfps[$key],$thisFriendsIfps);
           	
        }
        
        
    	return $userKnowsIfps;
    }
    
    /*combines all the elements which share at least one ifp*/
    //XXX is this correct and fool proof or does it need to be done iteratively.
    private function uniqueIterativelyUsingIfps($userKnowsIfps){
 		//echo("LENGTH:".sizeOf($userKnowsIfps)."\n");
    	
    	$this->uniqueArrayUsingIfps($userKnowsIfps);
   		//echo("LENGTHaft:".sizeOf($userKnowsIfps)."\n");
    //	var_dump($userKnowsIfps);
    	//TODO we can do better than that!
    	return  $userKnowsIfps;
    	
    }
    
    //doesn't work! XXX
    private function uniqueArrayUsingIfps(&$userKnowsIfps){
    	
    	$ret = array();//the array we'
    	$stillWorking = false;//whether or not we're still making changes to the array
		$userKnowsIfps2 = $userKnowsIfps;

    	foreach($userKnowsIfps as $ifps){
    		
    		//the array we'll grow and eventually put in the array to return
    		$snowballedIfps = $ifps;
    		$addedToSnowball = false;
    		
    		//loop through the array seeing if this element has anything in common with any of the other elements.  
    		//Keep doing this until we can't pick anything more up.
    		//while(!$addedToSnowball){
	    		foreach($userKnowsIfps2 as $ifps2){
	    			
	    			$intersectionArray = array_intersect($snowballedIfps,$ifps2);
	
	    			/*if there are any ifps in common with this one, the others should be added to the 'snowball' (if there are any)*/
	    			if(!empty($intersectionArray) && sizeOf($intersectionArray) != sizeOf($ifps2)){
	    				$snowballedIfps = array_merge($snowballedIfps,$ifps2);
	    				$addedToSnowball = true;
	    			}
	
	    		}
    		//}
    		
    		/*if it isn't already there, shove the ifp list we've got into the array to return*/
	    	$alreadyInRet = false;
	    	foreach($ret as $retElement){
    			$intersectArraySnowball = array_intersect($snowballedIfps,$retElement);
    			if(!empty($intersectArraySnowball)){
    				$alreadyInRet = true;
    			}
	    	}
    		if(!$alreadyInRet){
    			array_push($ret,array_unique($snowballedIfps));
    		}
    	}
    	$userKnowsIfps = $ret;
    	return $stillWorking;
    }
    
    public function nameSorter($a,$b){
    	$ret = strcmp($a['name'],$b['name']);
    	if($ret == 0){
    		return 1;
    	}

    	return $ret;
    }
    
    
    /*get ifps, keyed on the uri, of people who say they know the user*/
    private function getKnowsUserIfps($ifps,$foafData){
    	$inquery='';
        		
        		foreach($ifps as $ifp){
                        if(isset($ifp) && $ifp && substr($ifp,0,2)!="_:"){
                                $inquery.=" ?ifp = ".$ifp." || ";
                                //look for sha1s both done the correct way and the incorrect way too
                                if(substr($ifp,0,8)=="<mailto:"){
                                	$inquery.=" ?ifp = \"".sha1(sparql_strip($ifp))."\" ||";
                                	$inquery.=" ?ifp = \"".sha1(substr(sparql_strip($ifp),7))."\" ||";
                                }
                        }
                }
                if($inquery!=""){
                        $inquery="
						PREFIX foaf: <http://xmlns.com/foaf/0.1/> PREFIX bio: <http://purl.org/vocab/bio/0.1/> PREFIX ya: <http://blogs.yandex.ru/schema/foaf/>
						SELECT DISTINCT ?infriend ?infriend_predicate ?infriend_ifp
						WHERE {
						 	OPTIONAL{
							  	?uri ?ifp_predicate ?ifp .
						 		?infriend foaf:knows ?uri .						 	
							  	?infriend ?infriend_predicate ?infriend_ifp .
							  	FILTER(?ifp_predicate = foaf:homepage || ?ifp_predicate = foaf:weblog || ?ifp_predicate = foaf:mbox  || ?ifp_predicate = foaf:mbox_sha1sum) .
								FILTER(?infriend_predicate = foaf:homepage || ?infriend_predicate = foaf:weblog || ?infriend_predicate = foaf:mbox  || ?infriend_predicate = foaf:mbox_sha1sum) .				
						    	FILTER(".substr($inquery,0,-2).")
						    }						
                		}";
                        //TODO: continue where I left off.  I just added the optional and I want
                        //to use the infriend uri to get some of the details in order to make sure libby knows dan as she should
                }//need to allow people who just know by uri to enter stuff here
                
				
                $inquery_array = sparql_query(FOAF_EP, $inquery);
            	$knows = array();
			
                 foreach ($inquery_array as $row) {
                    $value = sparql_strip($row['?infriend']);
                 	if (substr($value, 0, 2) == '_:'){
                    	$value = 'bnode:'.substr($value, 2);
                 	}
                    if(!isset($knows[$value])){
                   		$knows[$value] = array();
                    }
                    array_push($knows[$value],$row['?infriend_ifp']);
                 }	
                      
              
    			/*loop through the people who know them and triangulate their IFPs*/
                 
        		$knowsUserIfps = array();
        		foreach($knows as $key => $values){
        			$thisIfpArray = IFPTriangulation::doIterativeIFPTriangulation($values);
        			$knowsUserIfps[$key] = $thisIfpArray;
        		}
        		
        		//echo("KNOWS USER IFPS:");
        		//var_dump($knowsUserIfps);
        		

                return $knowsUserIfps;
    }
    
    /*queries the loaded foaf file and queries foaf.qdos.com then triangulates the two*/
    private function getTriangulatedIfps($foafData){
    	
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
        // decho("Initial IFP Array");
		//var_dump($initialIfpArray);
       	/*triangulate the ifps to grow the array*/
        $ifps = IFPTriangulation::doIterativeIFPTriangulation($initialIfpArray);
        
		return $ifps;
    }
	
    /*saves the values created by the editor in value... as encoded in json. */
    public function saveToModel(&$foafData, $value) {

	//this doesn't do anything since the session is kept up to date via the friend controller
	$this->view->isSuccess = 1;
	return;
    }
    
    
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

	/*gets an array of IFPS from a foaf knows triple such as <usersuri> foaf:knows <somedudesuriorbnodehere>*/
    public function getIFPSFromFoafKnows($triple,$foafData){
    	
		/*get hold of all the ifps in one array*/
		$friendsIFPS = array();
		//XXX: simpler and more efficient with sparql perhaps?
		$foundIFPFields1 = $foafData->getModel()->find($triple->obj,new Resource('http://xmlns.com/foaf/0.1/mbox'),NULL);
		$foundIFPFields2 = $foafData->getModel()->find($triple->obj,new Resource('http://xmlns.com/foaf/0.1/mbox_sha1sum'),NULL);
		$foundIFPFields3 = $foafData->getModel()->find($triple->obj,new Resource('http://xmlns.com/foaf/0.1/homepage'),NULL);
		$foundIFPFields4 = $foafData->getModel()->find($triple->obj,new Resource('http://xmlns.com/foaf/0.1/weblog'),NULL);
			
		if($foundIFPFields1 && property_exists($foundIFPFields1,'triples') 
			&& $foundIFPFields1->triples && !empty($foundIFPFields1->triples)){
			$friendsIFPS = array_merge($friendsIFPS,$foundIFPFields1->triples);
		}
		if($foundIFPFields2 && property_exists($foundIFPFields2,'triples') 
			&& $foundIFPFields2->triples && !empty($foundIFPFields2->triples)){
			$friendsIFPS = array_merge($friendsIFPS,$foundIFPFields2->triples);
		}
		if($foundIFPFields3 && property_exists($foundIFPFields3,'triples') 
			&& $foundIFPFields3->triples && !empty($foundIFPFields3->triples)){
			$friendsIFPS = array_merge($friendsIFPS,$foundIFPFields3->triples);
		}
		if($foundIFPFields4 && property_exists($foundIFPFields4,'triples') 
			&& $foundIFPFields4->triples && !empty($foundIFPFields4->triples)){
			$friendsIFPS = array_merge($friendsIFPS,$foundIFPFields4->triples);
		}	

					
		return $friendsIFPS;		
    }
    
    
	/*gets a url from the ifp that is passed in, if it isn't a bnode*/
	public function getURIFromIFP($ifp){
		
		echo("This is the IFP: ".$ifp."\n");
		//maybe flesh this out FIXME
	}
}
