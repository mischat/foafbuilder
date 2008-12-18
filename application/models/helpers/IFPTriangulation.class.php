<?php
require_once("settings.php");
require_once("sparql.php");

//FIXME: ought to use the one of these that is in the foaf.qdos.com code

function bnode_transform($lit) {
	return preg_replace('(^_:(.*))', '<bnode:$1>', $lit);
}

class IFPTriangulation {
	
	//checks whether an ifp has some data associated with it 
	static function validateIFP($ifp){
		
		if(substr($ifp,0,2)!="_:"){
	
			$query="PREFIX foaf: <http://xmlns.com/foaf/0.1/>
						SELECT DISTINCT ?pred WHERE {
						   ?person ?pred ".$ifp."
						    .FILTER(?pred = foaf:weblog 
						    	 || ?pred = foaf:homepage 
						    	 || ?pred = foaf:mbox_sha1sum
						    	 || ?pred = foaf:mbox)}limit 200";



			$res = sparql_query(FOAF_EP,$query);

			if(isset($res[0]['?pred']) && $res[0]['?pred']){

				return true;
				
			}
			else{
				return false;
			}
		}
		else{
			return false;
		}
	
	}
	static function doIterativeIFPTriangulation($ifp_array){
		$i=0;
		$ifp_array_cumulative=$ifp_array;
		
		while($ifp_array && $i <5){
			$ifp_array = IFPTriangulation::doIFPTriangulation($ifp_array);	
			$ifp_array_cumulative = array_merge($ifp_array,$ifp_array_cumulative);
			$i++;
		}
		
		$ifp_array_cumulative = array_unique($ifp_array_cumulative);
		$ifp_array_cumulative = array_map("bnode_transform", $ifp_array_cumulative);
		$ifp_array_cumulative = array_unique($ifp_array_cumulative);
		return $ifp_array_cumulative;
	}

	static function doIFPTriangulation($ifp_array){	

		$ifp_array =  array_unique  ( $ifp_array);
	


		
		$ifp_query = "PREFIX foaf: <http://xmlns.com/foaf/0.1/>
					SELECT DISTINCT ?ifp_wanted WHERE {
					   ?person ?predicate_already_have ?ifp_already_have .
					   ?person ?predicate_wanted ?ifp_wanted
					    .FILTER(?predicate_already_have = foaf:weblog 
					    	 || ?predicate_already_have = foaf:homepage 
					    	 || ?predicate_already_have = foaf:mbox_sha1sum
					    	 || ?predicate_already_have = foaf:mbox)
					    FILTER(?predicate_wanted = foaf:weblog 
					         || ?predicate_wanted = foaf:homepage 
					         || ?predicate_wanted = foaf:mbox_sha1sum
					         || ?predicate_wanted = foaf:mbox)";
		
		
		
		$already_have_filter="";
		$want_filter="";
		foreach($ifp_array as $ifp){
	
			//weed out duff/empty results or dangerous bnodes
			if( isset($ifp)
				&& $ifp
				&& $ifp != "NULL" 
				&& substr($ifp,0,2)!="_:"){
					if ($ifp != "<mailto:>" && $ifp != "da39a3ee5e6b4b0d3255bfef95601890afd80709" && $ifp != "08445a31a78661b5c746feff39a9db6e4e2cc5cf" && $ifp != "" & $ifp != "20cb76cb42b39df43cb616fffdda22dbb5ebba32") {
						$want_filter.=" ?ifp_wanted != ".$ifp." &&";
						$already_have_filter.=" ?ifp_already_have = ".$ifp." ||";			
					}
			}
		}
		

		if($already_have_filter != ""){
			$ifp_query.="FILTER(".substr($already_have_filter,0,-2).")";
		}
		else{
			$ifp_query="";
		}
		if($want_filter != ""){
			$ifp_query.="FILTER(".substr($want_filter,0,-2).")";
		}
		else{
			$ifp_query="";
		}
		if($ifp_query!=""){
			$ifp_query.="}";
		}
		$results = sparql_query(FOAF_EP,$ifp_query);
		$return_array = array();
		
		foreach($results as $res){		
			array_push($return_array,@$res['?ifp_wanted']);
		}
		
		return $return_array;
	}
}


?>
