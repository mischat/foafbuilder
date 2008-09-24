<?php
/*FIXME: combine with the other geo fields in a similar way to BirthdayField.*/
require_once 'Field.php';
/*class to represent one item e.g. foafName or bioBirthday... not the same as one triple*/
class GeoLatLongField extends Field{
	
	/*predicateUri is only appropriate for simple ones (one triple only)*/
	public function GeoLatLongField(){
		$this->label ='Location';
		$this->name = 'geoLatLong';
		$this->queryBit = '?x foaf:based_near ?l . ?l geo:lat_long ?geoLatLong';
		$this->type = 'literal';
	}

	public function getPredicateUri(){
		return $this->predicateUri;
	}
	public function setPredicateUri($predicateUri){
		$this->predicateUri = $predicateUri;
	}
	/*saves the appropriate triples in the model at the appropriate index and replace them with $value*/
	public function saveToModel(&$foafData, $value){
		echo("SAVING LATLONG");
		require_once 'SimpleField.php';
		require_once 'FieldNames.php';

		/*TODO: Probably inefficient, possibly a load method is needed to store the event uri in this object?*/
		$query = " 	PREFIX foaf: <http://xmlns.com/foaf/0.1/>
        			PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
        			PREFIX bio: <http://purl.org/vocab/bio/0.1/>
            		PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
					SELECT ?l ?x ?geoLatLong WHERE {?z foaf:primaryTopic ?x . ".$this->getQueryBit().".}";
		
		$results = $foafData->getModel()->sparqlQuery($query);
		
		/*remove the statement if they've defined their location in this way*/
		if(isset($results[0])){
			$remove_statement = new Statement(new BlankNode($results[0]['?l']->uri), new Resource("http://www.w3.org/2003/01/geo/wgs84_pos#lat_long"), new Literal($results[0]['?geoLatLong']->label));
			$foafData->getModel()->remove($remove_statement);
			/*and add another one if the value is set*/
			if($value){
				$add_statement = new Statement(new BlankNode($results[0]['?l']->uri), new Resource("http://www.w3.org/2003/01/geo/wgs84_pos#lat_long"), new Literal($value));
				$foafData->getModel()->add($add_statement);
			}
		} 
	}
}
?>