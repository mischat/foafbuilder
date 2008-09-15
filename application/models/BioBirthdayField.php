<?php
require_once 'Field.php';

/*class to represent one item e.g. foafName or bioBirthday... not the same as one triple*/
class BioBirthdayField extends Field{
	
	private $predicateUri;
	
	/*predicateUri is only appropriate for simple ones (one triple only)*/
	public function BioBirthdayField($name, $queryBit, $type, $predicateUri = NULL){
		$this->name = $name;
		$this->queryBit = $queryBit;
		$this->type = $type;
		if($predicateUri){
			$this->predicateUri = $predicateUri;		
		}
	}

	public function getPredicateUri(){
		return $this->predicateUri;
	}
	public function setPredicateUri($predicateUri){
		$this->predicateUri = $predicateUri;
	}
	/*saves the appropriate triples in the model at the appropriate index and replace them with $value*/
	public function saveToModel(&$foafData, $value, $index){
echo("dsadsa");
		require_once 'SimpleField.php';
		require_once 'FieldNames.php';

		/*TODO: Probably inefficient, possibly a load method is needed to store the event uri in this object?*/
		$query = " 	PREFIX foaf: <http://xmlns.com/foaf/0.1/>
        			PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#>
        			PREFIX bio: <http://purl.org/vocab/bio/0.1/>
            		PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
					SELECT ?e ?x ?bioBirthday WHERE {?z foaf:primaryTopic ?x . ".$this->getQueryBit().".}";
		
		$results = $foafData->getModel()->sparqlQuery($query);
		
		/*remove the statement if they've defined their birthday in this way*/
		if(isset($results[$index])){
			$remove_statement = new Statement(new BlankNode($results[$index]['?e']->uri), new Resource("http://purl.org/vocab/bio/0.1/date"), new Literal($results[$index]['?bioBirthday']->label));
			$foafData->getModel()->remove($remove_statement);
			/*and add another one if the value is set*/
			if($value){
				$add_statement = new Statement(new BlankNode($results[$index]['?e']->uri), new Resource("http://purl.org/vocab/bio/0.1/date"), new Literal($value));
				$foafData->getModel()->add($add_statement);
			}
		}
		
		//$found_model = $foafData->getModel()->find(new BlankNode($results[0]['?e']->uri), new Resource("http://purl.org/vocab/bio/0.1/date"), new Literal($results[0]['?bioBirthday']->label));
		
	}
}
?>