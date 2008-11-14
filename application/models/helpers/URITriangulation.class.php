<?php
require_once("settings.php");
require_once("sparql.php");
require_once('IFPTriangulation.class.php');

class URITriangulation {

	//Get all the URI
	static function getOwlSameAs($uri) {

		$uri_query = "PREFIX foaf: <http://xmlns.com/foaf/0.1/>
			      PREFIX owl: <http://www.w3.org/2002/07/owl#>
					SELECT DISTINCT ?uris WHERE { 
					   <".$uri."> owl:sameAs ?uris }";
	
		//I guess I could add some owlsameAs in here ...
		$results = sparql_query(FOAF_EP,$uri_query);
		$cleanres= array();
		foreach($results as $result) {
			array_push($cleanres,$result['?uris']);
		}
		array_push($cleanres,"<".$uri.">");

		return $cleanres;
	}
	

	//Get all the URI's IFPs
	static function getIFPsFromURI($uri) {

		$query="PREFIX foaf: <http://xmlns.com/foaf/0.1/>
			SELECT DISTINCT ?obj WHERE {
			   ".$uri." ?pred ?obj
			    .FILTER(?pred = foaf:weblog 
				 || ?pred = foaf:homepage 
				 || ?pred = foaf:mbox_sha1sum
				 || ?pred = foaf:mbox)}limit 200";

		$res = sparql_query(FOAF_EP,$query);
		$cleanres= array();
		
		foreach($res as $result) {
			if(isset($result['?obj']) && $result['?obj']){
				array_push($cleanres,@$result['?obj']);
			}
		}

		if (!empty($cleanres)) {
			return $cleanres;
		} else {
			return false;
		}
	}


	//checks whether an ifp has some data associated with it 
	static function validateURI($uri) {
		//if(substr($uri,0,2)!="_:"){
		if(preg_match('/^<https?:\/\/[^>]+>/',$uri)) {
			error_log("THis works now ...");
			$query="PREFIX foaf: <http://xmlns.com/foaf/0.1/> 
				PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
				SELECT DISTINCT ?type WHERE {
						   ".$uri." rdf:type ?type
						    .FILTER(?type = foaf:Person)}limit 200";

			$res = sparql_query(FOAF_EP,$query);

			if (isset($res[0]['?type']) && $res[0]['?type']) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	static function doIterativeURITriangulation($uri_array) {
		$uri_array_cumulative=$uri_array;
		foreach ($uri_array as $person) {
			$res = URITriangulation::doURITriangulation($person);	
			$uri_array_cumulative = array_merge($res,$uri_array_cumulative);
		}
		$uri_array_cumulative = array_unique($uri_array_cumulative);
		$uri_array_cumulative = array_map("bnode_transform", $uri_array_cumulative);
		$uri_array_cumulative = array_unique($uri_array_cumulative);

		return $uri_array_cumulative;
	}

	static function doURITriangulation($uri) {	
		
		$uri_query = "PREFIX foaf: <http://xmlns.com/foaf/0.1/>
			SELECT DISTINCT ?known_by WHERE {?known_by foaf:knows ".$uri."}";

		$results = sparql_query(FOAF_EP,$uri_query);
		$res = array();
		foreach($results as $result) {
			array_push($res,@$result['?known_by']);
		}

		return $res;
	}
}


?>
