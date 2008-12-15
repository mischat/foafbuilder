<?php
require_once 'Zend/Controller/Action.php';
require_once("helpers/JSON.php");
require_once("helpers/sparql.php");
require_once("helpers/settings.php");
require_once('helpers/IFPTriangulation.class.php');
require_once('helpers/URITriangulation.class.php');
require_once("helpers/security_utils.php");
require_once('helpers/image-cache.php');

//XXX could turn this into a service or implement this way of searching in foaf.qdos.com
class FriendController extends Zend_Controller_Action {
	
	
    public function init() {
        $this->view->baseUrl = $this->_request->getBaseUrl();
    }
	
    /*remove the friend with the given IFPs from the in memory model*/
    public function removeFriendAction() {
      $this->view->isSuccess = 0;
        require_once 'FoafData.php';
        $friendString = @$_POST['friend'];
	if (!check_key('post')) {
		error_log("POST hijack attempt friend extractor");
		exit();
	}
        
        if ($friendString) {
            $foafData = FoafData::getFromSession();	
            
            if($foafData) {
            	
                $json = new Services_JSON();
        		$friend = $json->decode(stripslashes($friendString));
               	
				/*instantiate a knowsField*/
               	$knowsField = new KnowsField($foafData,false);
               	
               	/*remove the appropriate friend*/
               	$successfulRemove = $knowsField->removeFriend($friend,$foafData);

               	if($successfulRemove){
                	$foafData->putInSession();
                	$this->view->isSuccess = 1;
               	}
            } else {
                echo("there aint anything in the session");
            }
        }
    }
    
    /*add a friend with the given IFPs to the in memory model*/
    public function addFriendAction() {
      $this->view->isSuccess = 0;
        require_once 'FoafData.php';
        $friendString = @$_POST['friend'];
	if (!check_key('post')) {
		error_log("POST hijack attempt in add friend");
		exit();
	}
        
        if ($friendString) {
	 	//put friends stuff in the public bit
            $foafData = FoafData::getFromSession(true);	
            
            if($foafData) {
            	
                $json = new Services_JSON();
        		$friend = $json->decode(stripslashes($friendString));
               		
            	/*instantiate a knowsField*/
               	$knowsField = new KnowsField($foafData,false);
               	
               	/*add the appropriate friend*/
               	$successfulAdd = $knowsField->addFriend($friend,$foafData);
               
		if($successfulAdd){
                	$foafData->putInSession();
                	$this->view->isSuccess = 1;
               	}
            } else {
                echo("there aint anything in the session");
            }
        }
    }
    
    /*does a search for someone using ifp/uri triangulation*/
    public function findFriendAction() {
    	
    	/*the actual json that we spit out*/
    	$this->view->results = array();
    	
    	if(!isset($_GET['uri']) || !$_GET['uri']){
    		return;
    	}
    	
    	/*does a search for a friend*/
    	$ifp_or_uri_array = $this->multiTryURL($_GET['uri']);
    	$ifp_array = $ifp_or_uri_array;
    	
    	/*if the ifp passed is a uri, we need to get the ifps from it*/
    	foreach($ifp_or_uri_array as $ifp_or_uri){
    		$new_ifp_array = URITriangulation::getIFPsFromURI($ifp_or_uri);
    		if($new_ifp_array){
    			array_merge($ifp_array,$new_ifp_array);
    		}
    	}
  
    	/*now we have an array of ifps (and possibly a uri)*/
    	$ifp_array = IFPTriangulation::doIterativeIFPTriangulation($ifp_array);

    	/*build up a filter for the query*/
    	$filter = 'FILTER(';
    	foreach($ifp_array as $ifp){
    		$filter .= "?ifp = ".$ifp." || ";
    	}
    	$filter = substr($filter,0,-3).")";
    	
    	
		/*now we need to use these IFPs to get the details*/
    	$query = "PREFIX foaf: <http://xmlns.com/foaf/0.1/>
					SELECT DISTINCT ?name ?depiction ?img ?person WHERE {
					   ?person ?ifp_type ?ifp .
					   ?person foaf:name ?name .
					   OPTIONAL{
					   		?person foaf:img ?img
					   }
					   OPTIONAL{
					   		?person foaf:depiction ?depiction
					   }
					   FILTER(?ifp_type = foaf:weblog 
					    	 	|| ?ifp_type = foaf:homepage 
					    	 	|| ?ifp_type = foaf:mbox_sha1sum
					    	 	|| ?ifp_type = foaf:mbox) .".$filter."}";
    	
    	
    	$results = sparql_query(FOAF_EP,$query);
    	$images = array();
    	
    	//loop through the results we only want one image
    	foreach($results as $row){
    		if(isset($row['?img']) && $row['?img'] && $row['?img'] != 'NULL'){
    			array_push($images,sparql_strip($row['?img']));
    		}
    		if(isset($row['?depiction']) && $row['?depiction'] && $row['?depiction'] != 'NULL'){
    			array_push($images,sparql_strip($row['?depiction']));
    		}
    	}
	$stripped_ifps = array();
	foreach($ifp_array as $ifp){
		array_push($stripped_ifps,sparql_strip($ifp));
	}
	$cachedImageArray = cache_get($stripped_ifps,$images);
	$this->view->results['img'] = $cachedImageArray[0];
    	
    	//loop through the results we only want one name
    	foreach($results as $row){
    	    if(isset($row['?name']) && $row['?name'] && $row['?name'] != 'NULL'){
    			$this->view->results['name'] = sparql_strip($row['?name']);
    			break;
    		}
    	}

	//we only want one uri
	//FIXME: this should only use the uri from the ifp given.
	foreach($results as $row){
		//check that the uri is not a bnode, which would be useless
		if(isset($row['?person']) && $row['?person'] && $row['?person'] != 'NULL' && substr($row['?person'],0,2)!="_:"){
                        $this->view->results['uri'] = sparql_strip($row['?person']);
                        break;
                }
	}
    	
    	//the originally passed uri/ifp
    	$this->view->results['ifps'] = array($_GET['uri']);
    }
    
    private function multiTryURL($val){
    	$ifps=array();

        //try just straight up
        $ifps = $this->tryURL($val);

        //try adding http:// at the start and a slash at the end
        if(!isset($ifps[0])){
                $ifps = $this->tryURL("http://".$val."/");
        }

        //try adding a slash
        if(!isset($ifps[0])){
                $ifps = $this->tryURL($val."/");
        }
        //try removing the last character
        if(!isset($ifps[0])){
                $ifps = $this->tryURL(substr($val,0,-1));
        }
        //try adding http:// at the start
        if(!isset($ifps[0])){
                $ifps = $this->tryURL("http://".$val);
        }

        //try adding www. at the start
        if(!isset($ifps[0])){
                $ifps = $this->tryURL("http://www.".$val."/");
        }
    	
        return $ifps;
    	
    }
    
    //XXX: nicked from foaf.qdos.com, this should be used in a more sensible way 
	private function tryURL($val){
		
    	$ifp_array = array();

        if (preg_match("/(mailto:)?([^\s]+@[a-zA-Z0-9\.-]+)/", $val, $matches)) {
        	$email = $matches[2];
			 $this->view->results['ifp_type'] = 'mbox';
        	
            if(IFPTriangulation::validateIFP("\"".sha1("mailto:".$email)."\"")){
             	array_push($ifp_array,"<mailto:".$email.">");
                array_push($ifp_array,"\"".sha1("mailto:".$email)."\"");
                $ifp_array = IFPTriangulation::doIterativeIFPTriangulation($ifp_array);
             }
         } else if (preg_match("/(((http|ftp|mailto)+:\\/\\/)[a-zA-Z0-9\.-]+.*)/", $val, $matches)) {
         	  $uri = $matches[1];
              if(IFPTriangulation::validateIFP("<".$uri.">")){
              	array_push($ifp_array,"<".$uri.">");
                $ifp_array = IFPTriangulation::doIterativeIFPTriangulation($ifp_array);
              	$this->view->results['ifp_type'] = 'homepage';//TODO: this could be a weblog... how do we know? Possibly from doing another query.
              }
         } else if (preg_match("/^[a-f0-9]{40}$/i", $val)) {
               if(IFPTriangulation::validateIFP("\"".$val."\"")){
               	array_push($ifp_array,"\"".$val."\"");
                $ifp_array = IFPTriangulation::doIterativeIFPTriangulation($ifp_array);
                $this->view->results['ifp_type'] = 'mbox_sha1sum'; 
               }
        }
        return $ifp_array;

	}
    
	
}
