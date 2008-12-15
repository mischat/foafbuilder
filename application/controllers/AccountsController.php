<?php
require_once 'Zend/Controller/Action.php';
require_once("helpers/JSON.php");
require_once("helpers/sparql.php");
require_once("helpers/settings.php");
require_once("helpers/Utils.php");
require_once("helpers/security_utils.php");

class AccountsController extends Zend_Controller_Action {
	
    public function init() {
        $this->view->baseUrl = $this->_request->getBaseUrl();
    }
	
    
    /*converts a userame to a URI*/
	//converts usernames into uris
	public function usernameToUriAction(){

		if (!check_key('post')) {
			error_log("POST hijack attempt load extractor ");
			exit();
		}
		
		//build a query from the names of the post variables to get the appropriate patterns
		$query = "PREFIX gs: <http://qdos.com/schema#> 
                        PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#> 
                        SELECT ?serv ?patt WHERE 
                        { ?serv rdfs:subPropertyOf gs:serviceProperty ; gs:canonicalUriPattern ?patt .
                        FILTER(";
       	 	//we only want to get canonical patterns of things that are passed in
		foreach($_POST as $key => $value){
			$query.='?serv = <http://qdos.com/schema#'.$key.'> || ';			
		}
		
		
		$query = substr($query,0,-3).')}';
		
		
		echo($query);
		
		//execute the query to get the patterns
		$res = sparql_query(QDOS_EP,$query);

                if(!$res || empty($res)){
                        return;
                }

		//use this to contain the patterns for all of the usernames passed in
                $uris = array();
          
                foreach($res as $row){
                        if(!isset($row['?serv']) || !$row['?serv']){
                                continue;
                        } 
                        if(!isset($row['?patt']) || !$row['?patt']){
                                continue;
                        }
                        //create the various uris
			foreach($_POST as $key => $value){
                        	if(sparql_strip($row['?serv']) == 'http://qdos.com/schema#'.$key){
					$uris[$key] = str_replace('@USER@',$value,sparql_strip($row['?patt']));
				}
			}
		}

                $this->view->results = $uris;
	}
	
	/*get all of the various types of account*/
	public function getAllAccountTypesAction(){
		if (!check_key('post')) {
			error_log("POST hijack attempt load extractor ");
			exit();
		}
		
		/*query to get account homepages and names*/
		$query = "PREFIX gs: <http://qdos.com/schema#> 
    					PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#> 
        				SELECT ?page ?name WHERE {	
        					?serv rdfs:subPropertyOf gs:serviceProperty
        				    	?serv gs:serviceHomepage ?page .
        					?serv gs:serviceName ?name .
						} LIMIT 200";
		
		$res = sparql_query(QDOS_EP,$query);		

		/*loop through the results and create ones that are easier to work with in javascript*/
		$retArray = array();
		foreach($res as $row){
			
			$thisArray = array();
			
			if(!isset($row['?page']) || !isset($row['?name'])){
				continue;
			}	
			
			$thisArray['name'] = sparql_strip($row['?name']);
			$thisArray['page'] = sparql_strip($row['?page']);
			array_push($retArray,$thisArray);
		}
		
		/*Add qdos too*/
		/*
		$qdosArray = array();
		$qdosArray['name'] = 'QDOS profile';
		$qdosArray['page'] = 'http://qdos.com';
		array_push($retArray,$qdosArray);
		*/
		/*Sort alphabetically*/
		//asort($retArray);
		$retArray = array(

  array(

    "name"=>

    "Amazon Reviews",

    "page"=>

    "http://www.amazon.com/"

  )

,
  array(

    "name"=>

    "Bebo",

    "page"=>

    "http://bebo.com/"

  )

,
  array(

    "name"=>

     "Blogger",

    "page"=>

   "http://www.blogger.com/"

  )
,

  array(

    "name"=>

    "Codeplex",

    "page"=>

    "http://www.codeplex.com"

  )

,
  array(

    "name"=>

    "Digg",

    "page"=>

   "http://digg.com/"

  )

  ,
  array(

    "name"=>

    "Facebook",

    "page"=>

   "http://www.facebook.com"

  )

  ,

  array(

    "name"=>

    "Flickr",

    "page"=>

    "http://www.flickr.com/"

  )

  ,

  array(

    "name"=>

    "Fotolog",

    "page"=>

    "http://www0.fotolog.com/"

  )

  ,

  array(

    "name"=>

    "IMDB User",

    "page"=>

    "http://www.imdb.com/"

  )

  ,

  array(

    "name"=>

    "Last.fm User",

    "page"=>

    "http://www.last.fm/"

  )

  ,

  array(

    "name"=>

    "LinkedIn",

    "page"=>

    "http://www.linkedin.com/"

  )

  ,

  array(

    "name"=>

    "Live Journal",

    "page"=>

    "http://livejournal.com/"

  )

  ,

  array(

    "name"=>

    "MTV Artist page",

    "page"=>

     "http://www.mtv.com/music/"

  )

  ,

  array(

    "name"=>

    "MySpace",

    "page"=>

   "http://www.myspace.com/"

  )

  ,

  array(

    "name"=>

    "Reddit",

    "page"=>

    "http://reddit.com/"

  )

  ,

  array(

    "name"=>

    "Ringo",

    "page"=>

    "http://www.ringo.com/"

  )

  ,

  array(

    "name"=>

    "Slashdot",

    "page"=>

    "http://slashdot.org/"

  )

  ,

  array(

    "name"=>

    "Sourceforge",

    "page"=>

    "http://sourceforge.net/"

  )

  ,

  array(

    "name"=>

    "Spock",

    "page"=>

    "http://www.spock.com/"

  )

  ,

  array(

    "name"=>

    "TripAdvisor",

    "page"=>

    "http://www.tripadvisor.com/"

  )

  ,

  array(

    "name"=>

    "Twitter Userpage",

    "page"=>

    "http://twitter.com"

  )

  ,

  array(

    "name"=>

    "Vox",

    "page"=>

    "http://www.vox.com/"

  )

  ,

  array(

    "name"=>

    "Wikipedia User page",

    "page"=>

    "http://wikipedia.org"

  )


  ,

  array(

    "name"=>

    "Windows Live Spaces",

    "page"=>

   "http://home.services.spaces.live.com/"

  )

  ,

  array(

    "name"=>

    "Wink",

    "page"=>

    "http://wink.com/"

  )

  ,

  array(

    "name"=>

    "Xanga",

    "page"=>

    "http://www.xanga.com/"

  )

  ,
  
  array(

    "name"=>

    "YouTube",

    "page"=>

    "http://youtube.com/"

  )

  ,

  array(

    "name"=>

    "Zoominfo",

    "page"=>

    "http://www.zoominfo.com/"

  )

  ,

  array(

    "name"=>

    "del.icio.us User Page",

    "page"=>

    "http://del.icio.us"

  )

  ,

  array(

    "name"=>

    "eBay UK",

    "page"=>

    "http://www.ebay.co.uk/"

  )

  ,

  array(

    "name"=>

    "hi5 User Page",

    "page"=>

    "http://www.hi5.com"

  )

  ,

  array(
	"name" =>
	"QDOS User Page",
	"page"=>
	"http://qdos.com"

 )	
);
	asort($retArray);	
	//	var_dump($retArray);
		/*add the results to the view*/
		$this->view->results = $retArray;
	}
}
	
