<?php
/*FIXME: combine with the other geo fields in a similar way to BirthdayField. 
 *we also need to create a based_near triple if they don't have one*/
require_once 'Field.php';

/*class to represent one item e.g. foafName or bioBirthday... not the same as one triple*/
class GeoLongField extends Field{
	
	/*predicateUri is only appropriate for simple ones (one triple only)*/
	public function GeoLongField(){
		$this->label ='Location';
		$this->name = 'geoLong';
		$this->queryBit = '?x foaf:based_near ?l . ?l geo:long ?geoLong';
		$this->type = 'literal';
	}

	public function getPredicateUri(){
		return $this->predicateUri;
	}
	public function setPredicateUri($predicateUri){
		$this->predicateUri = $predicateUri;
	}
	/*saves the appropriate triples in the model at the appropriate index and replace them with $value*/
	/*FIXME: perhaps the sparql bit at the start of this class is overkill? Also, need to add nearest airport stuff.*/
	public function saveToModel(&$foafData, $value){
		echo("SAVING long");
		require_once 'SimpleField.php';
		require_once 'FieldNames.php';

		/*TODO: Probably inefficient, possibly a load method is needed to store the event uri in this object?*/
			$query = " 	PREFIX foaf: <http://xmlns.com/foaf/0.1/>
        			PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
        			PREFIX bio: <http://purl.org/vocab/bio/0.1/>
            		PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
					SELECT ?l ?x ?geoLong WHERE {?z foaf:primaryTopic ?x . ".$this->getQueryBit().".}";
		
		$results = $foafData->getModel()->sparqlQuery($query);
		
		
		if($results){
			/*remove the statement if they've defined their location in this way*/
			$remove_statement = new Statement(new BlankNode($results[0]['?l']->uri), new Resource("http://www.w3.org/2003/01/geo/wgs84_pos#long"), new Literal($results[0]['?geoLong']->label));
			$foafData->getModel()->remove($remove_statement);
			
			/*and add another one if the value is set.*/
			if($value){
				$add_statement = new Statement(new BlankNode($results[0]['?l']->uri), new Resource("http://www.w3.org/2003/01/geo/wgs84_pos#long"), new Literal($value));
				$foafData->getModel()->add($add_statement);
			}
		} else {
			if($value){
				/*if there are no results, check whethere there is a based near triple*/ 
				$found_model = $foafData->getModel()->find(new Resource($foafData->getPrimaryTopic()), new Resource("http://xmlns.com/foaf/0.1/based_near"), NULL);
				
				if($found_model->triples[0]){
					/*there is at least one based near triple, so loop through them adding the required long triple*/
					foreach($found_model->triples as $triple){
						$add_statement = new Statement($triple->obj, new Resource("http://www.w3.org/2003/01/geo/wgs84_pos#long"),new Literal($value));
						$foafData->getModel()->add($add_statement);
					}
				} else {
					/*there is no based near triple, so add it and add the required long triple*/
					$new_bnode = new BlankNode($foafData->getModel());
					$add_statement = new Statement(new Resource($foafData->getPrimaryTopic()), new Resource("http://xmlns.com/foaf/0.1/based_near"),$new_bnode);
					$foafData->getModel()->add($add_statement);
					$add_statement = new Statement($new_bnode, new Resource("http://www.w3.org/2003/01/geo/wgs84_pos#long"),new Literal($value));
				}
			}
		}
	}
}
?>